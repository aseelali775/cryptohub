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
    
    protected $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
    ];

    /**
     * نسبة تشابه العنوان التي نعتبر بعدها الخبر مكرراً.
     */
    private const DUPLICATE_SIMILARITY = 72;

    /**
     * عدد الأخبار الأخيرة التي نفحصها عند مقارنة العنوان.
     */
    private const DUPLICATE_CHECK_LIMIT = 200;

    public function handle()
    {
        $this->info('Starting automated news fetcher...');
        $this->info('Duplicate detection: ENABLED');
        $this->info('AI processing will happen separately.');

$sources = [
            'CoinTelegraph'   => 'https://cointelegraph.com/rss',
            'NewsBTC'         => 'https://www.newsbtc.com/feed/',
            'BitcoinMagazine' => 'https://bitcoinmagazine.com/feed',
            'TheDailyHodl'    => 'https://dailyhodl.com/feed/',
            'BeInCrypto'      => 'https://beincrypto.com/feed/',
            'CoinJournal'     => 'https://coinjournal.net/news/feed/',
            'CryptoDaily'     => 'https://cryptodaily.co.uk/feed',
            'Decrypt'         => 'https://decrypt.co/feed'
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

             /*
                |--------------------------------------------------------------------------
                | Convert XML to Array securely while preserving Namespaces (Images)
                |--------------------------------------------------------------------------
                */
                $namespaces = $xml->getNamespaces(true);
                $newsItems = [];
                
                // نتعامل مع عنصر واحد أو عدة عناصر
                $items = isset($xml->channel->item[0]) ? $xml->channel->item : [$xml->channel->item];

                foreach ($items as $xmlItem) {
                    if (!$xmlItem) continue;
                    
                    $itemArray = json_decode(json_encode($xmlItem), true);
                    
                    // حقن روابط الصور من وسم media (لحل مشكلة NewsBTC و CoinJournal)
                    if (isset($namespaces['media'])) {
                        $media = $xmlItem->children($namespaces['media']);
                        if (isset($media->content)) {
                            $itemArray['media:content']['@attributes']['url'] = (string) $media->content->attributes()['url'];
                        }
                        if (isset($media->thumbnail)) {
                            $itemArray['media:thumbnail']['@attributes']['url'] = (string) $media->thumbnail->attributes()['url'];
                        }
                    }
                    
                    // حقن النص الكامل من وسم content (لحل مشاكل بعض المواقع المتقدمة)
                    if (isset($namespaces['content'])) {
                        $content = $xmlItem->children($namespaces['content']);
                        if (isset($content->encoded)) {
                            $itemArray['content:encoded'] = (string) $content->encoded;
                        }
                    }
                    
                    $newsItems[] = $itemArray;
                }

                if (empty($newsItems)) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Sort newest first
                |--------------------------------------------------------------------------
                */
                usort($newsItems, function ($a, $b) {
                    return strtotime($b['pubDate'] ?? 'now') <=> strtotime($a['pubDate'] ?? 'now');
                });

                $count = 0;

                /*
                |--------------------------------------------------------------------------
                | Process RSS items
                |--------------------------------------------------------------------------
                */
                foreach ($newsItems as $item) {
                    /*
                    |--------------------------------------------------------------------------
                    | Maximum 3 articles from each source per run
                    |--------------------------------------------------------------------------
                    */
                    if ($count >= 3) {
                        break;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Extract title
                    |--------------------------------------------------------------------------
                    */
                    $title = is_array($item['title'] ?? null) ? ($item['title'][0] ?? '') : ($item['title'] ?? '');

                    /*
                    |--------------------------------------------------------------------------
                    | Extract URL
                    |--------------------------------------------------------------------------
                    */
                    $link = is_array($item['link'] ?? null) ? ($item['link'][0] ?? '') : ($item['link'] ?? '');
                    
                    $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $link = trim($link);

                    if (empty($title) || empty($link)) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 1. Exact URL duplicate check
                    |--------------------------------------------------------------------------
                    */
                    $existsByUrl = News::where('url', $link)->exists();
                    if ($existsByUrl) {
                        $this->warn("⏭ Duplicate URL: {$title}");
                        $totalDuplicates++;
                        Log::info('Duplicate news skipped by URL', [
                            'source' => $sourceName,
                            'title' => $title,
                            'url' => $link,
                        ]);
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 2. Exact normalized title check
                    |--------------------------------------------------------------------------
                    */
                    $normalizedTitle = $this->normalizeTitle($title);
                    $existsByTitle = News::whereNotNull('title_en')
                        ->get(['id', 'title_en'])
                        ->contains(function ($news) use ($normalizedTitle) {
                            return $this->normalizeTitle($news->title_en) === $normalizedTitle;
                        });

                    if ($existsByTitle) {
                        $this->warn("⏭ Duplicate title: {$title}");
                        $totalDuplicates++;
                        Log::info('Duplicate news skipped by normalized title', [
                            'source' => $sourceName,
                            'title' => $title,
                        ]);
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 3. Similar article detection
                    |--------------------------------------------------------------------------
                    */
                    $similarNews = $this->findSimilarNews($title);
                    if ($similarNews) {
                        $this->warn("⏭ Similar article detected:");
                        $this->warn("New: {$title}");
                        $this->warn("Existing: {$similarNews['title']}");
                        $this->warn("Similarity: {$similarNews['similarity']}%");
                        $totalDuplicates++;
                        Log::info('Similar crypto news skipped', [
                            'source' => $sourceName,
                            'new_title' => $title,
                            'existing_title' => $similarNews['title'],
                            'existing_id' => $similarNews['id'],
                            'similarity' => $similarNews['similarity'],
                        ]);
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | New article
                    |--------------------------------------------------------------------------
                    */
                    $this->info("🆕 New article: {$title}");

                    /*
                    |--------------------------------------------------------------------------
                    | Extract image
                    |--------------------------------------------------------------------------
                    */
                    $imageUrl = $this->extractImage($item);

                    /*
                    |--------------------------------------------------------------------------
                    | Extract full article
                    |--------------------------------------------------------------------------
                    */
                    $fullContent = $this->extractFullArticle($link);
                    $isSuccess = !empty($fullContent);

                    /*
                    |--------------------------------------------------------------------------
                    | RSS fallback
                    |--------------------------------------------------------------------------
                    */
                    if (!$isSuccess) {
                        $description = is_array($item['description'] ?? null) ? ($item['description'][0] ?? '') : ($item['description'] ?? '');
                        $fullContent = strip_tags($description);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Clean content
                    |--------------------------------------------------------------------------
                    */
                    $safeContentEn = Str::limit(trim(preg_replace('/\s+/', ' ', $fullContent)), 15000, '');

                    /*
                    |--------------------------------------------------------------------------
                    | Ignore very short articles
                    |--------------------------------------------------------------------------
                    */
                    if (mb_strlen($safeContentEn) < 100) {
                        $this->warn("⚠️ Skipped because article is too short.");
                        Log::warning('Skipped short crypto article', [
                            'source' => $sourceName,
                            'url' => $link,
                        ]);
                        $totalSkipped++;
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Extraction statistics
                    |--------------------------------------------------------------------------
                    */
                    if ($isSuccess) {
                        Log::info("Extraction Success", [
                            'source' => $sourceName,
                            'url' => $link,
                        ]);
                        $totalSuccess++;
                    } else {
                        Log::warning("Extraction Fallback", [
                            'source' => $sourceName,
                            'url' => $link,
                        ]);
                        $totalFallback++;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create article
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    | ai_processed = false
                    | This means the article will NOT be shown publicly
                    | until Gemini successfully processes it.
                    |
                    */
                    $news = News::create([
                        'title_en' => $title,
                        'content_en' => $safeContentEn,
                        /*
                        |--------------------------------------------------------------------------
                        | Arabic fields remain empty until AI processing.
                        |--------------------------------------------------------------------------
                        */
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
                        /*
                        |--------------------------------------------------------------------------
                        | AI has not processed this article yet.
                        |--------------------------------------------------------------------------
                        */
                        'ai_processed' => false,
                        /*
                        |--------------------------------------------------------------------------
                        | Initial defaults
                        |--------------------------------------------------------------------------
                        */
                        'sentiment' => 'Neutral',
                        'category' => 'Market',
                        'impact_score' => 5,
                        'keywords' => [],
                    ]);

                    $this->info("✅ Saved News ID {$news->id}");
                    $totalNew++;
                    $count++;

                    /*
                    |--------------------------------------------------------------------------
                    | Small delay between articles
                    |--------------------------------------------------------------------------
                    */
                    usleep(500000); // 0.5 seconds
                }
            } catch (\Throwable $e) {
                $this->error("Failed to process source {$sourceName}: {$e->getMessage()}");
                Log::error('Scraper Error', [
                    'source' => $sourceName,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Extraction success rate
        |--------------------------------------------------------------------------
        */
        $totalExtracted = $totalSuccess + $totalFallback;
        $rate = $totalExtracted > 0 ? round(($totalSuccess / $totalExtracted) * 100, 2) : 0;

        /*
        |--------------------------------------------------------------------------
        | Final statistics
        |--------------------------------------------------------------------------
        */
        $this->info('');
        $this->info('====================================');
        $this->info('NEWS FETCH COMPLETED');
        $this->info('====================================');
        $this->info("New Articles : {$totalNew} 🆕");
        $this->warn("Duplicates Skipped: {$totalDuplicates} ⏭");
        $this->warn("Too Short Skipped : {$totalSkipped} ⚠️");
        $this->info("Extraction Success : {$totalSuccess} ✅");
        $this->warn("Extraction Fallback: {$totalFallback} ⚠️");
        $this->info("Success Rate : {$rate}% 📊");
        $this->info('====================================');

        return self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize title
    |--------------------------------------------------------------------------
    |
    | Makes titles comparable even when punctuation/capitalization differs.
    |
    */
    private function normalizeTitle(string $title): string
    {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = Str::lower($title);

        /*
        |--------------------------------------------------------------------------
        | Remove URLs
        |--------------------------------------------------------------------------
        */
        $title = preg_replace('/https?:\/\/\S+/i', '', $title);

        /*
        |--------------------------------------------------------------------------
        | Replace punctuation with spaces
        |--------------------------------------------------------------------------
        */
        $title = preg_replace('/[^a-z0-9\s]/u', ' ', $title);

        /*
        |--------------------------------------------------------------------------
        | Normalize whitespace
        |--------------------------------------------------------------------------
        */
        $title = preg_replace('/\s+/', ' ', $title);

        return trim($title);
    }

    /*
    |--------------------------------------------------------------------------
    | Find similar existing article
    |--------------------------------------------------------------------------
    */
    private function findSimilarNews(string $newTitle): ?array
    {
        $normalizedNew = $this->normalizeTitle($newTitle);

        if (mb_strlen($normalizedNew) < 15) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Get recent articles only.
        |--------------------------------------------------------------------------
        |
        | We do not need to compare against thousands of articles.
        |
        */
        $existingNews = News::query()
            ->whereNotNull('title_en')
            ->latest('id')
            ->limit(self::DUPLICATE_CHECK_LIMIT)
            ->get(['id', 'title_en', 'source']);

        $bestMatch = null;

        foreach ($existingNews as $existing) {
            $existingTitle = trim((string) $existing->title_en);
            if ($existingTitle === '') {
                continue;
            }

            $normalizedExisting = $this->normalizeTitle($existingTitle);
            if ($normalizedExisting === '') {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Similar text percentage
            |--------------------------------------------------------------------------
            */
            similar_text($normalizedNew, $normalizedExisting, $percentage);

            /*
            |--------------------------------------------------------------------------
            | Token similarity
            |--------------------------------------------------------------------------
            |
            | This helps detect titles that use different wording
            | but contain the same important terms.
            |
            */
            $tokenSimilarity = $this->calculateTokenSimilarity($normalizedNew, $normalizedExisting);

            /*
            |--------------------------------------------------------------------------
            | Use the stronger signal
            |--------------------------------------------------------------------------
            */
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

    /*
    |--------------------------------------------------------------------------
    | Calculate token similarity
    |--------------------------------------------------------------------------
    */
    private function calculateTokenSimilarity(string $titleA, string $titleB): float
    {
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'for', 'with', 'as', 'at', 'by', 'from', 'is', 'are', 'was', 'were', 'has', 'have', 'had', 'this', 'that', 'after', 'before', 'over', 'into', 'its', 'their', 'how', 'why', 'what', 'new',
        ];

        $tokensA = collect(preg_split('/\s+/', $titleA))
            ->filter()
            ->reject(fn ($word) => in_array($word, $stopWords, true))
            ->unique()
            ->values()
            ->toArray();

        $tokensB = collect(preg_split('/\s+/', $titleB))
            ->filter()
            ->reject(fn ($word) => in_array($word, $stopWords, true))
            ->unique()
            ->values()
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Not enough meaningful words
        |--------------------------------------------------------------------------
        */
        if (count($tokensA) < 3 || count($tokensB) < 3) {
            return 0;
        }

        $intersection = count(array_intersect($tokensA, $tokensB));
        $union = count(array_unique(array_merge($tokensA, $tokensB)));

        if ($union === 0) {
            return 0;
        }

        return ($intersection / $union) * 100;
    }

    /*
    |--------------------------------------------------------------------------
    | Extract image
    |--------------------------------------------------------------------------
    */
   private function extractImage($item)
    {
        // 1. البحث في enclosure (المعيار الأساسي لمعظم المواقع)
        if (isset($item['enclosure']['@attributes']['url'])) {
            return $item['enclosure']['@attributes']['url'];
        }

        // 2. البحث في media:content (لحل مشكلة NewsBTC وغيرها)
        if (isset($item['media:content']['@attributes']['url'])) {
            return $item['media:content']['@attributes']['url'];
        }

        // 3. البحث في media:thumbnail كاحتياط
        if (isset($item['media:thumbnail']['@attributes']['url'])) {
            return $item['media:thumbnail']['@attributes']['url'];
        }

        // 4. استخراج الصورة من description (إذا كانت مضمنة كـ HTML)
        $htmlContent = is_array($item['description'] ?? null) ? ($item['description'][0] ?? '') : ($item['description'] ?? '');
        if (!empty($htmlContent)) {
            preg_match('/<img[^>]+src="([^">]+)"/i', $htmlContent, $matches);
            if (!empty($matches[1])) {
                return $matches[1];
            }
        }

        // 5. استخراج الصورة من content:encoded (لحل مشكلة بعض المواقع المتقدمة)
        $fullContent = is_array($item['content:encoded'] ?? null) ? ($item['content:encoded'][0] ?? '') : ($item['content:encoded'] ?? '');
        if (!empty($fullContent)) {
            preg_match('/<img[^>]+src="([^">]+)"/i', $fullContent, $matches);
            if (!empty($matches[1])) {
                return $matches[1];
            }
        }

        // 6. صورة الطوارئ الافتراضية
        return 'https://cryptologos.cc/logos/bitcoin-btc-logo.png';
    }
/*
    |--------------------------------------------------------------------------
    | Extract full article (With Debugging)
    |--------------------------------------------------------------------------
    */
    private function extractFullArticle($url)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(20)
                ->get($url);

            if (!$response->successful()) {
                Log::warning("Debug Extractor: HTTP Failed", ['url' => $url, 'status' => $response->status()]);
                return null;
            }

            $html = $response->body();

            /*
            |--------------------------------------------------------------------------
            | Cloudflare / protection detection
            |--------------------------------------------------------------------------
            */
            if (
                str_contains($html, 'Cloudflare') || 
                str_contains($html, 'Access Denied') || 
                str_contains($html, 'verify you are human') || 
                str_contains($html, 'Just a moment...')
            ) {
                Log::warning("Debug Extractor: Blocked by Cloudflare/Anti-bot", ['url' => $url]);
                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | Readability
            |--------------------------------------------------------------------------
            */
            $configuration = new Configuration();
            $configuration->setFixRelativeURLs(true);
            $configuration->setOriginalURL($url);
            $readability = new Readability($configuration);

            if (!$readability->parse($html)) {
                Log::warning("Debug Extractor: Readability Parse Failed", ['url' => $url]);
                return null;
            }

            $content = trim(strip_tags($readability->getContent()));
            
            if (mb_strlen($content) <= 200) {
                Log::warning("Debug Extractor: Content too short", ['url' => $url, 'length' => mb_strlen($content)]);
                return null;
            }

            return $content;

        } catch (ParseException $e) {
            Log::warning("Debug Extractor: ParseException", ['url' => $url, 'message' => $e->getMessage()]);
            return null;
        } catch (\Throwable $e) {
            Log::warning('Article extraction exception', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}