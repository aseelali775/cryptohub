<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;

class FetchCryptoNews extends Command
{
    protected $signature = 'crypto:fetch-news';

    protected $description =
        'Fetch crypto news from multiple sources, extract full articles, detect duplicates, repair weak existing articles, and store new articles.';


    /*
    |--------------------------------------------------------------------------
    | HTTP Headers
    |--------------------------------------------------------------------------
    */

    protected $headers = [

        'User-Agent' =>
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',

        'Accept' =>
            'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',

        'Accept-Language' =>
            'en-US,en;q=0.5',
    ];


    /*
    |--------------------------------------------------------------------------
    | Content Thresholds
    |--------------------------------------------------------------------------
    |
    | This MUST stay aligned with ProcessNewsWithAI.
    |
    */

    private const MIN_SOURCE_LENGTH = 400;


    /*
    |--------------------------------------------------------------------------
    | Duplicate Detection
    |--------------------------------------------------------------------------
    */

    private const DUPLICATE_SIMILARITY = 85;

    private const DUPLICATE_CHECK_LIMIT = 300;


    /*
    |--------------------------------------------------------------------------
    | Fetching
    |--------------------------------------------------------------------------
    */

    private const ARTICLES_PER_SOURCE = 3;

    private const HTTP_TIMEOUT = 20;


    /*
    |--------------------------------------------------------------------------
    | Main Handler
    |--------------------------------------------------------------------------
    */

    public function handle(): int
    {
        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            '        AQL CRYPTO NEWS FETCHER'
        );

        $this->info(
            '=============================================='
        );

        $this->info(
            'Minimum source content: ' .
            self::MIN_SOURCE_LENGTH .
            ' chars'
        );

        $this->info(
            'Duplicate detection: ENABLED'
        );

        $this->info(
            'Weak existing articles: REFETCH ENABLED'
        );

        $this->info(
            'AI processing: SEPARATE COMMAND'
        );

        $this->newLine();


        /*
        |--------------------------------------------------------------------------
        | RSS Sources
        |--------------------------------------------------------------------------
        */

        $sources = [

            'CoinTelegraph' =>
                'https://cointelegraph.com/rss',

            'Decrypt' =>
                'https://decrypt.co/feed',

            'BitcoinMagazine' =>
                'https://bitcoinmagazine.com/feed',
        ];


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalSuccess = 0;

        $totalFallback = 0;

        $totalNew = 0;

        $totalDuplicates = 0;

        $totalSkipped = 0;

        $totalRefetched = 0;

        $totalRefetchFailed = 0;


        /*
        |--------------------------------------------------------------------------
        | Process Sources
        |--------------------------------------------------------------------------
        */

