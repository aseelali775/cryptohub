<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\News;
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;

class FetchCryptoNews extends Command
{
    protected $signature = 'crypto:fetch-news';
    protected $description = 'Fetch crypto news from multiple sources, extract full articles, detect duplicates, and store new articles.';
    
    // 🟢 1. تحديث الـ Headers لتصبح متطابقة تماماً مع متصفح حقيقي لتخطي بعض الحمايات
    protected $headers = [
        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Connection'      => 'keep-alive',
        'Upgrade-Insecure-Requests' => '1',
        'Sec-Fetch-Dest'  => 'document',
        'Sec-Fetch-Mode'  => 'navigate',
        'Sec-Fetch-Site'  => 'none',
        'Sec-Fetch-User'  => '?1',
    ];

    /**
     * نسبة تشابه العنوان التي نعتبر بعدها الخبر مكرراً.
     */
    private const DUPLICATE_SIMILARITY = 72;

    /**
     * عدد الأخبار الأخيرة التي نفحصها عند مقارنة العنوان.
     */
    private const DUPLICATE_CHECK_LIMIT = 200;

    /**
     * 🟢 2. زيادة عدد الأخبار المسحوبة من كل مصدر (كانت 3 وأصبحت 15)
     */
    private const MAX_ARTICLES_PER_SOURCE = 15;

