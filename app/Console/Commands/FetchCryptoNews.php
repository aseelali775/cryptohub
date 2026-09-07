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

    private const DUPLICATE_SIMILARITY = 72;
    private const DUPLICATE_CHECK_LIMIT = 200;

    public function handle()
    {
        $this->info('Starting automated news fetcher...');
        $this->info('Duplicate detection: ENABLED');
        $this->info('AI processing will happen separately.');

        $sources = [
            'CoinTelegraph'   => 'https://cointelegraph.com/rss',
            // 'NewsBTC'         => 'https://www.newsbtc.com/feed/',
            'BitcoinMagazine' => 'https://bitcoinmagazine.com/feed',
            // 'TheDailyHodl'    => 'https://dailyhodl.com/feed/',
            // 'BeInCrypto'      => 'https://beincrypto.com/feed/',
            // 'CoinJournal'     => 'https://coinjournal.net/news/feed/',
            // 'CryptoDaily'     => 'https://cryptodaily.co.uk/feed',
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

                $namespaces = $xml->getNamespaces(true);
                $newsItems = [];
                
                $items = isset($xml->channel->item[0]) ? $xml->channel->item : [$xml->channel->item];

                foreach ($items as $xmlItem) {
                    if (!$xmlItem) continue;
                    
                    $itemArray = json_decode(json_encode($xmlItem), true);
                    
                    if (isset($namespaces['media'])) {
                        $media = $xmlItem->children($namespaces['media']);
                        if (isset($media->content)) {
                            $itemArray['media:content']['@attributes']['url'] = (string) $media->content->attributes()['url'];
                        }
                        if (isset($media->thumbnail)) {
                            $itemArray['media:thumbnail']['@attributes']['url'] = (string) $media->thumbnail->attributes()['url'];
                        }
                    }
                    
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

                usort($newsItems, function ($a, $b) {
                    return strtotime($b['pubDate'] ?? 'now') <=> strtotime($a['pubDate'] ?? 'now');
                });

                $count = 0;

                foreach ($newsItems as $item) {
                    if ($count >= 3) {
                        break;
                    }

                    $title = is_array($item['title'] ?? null) ? ($item['title'][0] ?? '') : ($item['title'] ?? '');
                    $link = is_array($item['link'] ?? null) ? ($item['link'][0] ?? '') : ($item['link'] ?? '');
                    
                    $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $link = trim($link);

                    if (empty($title) || empty($link)) {
                        continue;
                    }

                    $existsByUrl = News::where('url', $link)->exists();
                    if ($existsByUrl) {
                        $this->warn("⏭ Duplicate URL: {$title}");
                        $totalDuplicates++;
                        continue;
                    }

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

                    $similarNews = $this->findSimilarNews($title);
                    if ($similarNews) {
                        $this->warn("⏭ Similar article detected: New: {$title} | Existing: {$similarNews['title']} ({$similarNews['similarity']}%)");
                        $totalDuplicates++;
                        continue;
                    }

                    $this->info("🆕 New article: {$title}");

                    $imageUrl = $this->extractImage($item);
                    $fullContent = $this->extractFullArticle($link);
                    $isSuccess = !empty($fullContent);

                    if (!$isSuccess) {
                        $description = is_array($item['description'] ?? null) ? ($item['description'][0] ?? '') : ($item['description'] ?? '');
                        $fullContent = strip_tags($description);
                    }

                    $safeContentEn = Str::limit(trim(preg_replace('/\s+/', ' ', $fullContent)), 15000, '');

                    if (mb_strlen($safeContentEn) < 100) {
                        $this->warn("⚠️ Skipped because article is too short.");
                        $totalSkipped++;
                        continue;
                    }

                    // =========================================================
                    // 🛡️ PRE-RELEVANCE FILTER
                    // =========================================================
                    $unsuitableReason = $this->isClearlyUnsuitableContent($title, $safeContentEn);

                    if ($unsuitableReason !== null) {
                        News::create([
                            'title_en' => $title,
                            'content_en' => $safeContentEn,

                            'title_ar' => null,
                            'content_ar' => null,
                            'summary_ar' => null,
                            'meta_description_ar' => null,

                            'why_it_matters_ar' => null,
                            'analysis_ar' => null,
                            'context_ar' => null,
                            'what_to_watch_ar' => null,
                            'limitations_ar' => null,

                            'image_url' => $imageUrl,
                            'source' => $sourceName,
                            'url' => $link,

                            'ai_processed' => false,

                            'status' => 'rejected',
                            'rejection_reason' => $unsuitableReason,

                            'sentiment' => 'Neutral',
                            'category' => 'Market',
                            'impact_score' => 0,

                            'slug' => null,
                            'keywords' => [],
                        ]);

                        $this->warn("🚫 Pre-filter rejected: {$title} [{$unsuitableReason}]");
                        $totalSkipped++;
                        continue;
                    }

                    if ($isSuccess) {
                        $totalSuccess++;
                    } else {
                        $totalFallback++;
                    }

                    // =========================================================
                    // ✅ PENDING ARTICLE
                    // =========================================================
                    $news = News::create([
                        'title_en' => $title,
                        'content_en' => $safeContentEn,

                        'title_ar' => null,
                        'content_ar' => null,
                        'summary_ar' => null,
                        'meta_description_ar' => null,

                        'why_it_matters_ar' => null,
                        'analysis_ar' => null,
                        'context_ar' => null,
                        'what_to_watch_ar' => null,
                        'limitations_ar' => null,

                        'image_url' => $imageUrl,
                        'source' => $sourceName,
                        'url' => $link,

                        'ai_processed' => false,

                        'status' => 'pending',
                        'rejection_reason' => null,

                        'sentiment' => 'Neutral',
                        'category' => 'Market',
                        'impact_score' => 5,

                        'slug' => null,
                        'keywords' => [],
                    ]);

                    $this->info("✅ Saved Pending News ID {$news->id}");
                    $totalNew++;
                    $count++;

                    usleep(500000); 
                }
            } catch (\Throwable $e) {
                $this->error("Failed to process source {$sourceName}: {$e->getMessage()}");
            }
        }

        $totalExtracted = $totalSuccess + $totalFallback;
        $rate = $totalExtracted > 0 ? round(($totalSuccess / $totalExtracted) * 100, 2) : 0;

        $this->newLine();
        $this->info('====================================');
        $this->info('NEWS FETCH COMPLETED');
        $this->info('====================================');
        $this->info("New Articles : {$totalNew} 🆕");
        $this->warn("Duplicates/Skipped: " . ($totalDuplicates + $totalSkipped) . " ⏭");
        $this->info("Extraction Success : {$totalSuccess} ✅");
        $this->warn("Extraction Fallback: {$totalFallback} ⚠️");
        $this->info("Success Rate : {$rate}% 📊");
        $this->info('====================================');

        return self::SUCCESS;
    }

    /**
     * =========================================================
     * 🛡️ PRE-RELEVANCE FILTER
     * =========================================================
     */
    private function isClearlyUnsuitableContent(string $title, string $content = ''): ?string 
    {
        $text = mb_strtolower(trim($title . ' ' . mb_substr($content, 0, 4000)), 'UTF-8');

        $casinoTerms = [
            'crypto casino', 'crypto casinos', 'online casino', 'online casinos',
            'casino dealer', 'casino vip', 'casino bonus', 'casino bonuses', 'casino games',
            'gambling site', 'gambling sites', 'crypto gambling', 'online gambling',
            'roulette', 'blackjack', 'slot machine', 'slot machines', 'slots', 'poker casino',
            'igaming', 'i-gaming',
        ];

        foreach ($casinoTerms as $term) {
            if (str_contains($text, $term)) return 'gambling';
        }

        $hasCasino = str_contains($text, 'casino') || str_contains($text, 'casinos');
        $hasGambling = str_contains($text, 'gambling');
        $hasCasinoContext = str_contains($text, 'roulette') || str_contains($text, 'blackjack') ||
                            str_contains($text, 'slots') || str_contains($text, 'slot machine') ||
                            str_contains($text, 'poker') || str_contains($text, 'casino dealer') ||
                            str_contains($text, 'casino vip') || str_contains($text, 'casino bonus');

        if (($hasCasino && $hasGambling) || ($hasCasino && $hasCasinoContext)) {
            return 'gambling';
        }

        $hasBetting = str_contains($text, 'betting') || str_contains($text, 'sportsbook') || str_contains($text, 'sports betting');
        $hasBettingContext = str_contains($text, 'casino') || str_contains($text, 'gambling') ||
                             str_contains($text, 'sportsbook') || str_contains($text, 'odds') ||
                             str_contains($text, 'wager') || str_contains($text, 'wagers') ||
                             str_contains($text, 'betting platform') || str_contains($text, 'betting site') ||
                             str_contains($text, 'betting sites');

        if ($hasBetting && $hasBettingContext) {
            return 'gambling';
        }

        $lotteryTerms = [
            'lottery jackpot', 'lottery ticket', 'winning lottery',
            'lottery winner', 'lottery drawing', 'lottery draw',
        ];

        foreach ($lotteryTerms as $term) {
            if (str_contains($text, $term)) return 'off_topic';
        }

        return null;
    }

    private function normalizeTitle(string $title): string
    {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = Str::lower($title);
        $title = preg_replace('/https?:\/\/\S+/i', '', $title);
        $title = preg_replace('/[^a-z0-9\s]/u', ' ', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        return trim($title);
    }

    private function findSimilarNews(string $newTitle): ?array
    {
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

    private function calculateTokenSimilarity(string $titleA, string $titleB): float
    {
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'for', 'with', 'as', 'at', 'by', 'from', 'is', 'are', 'was', 'were', 'has', 'have', 'had', 'this', 'that', 'after', 'before', 'over', 'into', 'its', 'their', 'how', 'why', 'what', 'new'];
        
        $tokensA = collect(preg_split('/\s+/', $titleA))->filter()->reject(fn ($word) => in_array($word, $stopWords, true))->unique()->values()->toArray();
        $tokensB = collect(preg_split('/\s+/', $titleB))->filter()->reject(fn ($word) => in_array($word, $stopWords, true))->unique()->values()->toArray();

        if (count($tokensA) < 3 || count($tokensB) < 3) return 0;

        $intersection = count(array_intersect($tokensA, $tokensB));
        $union = count(array_unique(array_merge($tokensA, $tokensB)));

        return $union === 0 ? 0 : ($intersection / $union) * 100;
    }

    private function extractImage($item)
    {
        if (isset($item['enclosure']['@attributes']['url'])) return $item['enclosure']['@attributes']['url'];
        if (isset($item['media:content']['@attributes']['url'])) return $item['media:content']['@attributes']['url'];
        if (isset($item['media:thumbnail']['@attributes']['url'])) return $item['media:thumbnail']['@attributes']['url'];
        
        $htmlContent = is_array($item['description'] ?? null) ? ($item['description'][0] ?? '') : ($item['description'] ?? '');
        if (!empty($htmlContent)) {
            preg_match('/<img[^>]+src="([^">]+)"/i', $htmlContent, $matches);
            if (!empty($matches[1])) return $matches[1];
        }

        $fullContent = is_array($item['content:encoded'] ?? null) ? ($item['content:encoded'][0] ?? '') : ($item['content:encoded'] ?? '');
        if (!empty($fullContent)) {
            preg_match('/<img[^>]+src="([^">]+)"/i', $fullContent, $matches);
            if (!empty($matches[1])) return $matches[1];
        }

        return 'https://cryptologos.cc/logos/bitcoin-btc-logo.png';
    }

    private function extractFullArticle($url)
    {
        try {
            $response = Http::withHeaders($this->headers)->timeout(20)->get($url);
            if (!$response->successful()) return null;

            $html = $response->body();

            if (str_contains($html, 'Cloudflare') || str_contains($html, 'Access Denied') || str_contains($html, 'verify you are human') || str_contains($html, 'Just a moment...')) {
                return null;
            }

            $configuration = new Configuration();
            $configuration->setFixRelativeURLs(true);
            $configuration->setOriginalURL($url);
            $readability = new Readability($configuration);

            if (!$readability->parse($html)) return null;

            $content = trim(strip_tags($readability->getContent()));
            return mb_strlen($content) <= 200 ? null : $content;

        } catch (ParseException $e) {
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}