        foreach (
            $sources as $sourceName => $rssUrl
        ) {

            $this->info(
                '===================================='
            );

            $this->info(
                "Fetching RSS from: {$sourceName}"
            );

            $this->info(
                '===================================='
            );


            try {

                /*
                |--------------------------------------------------------------------------
                | Fetch RSS
                |--------------------------------------------------------------------------
                */

                $response =
                    Http::timeout(
                        self::HTTP_TIMEOUT
                    )
                    ->withHeaders(
                        $this->headers
                    )
                    ->get(
                        $rssUrl
                    );


                if (
                    !$response->successful()
                ) {

                    $this->error(
                        "RSS request failed for {$sourceName}: HTTP {$response->status()}"
                    );


                    Log::error(
                        'RSS request failed',
                        [
                            'source' =>
                                $sourceName,

                            'status' =>
                                $response->status(),

                            'url' =>
                                $rssUrl,
                        ]
                    );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Parse XML
                |--------------------------------------------------------------------------
                */

                $xmlString =
                    $response->body();


                $xml =
                    @simplexml_load_string(
                        $xmlString,
                        'SimpleXMLElement',
                        LIBXML_NOCDATA
                    );


                if (
                    !$xml ||
                    !isset(
                        $xml->channel->item
                    )
                ) {

                    $this->warn(
                        "No RSS items found for {$sourceName}"
                    );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Convert RSS Items
                |--------------------------------------------------------------------------
                */

                $json =
                    json_encode(
                        $xml->channel->item
                    );


                $newsItems =
                    json_decode(
                        $json,
                        true
                    );


                if (
                    !is_array(
                        $newsItems
                    )
                ) {

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize Single Item
                |--------------------------------------------------------------------------
                */

                if (
                    isset(
                        $newsItems['title']
                    )
                ) {

                    $newsItems = [
                        $newsItems,
                    ];
                }


                /*
                |--------------------------------------------------------------------------
                | Newest First
                |--------------------------------------------------------------------------
                */

                usort(
                    $newsItems,
                    function (
                        $a,
                        $b
                    ) {

                        return strtotime(
                            $b['pubDate'] ?? 'now'
                        )
                        <=>
                        strtotime(
                            $a['pubDate'] ?? 'now'
                        );
                    }
                );


                $count = 0;


                /*
                |--------------------------------------------------------------------------
                | Process Items
                |--------------------------------------------------------------------------
                */

                foreach (
                    $newsItems as $item
                ) {

                    if (
                        $count >=
                        self::ARTICLES_PER_SOURCE
                    ) {

                        break;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Extract Title
                    |--------------------------------------------------------------------------
                    */

                    $title =
                        $this->extractTitle(
                            $item
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Extract URL
                    |--------------------------------------------------------------------------
                    */

                    $link =
                        $this->extractLink(
                            $item
                        );


                    if (
                        $title === '' ||
                        $link === ''
                    ) {

                        continue;
                    }


                    $this->line(
                        "Checking: {$title}"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Check Existing URL
                    |--------------------------------------------------------------------------
                    */

                    $existingByUrl =
                        News::query()
                            ->where(
                                'url',
                                $link
                            )
                            ->first();


                    /*
                    |--------------------------------------------------------------------------
                    | Existing Article With Weak Content
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    |
                    | This is what allows old weak articles such as ID 274
                    | to be refetched.
                    |
                    */

                    if (
                        $existingByUrl
                    ) {

                        $existingLength =
                            mb_strlen(
                                trim(
                                    (string)
                                    $existingByUrl->content_en
                                )
                            );


                        if (
                            $existingLength <
                            self::MIN_SOURCE_LENGTH
                        ) {

                            $this->warn(
                                "🔄 Existing weak article found: ID {$existingByUrl->id}"
                            );


                            $this->line(
                                "Current content: {$existingLength} chars"
                            );


                            $fullContent =
                                $this->extractFullArticle(
                                    $link
                                );


                            $safeContent =
                                $this->prepareContent(
                                    $fullContent
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | RSS fallback
                            |--------------------------------------------------------------------------
                            */

                            if (
                                mb_strlen(
                                    $safeContent
                                ) <
                                self::MIN_SOURCE_LENGTH
                            ) {

                                $rssContent =
                                    $this->extractRssContent(
                                        $item
                                    );


                                $safeContent =
                                    $this->prepareContent(
                                        $rssContent
                                    );


                                if (
                                    mb_strlen(
                                        $safeContent
                                    ) >
                                    $existingLength
                                ) {

                                    $this->info(
                                        '📦 RSS fallback produced better content.'
                                    );
                                }
                            }


                            $newLength =
                                mb_strlen(
                                    $safeContent
                                );


                            if (
                                $newLength >=
                                self::MIN_SOURCE_LENGTH
                            ) {

                                /*
                                |----------------------------------------------------------------------
                                | Update weak article
                                |----------------------------------------------------------------------
                                */

                                $existingByUrl->update(
                                    [
                                        'title_en' =>
                                            $title,

                                        'content_en' =>
                                            $safeContent,

                                        'source' =>
                                            $sourceName,

                                        'url' =>
                                            $link,
                                    ]
                                );


                                $this->info(
                                    "✅ Refetched article ID {$existingByUrl->id}"
                                );


                                $this->line(
                                    "Old length: {$existingLength}"
                                );


                                $this->line(
                                    "New length: {$newLength}"
                                );


                                Log::info(
                                    'Weak existing article successfully refetched',
                                    [
                                        'news_id' =>
                                            $existingByUrl->id,

                                        'source' =>
                                            $sourceName,

                                        'url' =>
                                            $link,

                                        'old_length' =>
                                            $existingLength,

                                        'new_length' =>
                                            $newLength,
                                    ]
                                );


                                $totalRefetched++;

                            } else {

                                $this->warn(
                                    "⚠️ Refetch did not produce enough content for ID {$existingByUrl->id}"
                                );


                                Log::warning(
                                    'Weak existing article refetch failed',
                                    [
                                        'news_id' =>
                                            $existingByUrl->id,

                                        'source' =>
                                            $sourceName,

                                        'url' =>
                                            $link,

                                        'old_length' =>
                                            $existingLength,

                                        'new_length' =>
                                            $newLength,
                                    ]
                                );


                                $totalRefetchFailed++;
                            }


                            $count++;


                            usleep(
                                500000
                            );


                            continue;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Existing article already healthy
                        |--------------------------------------------------------------------------
                        */

                        $this->warn(
                            "⏭ Existing URL: {$title}"
                        );


                        $totalDuplicates++;


                        Log::info(
                            'Existing crypto news skipped by URL',
                            [
                                'source' =>
                                    $sourceName,

                                'title' =>
                                    $title,

                                'url' =>
                                    $link,

                                'existing_id' =>
                                    $existingByUrl->id,
                            ]
                        );


                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Normalized Title
                    |--------------------------------------------------------------------------
                    */

                    $normalizedTitle =
                        $this->normalizeTitle(
                            $title
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Exact Normalized Title Check
                    |--------------------------------------------------------------------------
                    */

                    $existsByTitle =
                        $this->findExactNormalizedTitle(
                            $normalizedTitle
                        );


                    if (
                        $existsByTitle
                    ) {

                        /*
                        |----------------------------------------------------------------------
                        | If the existing matching article is weak,
                        | attempt to refetch it as well.
                        |----------------------------------------------------------------------
                        */

                        $existingLength =
                            mb_strlen(
                                trim(
                                    (string)
                                    $existsByTitle->content_en
                                )
                            );


                        if (
                            $existingLength <
                            self::MIN_SOURCE_LENGTH
                        ) {

                            $this->warn(
                                "🔄 Matching weak article found by title: ID {$existsByTitle->id}"
                            );


                            $fullContent =
                                $this->extractFullArticle(
                                    $link
                                );


                            $safeContent =
                                $this->prepareContent(
                                    $fullContent
                                );


                            if (
                                mb_strlen(
                                    $safeContent
                                ) <
                                self::MIN_SOURCE_LENGTH
                            ) {

                                $safeContent =
                                    $this->prepareContent(
                                        $this->extractRssContent(
                                            $item
                                        )
                                    );
                            }


                            $newLength =
                                mb_strlen(
                                    $safeContent
                                );


                            if (
                                $newLength >=
                                self::MIN_SOURCE_LENGTH
                            ) {

                                $existsByTitle->update(
                                    [
                                        'content_en' =>
                                            $safeContent,

                                        'source' =>
                                            $sourceName,

                                        'url' =>
                                            $link,
                                    ]
                                );


                                $this->info(
                                    "✅ Refetched weak article ID {$existsByTitle->id}"
                                );


                                $this->line(
                                    "Old length: {$existingLength}"
                                );


                                $this->line(
                                    "New length: {$newLength}"
                                );


                                $totalRefetched++;

                            } else {

                                $totalRefetchFailed++;
                            }


                            $count++;


                            continue;
                        }


                        /*
                        |----------------------------------------------------------------------
                        | Healthy exact-title duplicate
                        |----------------------------------------------------------------------
                        */

                        $this->warn(
                            "⏭ Duplicate title: {$title}"
                        );


                        $totalDuplicates++;


                        Log::info(
                            'Duplicate news skipped by normalized title',
                            [
                                'source' =>
                                    $sourceName,

                                'title' =>
                                    $title,

                                'existing_id' =>
                                    $existsByTitle->id,
                            ]
                        );


                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Similar Article Detection
                    |--------------------------------------------------------------------------
                    */

                    $similarNews =
                        $this->findSimilarNews(
                            $title
                        );


                    if (
                        $similarNews
                    ) {

                        $this->warn(
                            '⏭ Similar article detected:'
                        );


                        $this->warn(
                            "New: {$title}"
                        );


                        $this->warn(
                            "Existing: {$similarNews['title']}"
                        );


                        $this->warn(
                            "Similarity: {$similarNews['similarity']}%"
                        );


                        $totalDuplicates++;


                        Log::info(
                            'Similar crypto news skipped',
                            [
                                'source' =>
                                    $sourceName,

                                'new_title' =>
                                    $title,

                                'existing_title' =>
                                    $similarNews['title'],

                                'existing_id' =>
                                    $similarNews['id'],

                                'similarity' =>
                                    $similarNews['similarity'],
                            ]
                        );


                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | New Article
                    |--------------------------------------------------------------------------
                    */

                    $this->info(
                        "🆕 New article: {$title}"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Extract Image
                    |--------------------------------------------------------------------------
                    */

                    $imageUrl =
                        $this->extractImage(
                            $item
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Extract Full Article
                    |--------------------------------------------------------------------------
                    */

                    $fullContent =
                        $this->extractFullArticle(
                            $link
                        );


                    $isSuccess =
                        !empty(
                            $fullContent
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Clean Extracted Content
                    |--------------------------------------------------------------------------
                    */

                    $safeContentEn =
                        $this->prepareContent(
                            $fullContent
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | RSS Fallback
                    |--------------------------------------------------------------------------
                    */

                    if (
                        mb_strlen(
                            $safeContentEn
                        ) <
                        self::MIN_SOURCE_LENGTH
                    ) {

                        $rssContent =
                            $this->extractRssContent(
                                $item
                            );


                        $rssSafeContent =
                            $this->prepareContent(
                                $rssContent
                            );


                        /*
                        |----------------------------------------------------------------------
                        | Use RSS only if it is better.
                        |----------------------------------------------------------------------
                        */

                        if (
                            mb_strlen(
                                $rssSafeContent
                            ) >
                            mb_strlen(
                                $safeContentEn
                            )
                        ) {

                            $safeContentEn =
                                $rssSafeContent;


                            $isSuccess = false;
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Reject Very Short Article
                    |--------------------------------------------------------------------------
                    */

                    $finalContentLength =
                        mb_strlen(
                            $safeContentEn
                        );


                    if (
                        $finalContentLength <
                        self::MIN_SOURCE_LENGTH
                    ) {

                        $this->warn(
                            "⚠️ Skipped because usable article content is below " .
                            self::MIN_SOURCE_LENGTH .
                            " characters."
                        );


                        $this->line(
                            "Length: {$finalContentLength}"
                        );


                        Log::warning(
                            'Skipped crypto article because usable content is too short',
                            [
                                'source' =>
                                    $sourceName,

                                'url' =>
                                    $link,

                                'content_length' =>
                                    $finalContentLength,

                                'minimum_required' =>
                                    self::MIN_SOURCE_LENGTH,
                            ]
                        );


                        $totalSkipped++;

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Extraction Statistics
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $isSuccess
                    ) {

                        Log::info(
                            'Article extraction success',
                            [
                                'source' =>
                                    $sourceName,

                                'url' =>
                                    $link,

                                'content_length' =>
                                    $finalContentLength,
                            ]
                        );


                        $totalSuccess++;

                    } else {

                        Log::warning(
                            'Article extraction fallback',
                            [
                                'source' =>
                                    $sourceName,

                                'url' =>
                                    $link,

                                'content_length' =>
                                    $finalContentLength,
                            ]
                        );


                        $totalFallback++;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Create Article
                    |--------------------------------------------------------------------------
                    */

                    $news =
                        News::create(
                            [

                                'title_en' =>
                                    $title,

                                'content_en' =>
                                    $safeContentEn,

                                /*
                                |--------------------------------------------------------------
                                | Arabic fields
                                |--------------------------------------------------------------
                                */

                                'title_ar' =>
                                    null,

                                'content_ar' =>
                                    null,

                                'summary_ar' =>
                                    null,

                                'why_it_matters_ar' =>
                                    null,

                                'analysis_ar' =>
                                    null,

                                'context_ar' =>
                                    null,

                                'what_to_watch_ar' =>
                                    null,

                                'limitations_ar' =>
                                    null,

                                /*
                                |--------------------------------------------------------------
                                | Media / Source
                                |--------------------------------------------------------------
                                */

                                'image_url' =>
                                    $imageUrl,

                                'source' =>
                                    $sourceName,

                                'url' =>
                                    $link,

                                /*
                                |--------------------------------------------------------------
                                | AI
                                |--------------------------------------------------------------
                                */

                                'ai_processed' =>
                                    false,

                                /*
                                |--------------------------------------------------------------
                                | Defaults
                                |--------------------------------------------------------------
                                */

                                'sentiment' =>
                                    'Neutral',

                                'category' =>
                                    'Market',

                                'impact_score' =>
                                    5,

                                'keywords' =>
                                    [],
                            ]
                        );


                    $this->info(
                        "✅ Saved News ID {$news->id}"
                    );


                    $this->line(
                        "Source content: {$finalContentLength} chars"
                    );


                    $totalNew++;

                    $count++;


                    /*
                    |--------------------------------------------------------------------------
                    | Small Delay
                    |--------------------------------------------------------------------------
                    */

                    usleep(
                        500000
                    );
                }

            } catch (
                \Throwable $e
            ) {

                $this->error(
                    "Failed to process source {$sourceName}: {$e->getMessage()}"
                );


                Log::error(
                    'Scraper Error',
                    [
                        'source' =>
                            $sourceName,

                        'message' =>
                            $e->getMessage(),

                        'trace' =>
                            $e->getTraceAsString(),
                    ]
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Extraction Success Rate
        |--------------------------------------------------------------------------
        */

        $totalExtracted =
            $totalSuccess +
            $totalFallback;


        $rate =
            $totalExtracted > 0

                ? round(
                    (
                        $totalSuccess /
                        $totalExtracted
                    ) *
                    100,
                    2
                )

                : 0;


        /*
        |--------------------------------------------------------------------------
        | Final Statistics
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            '===================================='
        );

        $this->info(
            'NEWS FETCH COMPLETED'
        );

        $this->info(
            '===================================='
        );


        $this->info(
            "New Articles        : {$totalNew} 🆕"
        );


        $this->info(
            "Weak Articles Fixed : {$totalRefetched} 🔄"
        );


        $this->warn(
            "Refetch Failed      : {$totalRefetchFailed} ⚠️"
        );


        $this->warn(
            "Duplicates Skipped  : {$totalDuplicates} ⏭"
        );


        $this->warn(
            "Too Short Skipped   : {$totalSkipped} ⚠️"
        );


        $this->info(
            "Extraction Success  : {$totalSuccess} ✅"
        );


        $this->warn(
            "Extraction Fallback : {$totalFallback} ⚠️"
        );


        $this->info(
            "Success Rate        : {$rate}% 📊"
        );


        $this->info(
            '===================================='
        );


        return self::SUCCESS;
    }


    /*
    |--------------------------------------------------------------------------
    | Extract Title
    |--------------------------------------------------------------------------
    */

    private function extractTitle(
        array $item
    ): string {

        $title =
            is_array(
                $item['title'] ?? null
            )
                ? (
                    $item['title'][0] ??
                    ''
                )
                : (
                    $item['title'] ??
                    ''
                );


        return trim(
            html_entity_decode(
                strip_tags(
                    (string)
                    $title
                ),
                ENT_QUOTES |
                ENT_HTML5,
                'UTF-8'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Extract Link
    |--------------------------------------------------------------------------
    */

    private function extractLink(
        array $item
    ): string {

        $link =
            is_array(
                $item['link'] ?? null
            )
                ? (
                    $item['link'][0] ??
                    ''
                )
                : (
                    $item['link'] ??
                    ''
                );


        return trim(
            (string)
            $link
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Find Exact Normalized Title
    |--------------------------------------------------------------------------
    */

    private function findExactNormalizedTitle(
        string $normalizedTitle
    ): ?News {

        if (
            $normalizedTitle === ''
        ) {

            return null;
        }


        $existingNews =
            News::query()
                ->whereNotNull(
                    'title_en'
                )
                ->latest('id')
                ->limit(
                    self::DUPLICATE_CHECK_LIMIT
                )
                ->get(
                    [
                        'id',
                        'title_en',
                        'content_en',
                        'source',
                        'url',
                    ]
                );


        foreach (
            $existingNews as $news
        ) {

            if (
                $this->normalizeTitle(
                    (string)
                    $news->title_en
                )
                ===
                $normalizedTitle
            ) {

                return $news;
            }
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Title
    |--------------------------------------------------------------------------
    */

    private function normalizeTitle(
        string $title
    ): string {

        $title =
            html_entity_decode(
                $title,
                ENT_QUOTES |
                ENT_HTML5,
                'UTF-8'
            );


        $title =
            Str::lower(
                $title
            );


        $title =
            preg_replace(
                '/https?:\/\/\S+/i',
                '',
                $title
            );


        $title =
            preg_replace(
                '/[^a-z0-9\s]/u',
                ' ',
                $title
            );


        $title =
            preg_replace(
                '/\s+/u',
                ' ',
                $title
            );


        return trim(
            $title
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Find Similar News
    |--------------------------------------------------------------------------
    */

    private function findSimilarNews(
        string $newTitle
    ): ?array {

        $normalizedNew =
            $this->normalizeTitle(
                $newTitle
            );


        if (
            mb_strlen(
                $normalizedNew
            ) < 15
        ) {

            return null;
        }


        $existingNews =
            News::query()
                ->whereNotNull(
                    'title_en'
                )
                ->latest('id')
                ->limit(
                    self::DUPLICATE_CHECK_LIMIT
                )
                ->get(
                    [
                        'id',
                        'title_en',
                        'source',
                    ]
                );


        $bestMatch = null;


        foreach (
            $existingNews as $existing
        ) {

            $existingTitle =
                trim(
                    (string)
                    $existing->title_en
                );


            if (
                $existingTitle === ''
            ) {

                continue;
            }


            $normalizedExisting =
                $this->normalizeTitle(
                    $existingTitle
                );


            if (
                $normalizedExisting === ''
            ) {

                continue;
            }


            similar_text(
                $normalizedNew,
                $normalizedExisting,
                $percentage
            );


            $tokenSimilarity =
                $this->calculateTokenSimilarity(
                    $normalizedNew,
                    $normalizedExisting
                );


            $score =
                max(
                    $percentage,
                    $tokenSimilarity
                );


            if (
                $score >=
                self::DUPLICATE_SIMILARITY
            ) {

                if (
                    $bestMatch === null ||
                    $score >
                    $bestMatch['similarity']
                ) {

                    $bestMatch = [

                        'id' =>
                            $existing->id,

                        'title' =>
                            $existingTitle,

                        'source' =>
                            $existing->source,

                        'similarity' =>
                            round(
                                $score,
                                2
                            ),
                    ];
                }
            }
        }


        return $bestMatch;
    }


    /*
    |--------------------------------------------------------------------------
    | Token Similarity
    |--------------------------------------------------------------------------
    */

    private function calculateTokenSimilarity(
        string $titleA,
        string $titleB
    ): float {

        $stopWords = [

            'the',
            'a',
            'an',
            'and',
            'or',
            'of',
            'to',
            'in',
            'on',
            'for',
            'with',
            'as',
            'at',
            'by',
            'from',
            'is',
            'are',
            'was',
            'were',
            'has',
            'have',
            'had',
            'this',
            'that',
            'after',
            'before',
            'over',
            'into',
            'its',
            'their',
            'how',
            'why',
            'what',
            'new',
        ];


        $tokensA =
            collect(
                preg_split(
                    '/\s+/',
                    $titleA
                )
            )
                ->filter()
                ->reject(
                    fn ($word) =>
                        in_array(
                            $word,
                            $stopWords,
                            true
                        )
                )
                ->unique()
                ->values()
                ->toArray();


        $tokensB =
            collect(
                preg_split(
                    '/\s+/',
                    $titleB
                )
            )
                ->filter()
                ->reject(
                    fn ($word) =>
                        in_array(
                            $word,
                            $stopWords,
                            true
                        )
                )
                ->unique()
                ->values()
                ->toArray();


        if (
            count($tokensA) < 3 ||
            count($tokensB) < 3
        ) {

            return 0;
        }


        $intersection =
            count(
                array_intersect(
                    $tokensA,
                    $tokensB
                )
            );


        $union =
            count(
                array_unique(
                    array_merge(
                        $tokensA,
                        $tokensB
                    )
                )
            );


        if (
            $union === 0
        ) {

            return 0;
        }


        return (
            $intersection /
            $union
        ) *
        100;
    }


    /*
    |--------------------------------------------------------------------------
    | Extract RSS Image
    |--------------------------------------------------------------------------
    */

    private function extractImage(
        array $item
    ): ?string {

        if (
            isset(
                $item['enclosure']['@attributes']['url']
            )
        ) {

            return trim(
                (string)
                $item['enclosure']['@attributes']['url']
            );
        }


        if (
            isset(
                $item['enclosure']['url']
            )
        ) {

            return trim(
                (string)
                $item['enclosure']['url']
            );
        }


        $rssContent =
            $this->extractRssContent(
                $item
            );


        if (
            $rssContent !== ''
        ) {

            preg_match(
                '/<img[^>]+src=["\']([^"\']+)["\']/i',
                $rssContent,
                $matches
            );


            if (
                !empty(
                    $matches[1]
                )
            ) {

                return trim(
                    $matches[1]
                );
            }
        }


        return
            'https://cryptologos.cc/logos/bitcoin-btc-logo.png';
    }


    /*
    |--------------------------------------------------------------------------
    | Extract RSS Content
    |--------------------------------------------------------------------------
    */

    private function extractRssContent(
        array $item
    ): string {

        $possibleFields = [

            'content:encoded',

            'content',

            'description',

            'summary',
        ];


        foreach (
            $possibleFields as $field
        ) {

            if (
                !isset(
                    $item[$field]
                )
            ) {

                continue;
            }


            $value =
                is_array(
                    $item[$field]
                )
                    ? (
                        $item[$field][0] ??
                        ''
                    )
                    : (
                        $item[$field] ??
                        ''
                    );


            if (
                !is_string(
                    $value
                )
            ) {

                continue;
            }


            if (
                trim(
                    $value
                ) === ''
            ) {

                continue;
            }


            return $value;
        }


        return '';
    }


    /*
    |--------------------------------------------------------------------------
    | Prepare Content
    |--------------------------------------------------------------------------
    */

    private function prepareContent(
        ?string $content
    ): string {

        if (
            !is_string(
                $content
            )
        ) {

            return '';
        }


        $content =
            trim(
                $content
            );


        if (
            $content === ''
        ) {

            return '';
        }


        /*
        |----------------------------------------------------------------------
        | Remove scripts / styles
        |----------------------------------------------------------------------
        */

        $content =
            preg_replace(
                '/<script\b[^>]*>.*?<\/script>/is',
                ' ',
                $content
            );


        $content =
            preg_replace(
                '/<style\b[^>]*>.*?<\/style>/is',
                ' ',
                $content
            );


        /*
        |----------------------------------------------------------------------
        | Convert HTML to text
        |----------------------------------------------------------------------
        */

        $content =
            strip_tags(
                $content
            );


        /*
        |----------------------------------------------------------------------
        | Decode entities
        |----------------------------------------------------------------------
        */

        $content =
            html_entity_decode(
                $content,
                ENT_QUOTES |
                ENT_HTML5,
                'UTF-8'
            );


        /*
        |----------------------------------------------------------------------
        | Normalize whitespace
        |----------------------------------------------------------------------
        */

        $content =
            preg_replace(
                '/\s+/u',
                ' ',
                $content
            );


        $content =
            trim(
                $content
            );


        /*
        |----------------------------------------------------------------------
        | Limit size
        |----------------------------------------------------------------------
        */

        return Str::limit(
            $content,
            15000,
            ''
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Extract Full Article
    |--------------------------------------------------------------------------
    */

    private function extractFullArticle(
        string $url
    ): ?string {

        try {

            $response =
                Http::withHeaders(
                    $this->headers
                )
                    ->timeout(
                        self::HTTP_TIMEOUT
                    )
                    ->get(
                        $url
                    );


            if (
                !$response->successful()
            ) {

                Log::warning(
                    'Article fetch failed',
                    [
                        'url' =>
                            $url,

                        'status' =>
                            $response->status(),
                    ]
                );


                return null;
            }


            $html =
                $response->body();


            if (
                trim(
                    $html
                ) === ''
            ) {

                return null;
            }


            /*
            |--------------------------------------------------------------------------
            | Protection Detection
            |--------------------------------------------------------------------------
            */

            $protectionIndicators = [

                'Cloudflare',

                'Access Denied',

                'verify you are human',

                'Just a moment...',

                'cf-chl',

                'challenge-platform',

                'captcha',
            ];


            foreach (
                $protectionIndicators as $indicator
            ) {

                if (
                    stripos(
                        $html,
                        $indicator
                    ) !== false
                ) {

                    Log::warning(
                        'Article page appears protected',
                        [
                            'url' =>
                                $url,

                            'indicator' =>
                                $indicator,
                        ]
                    );


                    return null;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Readability
            |--------------------------------------------------------------------------
            */

            $configuration =
                new Configuration();


            $configuration->setFixRelativeURLs(
                true
            );


            $configuration->setOriginalURL(
                $url
            );


            $readability =
                new Readability(
                    $configuration
                );


            if (
                !$readability->parse(
                    $html
                )
            ) {

                return null;
            }


            $content =
                $readability->getContent();


            $content =
                $this->prepareContent(
                    $content
                );


            if (
                mb_strlen(
                    $content
                ) <
                self::MIN_SOURCE_LENGTH
            ) {

                return null;
            }


            return $content;

        } catch (
            ParseException $e
        ) {

            Log::warning(
                'Readability parse exception',
                [
                    'url' =>
                        $url,

                    'message' =>
                        $e->getMessage(),
                ]
            );


            return null;

        } catch (
            \Throwable $e
        ) {

            Log::warning(
                'Article extraction exception',
                [
                    'url' =>
                        $url,

                    'message' =>
                        $e->getMessage(),
                ]
            );


            return null;
        }
    }
}