    public function handle()
    {
        $this->info('Starting automated tireless news fetcher...');
        $this->info('Duplicate detection: ENABLED');
        $this->info('AI processing will happen separately.');

        $sources = [
            'CoinTelegraph'   => 'https://cointelegraph.com/rss',
            'CoinDesk'        => 'https://www.coindesk.com/arc/outboundfeeds/rss/',
            'Decrypt'         => 'https://decrypt.co/feed',
            'BitcoinMagazine' => 'https://bitcoinmagazine.com/feed',
        ];

        $totalSuccess = 0;
        $totalFallback = 0;
        $totalNew = 0;
        $totalDuplicates = 0;
        $totalSkipped = 0;

        foreach ($sources as $sourceName => $rssUrl) {
            $this->info("====================================");
            $this->info("Fetching RSS from: {$sourceName}");
            $this->info("====================================");

            try {
                $response = Http::timeout(20)->get($rssUrl);

                if (!$response->successful()) {
                    $this->error("RSS request failed for {$sourceName}: HTTP {$response->status()}");
                    Log::error('RSS request failed', [
                        'source' => $sourceName,
                        'status' => $response->status(),
                    ]);
                    continue;
                }

                $xmlString = $response->body();
                $xml = @simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);

                if (!$xml || !isset($xml->channel->item)) {
                    $this->warn("No RSS items found for {$sourceName}");
                    continue;
                }

                $json = json_encode($xml->channel->item);
                $newsItems = json_decode($json, true);

                if (!is_array($newsItems)) {
                    continue;
                }

                if (isset($newsItems['title'])) {
                    $newsItems = [$newsItems];
                }

                // Sort newest first
                usort($newsItems, function ($a, $b) {
                    return strtotime($b['pubDate'] ?? 'now') <=> strtotime($a['pubDate'] ?? 'now');
                });

                $count = 0;

                foreach ($newsItems as $item) {
                    // الحد الأقصى للمقالات من كل مصدر
                    if ($count >= self::MAX_ARTICLES_PER_SOURCE) {
                        break;
                    }

                    $title = is_array($item['title'] ?? null) ? ($item['title'][0] ?? '') : ($item['title'] ?? '');
                    $link = is_array($item['link'] ?? null) ? ($item['link'][0] ?? '') : ($item['link'] ?? '');

                    $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $link = trim($link);

                    if (empty($title) || empty($link)) {
                        continue;
                    }

                    // 1. Exact URL duplicate check
                    if (News::where('url', $link)->exists()) {
                        $this->warn("⏭ Duplicate URL: {$title}");
                        $totalDuplicates++;
                        continue;
                    }

                    // 2. Exact normalized title check
                    $normalizedTitle = $this->normalizeTitle($title);
                    $existsByTitle = News::whereNotNull('title_en')
                        ->get(['id', 'title_en'])
                        ->contains(function ($news) use ($normalizedTitle) {
                            return $this->normalizeTitle($news->title_en) === $normalizedTitle;
                        });

                    if ($existsByTitle) {
                        $this->warn("⏭ Duplicate title: {$title}");
                        $totalDuplicates++;
                        continue;
                    }

                    // 3. Similar article detection
                    $similarNews = $this->findSimilarNews($title);
                    if ($similarNews) {
                        $this->warn("⏭ Similar article detected: Similarity {$similarNews['similarity']}%");
                        $totalDuplicates++;
                        continue;
                    }

                    $this->info("🆕 New article: {$title}");

                    $imageUrl = $this->extractImage($item);
                    
                    // محاولة سحب المقال الكامل
                    $fullContent = $this->extractFullArticle($link);
                    $isSuccess = !empty($fullContent);

                    // 🟢 RSS Fallback (إذا فشل السحب بسبب Cloudflare، نستخدم الوصف من RSS)
                    if (!$isSuccess) {
                        $description = is_array($item['description'] ?? null) ? ($item['description'][0] ?? '') : ($item['description'] ?? '');
                        $fullContent = strip_tags($description);
                    }

                    // تنظيف المحتوى
                    $safeContentEn = Str::limit(trim(preg_replace('/\s+/', ' ', $fullContent)), 15000, '');

                    // 🟢 تجاهل الأخبار القصيرة جداً (أقل من 100 حرف)
                    if (mb_strlen($safeContentEn) < 100) {
                        $this->warn("⚠️ Skipped because article is too short.");
                        $totalSkipped++;
                        continue;
                    }

                    if ($isSuccess) {
                        $totalSuccess++;
                    } else {
                        Log::warning("Extraction Fallback", ['source' => $sourceName, 'url' => $link]);
                        $totalFallback++;
                    }

                    // حفظ الخبر
                    $news = News::create([
                        'title_en' => $title,
                        'content_en' => $safeContentEn,
                        'title_ar' => null,
                        'content_ar' => null,
                        'summary_ar' => null,
                        'why_it_matters_ar' => null,
                        'analysis_ar' => null,
                        'context_ar' => null,
                        'what_to_watch_ar' => null,
                        'limitations_ar' => null,
                        'image_url' => $imageUrl,
                        'source' => $sourceName,
                        'url' => $link,
                        'ai_processed' => false,
                        'sentiment' => 'Neutral',
                        'category' => 'Market',
                        'impact_score' => 5,
                        'keywords' => [],
                    ]);

                    $this->info("✅ Saved News ID {$news->id}");
                    $totalNew++;
                    $count++;

                    usleep(500000); // نصف ثانية استراحة
                }
            } catch (\Throwable $e) {
                $this->error("Failed to process source {$sourceName}: {$e->getMessage()}");
                Log::error('Scraper Error', [
                    'source' => $sourceName,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $totalExtracted = $totalSuccess + $totalFallback;
        $rate = $totalExtracted > 0 ? round(($totalSuccess / $totalExtracted) * 100, 2) : 0;

        $this->info('');
        $this->info('====================================');
        $this->info('NEWS FETCH COMPLETED');
        $this->info('====================================');
        $this->info("New Articles      : {$totalNew} 🆕");
        $this->warn("Duplicates Skipped: {$totalDuplicates} ⏭");
        $this->warn("Too Short Skipped : {$totalSkipped} ⚠️");
        $this->info("Extraction Success: {$totalSuccess} ✅");
        $this->warn("Extraction Fallback: {$totalFallback} ⚠️");
        $this->info("Success Rate       : {$rate}% 📊");
        $this->info('====================================');

        return self::SUCCESS;
    }

    private function normalizeTitle(string $title): string {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = Str::lower($title);
        $title = preg_replace('/https?:\/\/\S+/i', '', $title);
        $title = preg_replace('/[^a-z0-9\s]/u', ' ', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        return trim($title);
    }

    private function findSimilarNews(string $newTitle): ?array {
        $normalizedNew = $this->normalizeTitle($newTitle);
        if (mb_strlen($normalizedNew) < 15) return null;

        $existingNews = News::query()
            ->whereNotNull('title_en')
            ->latest('id')
            ->limit(self::DUPLICATE_CHECK_LIMIT)
            ->get(['id', 'title_en', 'source']);

        $bestMatch = null;

        foreach ($existingNews as $existing) {
            $existingTitle = trim((string) $existing->title_en);
            if ($existingTitle === '') continue;

            $normalizedExisting = $this->normalizeTitle($existingTitle);
            if ($normalizedExisting === '') continue;

            similar_text($normalizedNew, $normalizedExisting, $percentage);
            $tokenSimilarity = $this->calculateTokenSimilarity($normalizedNew, $normalizedExisting);

            $score = max($percentage, $tokenSimilarity);

            if ($score >= self::DUPLICATE_SIMILARITY) {
                if ($bestMatch === null || $score > $bestMatch['similarity']) {
                    $bestMatch = [
                        'id' => $existing->id,
                        'title' => $existingTitle,
                        'source' => $existing->source,
                        'similarity' => round($score, 2),
                    ];
                }
            }
        }
        return $bestMatch;
    }

    private function calculateTokenSimilarity(string $titleA, string $titleB): float {
        $stopWords = ['the','a','an','and','or','of','to','in','on','for','with','as','at','by','from','is','are','was','were','has','have','had','this','that','after','before','over','into','its','their','how','why','what','new'];
        
        $getTokens = function($title) use ($stopWords) {
            return collect(preg_split('/\s+/', $title))
                ->filter()
                ->reject(fn ($word) => in_array($word, $stopWords, true))
                ->unique()
                ->values()
                ->toArray();
        };

        $tokensA = $getTokens($titleA);
        $tokensB = $getTokens($titleB);

        if (count($tokensA) < 3 || count($tokensB) < 3) return 0;

        $intersection = count(array_intersect($tokensA, $tokensB));
        $union = count(array_unique(array_merge($tokensA, $tokensB)));

        if ($union === 0) return 0;
        return ($intersection / $union) * 100;
    }

    private function extractImage($item) {
        if (isset($item['enclosure']['@attributes']['url'])) {
            return $item['enclosure']['@attributes']['url'];
        }
        
        $htmlContent = is_array($item['description'] ?? null) ? ($item['description'][0] ?? '') : ($item['description'] ?? '');
        if (!empty($htmlContent)) {
            preg_match('/<img[^>]+src="([^">]+)"/i', $htmlContent, $matches);
            if (!empty($matches[1])) return $matches[1];
        }
        return 'https://cryptologos.cc/logos/bitcoin-btc-logo.png';
    }

    private function extractFullArticle($url) {
        try {
            $response = Http::withHeaders($this->headers)->timeout(20)->get($url);

            if (!$response->successful()) return null;

            $html = $response->body();

            // التعرف على الحمايات للهروب منها فوراً واستخدام Fallback
            if (
                str_contains($html, 'Cloudflare') || 
                str_contains($html, 'Access Denied') || 
                str_contains($html, 'verify you are human') || 
                str_contains($html, 'Just a moment...') ||
                str_contains($html, 'challenge-platform')
            ) {
                return null;
            }

            $configuration = new Configuration();
            $configuration->setFixRelativeURLs(true);
            $configuration->setOriginalURL($url);
            $readability = new Readability($configuration);

            if (!$readability->parse($html)) return null;

            $content = trim(strip_tags($readability->getContent()));
            
            return mb_strlen($content) > 200 ? $content : null;

        } catch (\Throwable $e) {
            return null;
        }
    }
}