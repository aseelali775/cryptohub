<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessNewsWithAI extends Command
{
    protected $signature = 'news:process-ai
                            {--limit=3 : Number of unprocessed news to process}';

    protected $description =
        'Analyze new crypto news and generate original Arabic editorial analysis using Gemini AI.';


    /*
    |--------------------------------------------------------------------------
    | Gemini Configuration
    |--------------------------------------------------------------------------
    */

    private const GEMINI_MODEL = 'gemini-3.6-flash';


    /*
    |--------------------------------------------------------------------------
    | Default Batch Limit
    |--------------------------------------------------------------------------
    */

    private const DEFAULT_BATCH_LIMIT = 3;


    /*
    |--------------------------------------------------------------------------
    | Delay Between Requests
    |--------------------------------------------------------------------------
    */

    private const RATE_LIMIT_SECONDS = 20;


    /*
    |--------------------------------------------------------------------------
    | Maximum Source Length
    |--------------------------------------------------------------------------
    */

    private const MAX_SOURCE_LENGTH = 12000;


    /*
    |--------------------------------------------------------------------------
    | Minimum Source Length
    |--------------------------------------------------------------------------
    |
    | News below 300 characters should be refetched instead of
    | being sent to AI.
    |
    */

    private const MIN_SOURCE_LENGTH = 300;


    /*
    |--------------------------------------------------------------------------
    | Minimum Generated Arabic Article Length
    |--------------------------------------------------------------------------
    */

    private const MIN_CONTENT_AR_LENGTH = 400;


    /*
    |--------------------------------------------------------------------------
    | Maximum Generated Arabic Article Length
    |--------------------------------------------------------------------------
    */

    private const MAX_CONTENT_AR_LENGTH = 12000;


    /*
    |--------------------------------------------------------------------------
    | Temporary API Retry Configuration
    |--------------------------------------------------------------------------
    */

    private const MAX_API_RETRIES = 2;

    private const RETRY_BASE_SECONDS = 5;


    /*
    |--------------------------------------------------------------------------
    | Main Handler
    |--------------------------------------------------------------------------
    */

    public function handle(): int
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {

            $this->error(
                '❌ GEMINI_API_KEY is missing.'
            );

            Log::error(
                'Gemini API Key missing.'
            );

            return self::FAILURE;
        }


        /*
        |--------------------------------------------------------------------------
        | Determine batch limit
        |--------------------------------------------------------------------------
        */

        $limit = (int) $this->option('limit');

        if ($limit <= 0) {

            $limit =
                self::DEFAULT_BATCH_LIMIT;
        }


        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            '        AQL CRYPTO AI PROCESSING'
        );

        $this->info(
            '=============================================='
        );

        $this->info(
            'Model: ' . self::GEMINI_MODEL
        );

        $this->info(
            'Batch limit: ' . $limit
        );

        $this->newLine();


        /*
        |--------------------------------------------------------------------------
        | Get only unprocessed news
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | This command is for NEW articles.
        |
        | Already processed articles are handled by:
        |
        | news:repair-ai
        |
        */

        $newsList = News::query()
            ->where(
                'ai_processed',
                false
            )
            ->whereNotNull(
                'title_en'
            )
            ->where(
                'title_en',
                '!=',
                ''
            )
            ->whereNotNull(
                'content_en'
            )
            ->where(
                'content_en',
                '!=',
                ''
            )
            ->orderByDesc(
                'created_at'
            )
            ->limit(
                $limit
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | No articles
        |--------------------------------------------------------------------------
        */

        if (
            $newsList->isEmpty()
        ) {

            $this->info(
                '✅ No unprocessed articles found.'
            );

            return self::SUCCESS;
        }


        $this->info(
            "Found {$newsList->count()} unprocessed articles."
        );


        /*
        |--------------------------------------------------------------------------
        | Counters
        |--------------------------------------------------------------------------
        */

        $processed = 0;

        $failed = 0;

        $skipped = 0;

        $validationFailed = 0;

        $apiFailed = 0;

        $quotaExceeded = false;


        /*
        |--------------------------------------------------------------------------
        | Process Articles
        |--------------------------------------------------------------------------
        */

        foreach (
            $newsList as $index => $news
        ) {

            $this->newLine();

            $this->info(
                "Processing ID {$news->id}: {$news->title_en}"
            );


            try {

                /*
                |--------------------------------------------------------------------------
                | Prepare Source
                |--------------------------------------------------------------------------
                */

                $title = trim(
                    (string) $news->title_en
                );


                $content = trim(
                    mb_substr(
                        (string) $news->content_en,
                        0,
                        self::MAX_SOURCE_LENGTH
                    )
                );


                /*
                |--------------------------------------------------------------------------
                | Validate Title
                |--------------------------------------------------------------------------
                */

                if (
                    mb_strlen($title) < 5
                ) {

                    $this->warn(
                        "⚠️ Article {$news->id} has an invalid title. Skipping."
                    );


                    Log::warning(
                        'AI skipped article with invalid title',
                        [
                            'news_id' => $news->id,
                        ]
                    );


                    $skipped++;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Validate Original Source Length
                |--------------------------------------------------------------------------
                |
                | Very short original articles should be refetched.
                |
                */

                $sourceLength =
                    mb_strlen($content);


                if (
                    $sourceLength <
                    self::MIN_SOURCE_LENGTH
                ) {

                    $this->warn(
                        "⚠️ Article {$news->id} source content is too short for AI."
                    );

                    $this->warn(
                        "Content length: {$sourceLength}"
                    );

                    $this->warn(
                        'Recommended action: REFETCH CONTENT.'
                    );


                    Log::warning(
                        'AI skipped article because original content is too short',
                        [
                            'news_id' => $news->id,
                            'content_length' => $sourceLength,
                            'minimum_required' =>
                                self::MIN_SOURCE_LENGTH,
                        ]
                    );


                    $skipped++;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Analyze With Gemini
                |--------------------------------------------------------------------------
                */

                $result =
                    $this->analyzeWithGemini(
                        $news,
                        $title,
                        $content,
                        $apiKey
                    );


                /*
                |--------------------------------------------------------------------------
                | Quota Exhausted
                |--------------------------------------------------------------------------
                */

                if (
                    is_array($result) &&
                    ($result['__quota_exceeded'] ?? false)
                ) {

                    $quotaExceeded =
                        true;


                    $failed++;

                    $this->error(
                        '🚫 Gemini quota has been exhausted.'
                    );

                    $this->warn(
                        '🛑 Stopping AI processing cycle.'
                    );


                    Log::warning(
                        'AI processing cycle stopped because Gemini quota was exhausted',
                        [
                            'news_id' => $news->id,
                            'model' =>
                                self::GEMINI_MODEL,
                            'message' =>
                                $result['__message'] ?? null,
                            'retry_seconds' =>
                                $result['__retry_seconds'] ?? null,
                        ]
                    );


                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | Temporary API Failure
                |--------------------------------------------------------------------------
                */

                if (
                    is_array($result) &&
                    ($result['__temporary_failure'] ?? false)
                ) {

                    $apiFailed++;

                    $failed++;

                    $this->error(
                        "❌ Temporary Gemini API failure for ID {$news->id}."
                    );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Generic API Failure
                |--------------------------------------------------------------------------
                */

                if (
                    is_array($result) &&
                    ($result['__api_failure'] ?? false)
                ) {

                    $apiFailed++;

                    $failed++;

                    $this->error(
                        "❌ Gemini API failure for ID {$news->id}."
                    );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Null Response
                |--------------------------------------------------------------------------
                */

                if (
                    $result === null
                ) {

                    $apiFailed++;

                    $failed++;

                    $this->error(
                        "❌ Gemini returned no usable result for ID {$news->id}."
                    );


                    Log::warning(
                        'Gemini returned null result',
                        [
                            'news_id' => $news->id,
                        ]
                    );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Validate AI Result
                |--------------------------------------------------------------------------
                */

                if (
                    !$this->isValidResult(
                        $result
                    )
                ) {

                    $validationFailed++;

                    $failed++;

                    $this->error(
                        "❌ AI result failed validation for ID {$news->id}."
                    );


                    Log::warning(
                        'AI result failed validation',
                        [
                            'news_id' => $news->id,
                            'title' =>
                                $news->title_en,
                        ]
                    );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Build Slug
                |--------------------------------------------------------------------------
                */

                $slug =
                    $this->buildSlug(
                        $title,
                        $news->id
                    );


                /*
                |--------------------------------------------------------------------------
                | Normalize Keywords
                |--------------------------------------------------------------------------
                */

                $keywords =
                    $this->normalizeKeywords(
                        $result['keywords']
                    );


                if (
                    count($keywords) < 3
                ) {

                    $validationFailed++;

                    $failed++;

                    $this->error(
                        "❌ Invalid keywords for ID {$news->id}."
                    );


                    Log::warning(
                        'AI keywords validation failed',
                        [
                            'news_id' => $news->id,
                            'keywords' =>
                                $result['keywords'] ?? null,
                        ]
                    );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize Sentiment
                |--------------------------------------------------------------------------
                */

                $sentiment =
                    $this->normalizeSentiment(
                        $result['sentiment'] ?? null
                    );


                /*
                |--------------------------------------------------------------------------
                | Normalize Category
                |--------------------------------------------------------------------------
                */

                $category =
                    $this->normalizeCategory(
                        $result['category'] ?? null
                    );


                /*
                |--------------------------------------------------------------------------
                | Normalize Impact Score
                |--------------------------------------------------------------------------
                */

                $impactScore =
                    $this->normalizeImpactScore(
                        $result['impact_score'] ?? null
                    );


                /*
                |--------------------------------------------------------------------------
                | Prepare Arabic Fields
                |--------------------------------------------------------------------------
                */

                $titleAr =
                    trim(
                        (string)
                        $result['title_ar']
                    );


                $contentAr =
                    trim(
                        (string)
                        $result['content_ar']
                    );


                $summaryAr =
                    trim(
                        (string)
                        $result['summary_ar']
                    );


                $metaDescriptionAr =
                    trim(
                        (string)
                        (
                            $result['meta_description_ar']
                            ?? ''
                        )
                    );


                $whyItMattersAr =
                    trim(
                        (string)
                        $result['why_it_matters_ar']
                    );


                $analysisAr =
                    trim(
                        (string)
                        $result['analysis_ar']
                    );


                $contextAr =
                    trim(
                        (string)
                        $result['context_ar']
                    );


                $whatToWatchAr =
                    trim(
                        (string)
                        $result['what_to_watch_ar']
                    );


                $limitationsAr =
                    trim(
                        (string)
                        $result['limitations_ar']
                    );


                /*
                |--------------------------------------------------------------------------
                | Final Validation
                |--------------------------------------------------------------------------
                */

                if (
                    !$this->validateFinalContent(
                        $titleAr,
                        $contentAr,
                        $summaryAr,
                        $whyItMattersAr,
                        $analysisAr,
                        $contextAr,
                        $whatToWatchAr,
                        $limitationsAr,
                        $metaDescriptionAr
                    )
                ) {

                    $validationFailed++;

                    $failed++;

                    $this->error(
                        "❌ Final content validation failed for ID {$news->id}."
                    );


                    Log::warning(
                        'AI final content validation failed',
                        [
                            'news_id' => $news->id,

                            'lengths' => [
                                'title_ar' =>
                                    mb_strlen(
                                        $titleAr
                                    ),

                                'content_ar' =>
                                    mb_strlen(
                                        $contentAr
                                    ),

                                'summary_ar' =>
                                    mb_strlen(
                                        $summaryAr
                                    ),

                                'why_it_matters_ar' =>
                                    mb_strlen(
                                        $whyItMattersAr
                                    ),

                                'analysis_ar' =>
                                    mb_strlen(
                                        $analysisAr
                                    ),

                                'context_ar' =>
                                    mb_strlen(
                                        $contextAr
                                    ),

                                'what_to_watch_ar' =>
                                    mb_strlen(
                                        $whatToWatchAr
                                    ),

                                'limitations_ar' =>
                                    mb_strlen(
                                        $limitationsAr
                                    ),

                                'meta_description_ar' =>
                                    mb_strlen(
                                        $metaDescriptionAr
                                    ),
                            ],
                        ]
                    );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Save
                |--------------------------------------------------------------------------
                */

                $news->update(
                    [

                        'slug' =>
                            $slug,

                        'title_ar' =>
                            $titleAr,

                        'content_ar' =>
                            $contentAr,

                        'summary_ar' =>
                            $summaryAr,

                        'why_it_matters_ar' =>
                            $whyItMattersAr,

                        'analysis_ar' =>
                            $analysisAr,

                        'context_ar' =>
                            $contextAr,

                        'what_to_watch_ar' =>
                            $whatToWatchAr,

                        'limitations_ar' =>
                            $limitationsAr,

                        'keywords' =>
                            $keywords,

                        'sentiment' =>
                            $sentiment,

                        'category' =>
                            $category,

                        'impact_score' =>
                            $impactScore,

                        /*
                        |------------------------------------------------------
                        | Important:
                        |
                        | Mark processed ONLY after successful validation
                        | and database update.
                        |------------------------------------------------------
                        */

                        'ai_processed' =>
                            true,
                    ]
                );


                $processed++;


                $this->info(
                    "✅ AI editorial analysis saved for ID {$news->id}."
                );


                $this->line(
                    "Content AR: " .
                    mb_strlen($contentAr) .
                    " chars"
                );


                $this->line(
                    "Category: {$category}"
                );


                $this->line(
                    "Sentiment: {$sentiment}"
                );


                $this->line(
                    "Impact: {$impactScore}/10"
                );


            } catch (\Throwable $e) {

                $failed++;


                $this->error(
                    "❌ Processing failed for ID {$news->id}: {$e->getMessage()}"
                );


                Log::error(
                    'AI News Processing Exception',
                    [
                        'news_id' =>
                            $news->id,

                        'message' =>
                            $e->getMessage(),

                        'trace' =>
                            $e->getTraceAsString(),
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Delay Between Requests
            |--------------------------------------------------------------------------
            */

            $isLastArticle =
                $index ===
                $newsList->count() - 1;


            if (
                !$isLastArticle &&
                !$quotaExceeded
            ) {

                sleep(
                    self::RATE_LIMIT_SECONDS
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Clear AI Caches
        |--------------------------------------------------------------------------
        */

        Cache::forget(
            'ai_market_dashboard_stats'
        );

        Cache::forget(
            'ai_market_impact_news'
        );


        /*
        |--------------------------------------------------------------------------
        | Final Report
        |--------------------------------------------------------------------------
        */

        $this->newLine();


        $this->info(
            '🧹 AI Market cache cleared.'
        );


        $this->info(
            "📊 Processed: {$processed}"
        );


        $this->info(
            "❌ Failed: {$failed}"
        );


        $this->info(
            "⏭️ Skipped: {$skipped}"
        );


        $this->info(
            "⚠️ Validation Failed: {$validationFailed}"
        );


        $this->info(
            "🌐 API Failed: {$apiFailed}"
        );


        if (
            $quotaExceeded
        ) {

            $this->warn(
                '🚫 Gemini quota was exhausted. Processing stopped safely.'
            );
        }


        $this->info(
            '🚀 Aql Crypto AI Editorial Cycle Completed.'
        );


        /*
        |--------------------------------------------------------------------------
        | Exit Status
        |--------------------------------------------------------------------------
        */

        if (
            $quotaExceeded &&
            $processed === 0
        ) {

            return self::FAILURE;
        }


        if (
            $failed > 0 &&
            $processed === 0
        ) {

            return self::FAILURE;
        }


        return self::SUCCESS;
    }


    /*
    |--------------------------------------------------------------------------
    | Gemini Analysis
    |--------------------------------------------------------------------------
    */

    private function analyzeWithGemini(
        News $news,
        string $title,
        string $content,
        string $apiKey
    ): ?array {

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            self::GEMINI_MODEL,
            $apiKey
        );


        /*
        |--------------------------------------------------------------------------
        | Editorial Prompt
        |--------------------------------------------------------------------------
        */

        $prompt = <<<PROMPT
You are the senior editorial analyst for Aql Crypto.

Your task is to create an ORIGINAL Arabic cryptocurrency news article
based ONLY on the supplied source material.

This is journalism and editorial analysis.

It is NOT financial advice.

Do NOT provide investment recommendations.

Do NOT invent facts.

Do NOT use outside information.

Do NOT introduce facts from your general knowledge.

==================================================
SOURCE FIDELITY
==================================================

Use only information explicitly contained in the supplied source.

Preserve accurately:

- names
- companies
- organizations
- dates
- numbers
- percentages
- cryptocurrency names
- ticker symbols
- technical terms
- events
- claims
- quotations

Never invent:

- people
- companies
- partnerships
- investments
- statistics
- market prices
- market capitalization
- trading volume
- blockchain data
- regulatory decisions
- dates
- quotations
- sources
- citations
- events

If something is not contained in the source,
do not present it as a fact.

==================================================
ORIGINALITY
==================================================

Do NOT translate sentence by sentence.

Do NOT copy the source.

Do NOT preserve the source paragraph structure.

Write an independently structured Arabic article.

Explain the significance of the facts using cautious editorial reasoning.

Use expressions such as:

"قد يشير ذلك إلى"

"قد يعكس"

"من المحتمل أن"

"يمكن أن يعني"

"قد يؤدي إلى"

"لا يمكن الجزم بأن"

when discussing interpretation.

Never present an inference as a confirmed fact.

==================================================
ARTICLE REQUIREMENTS
==================================================

The content_ar field is the main published article.

It MUST NOT be a short summary.

It MUST NOT be only a translation.

It MUST contain at least 400 Arabic characters.

Prefer approximately 700-1200 Arabic characters when the supplied source
contains enough information to support that level of detail.

The article should naturally contain:

1. What happened.

2. Important factual details.

3. Relevant context supported by the source.

4. Aql Crypto analysis.

5. Why the development matters.

6. What remains uncertain.

7. What readers should watch.

Do not add filler merely to increase length.

==================================================
ANALYSIS
==================================================

The analysis must be based only on facts supplied in the source.

Explain:

- why the development matters
- what mechanism is important
- what implications may reasonably follow
- what remains uncertain

Do not add external facts.

Do not provide price predictions.

Do not recommend buying or selling.

==================================================
WHAT TO WATCH
==================================================

Mention realistic developments or indicators directly connected to the story.

Only use information that can reasonably be inferred from the supplied source.

Do not invent future events.

==================================================
LIMITATIONS
==================================================

Explain important limitations in the supplied source.

If the source is incomplete,
say so clearly.

==================================================
TITLE
==================================================

Create a concise Arabic SEO title.

Avoid clickbait.

Do not exaggerate.

==================================================
SUMMARY
==================================================

Create a concise factual Arabic summary.

==================================================
META DESCRIPTION
==================================================

Create an Arabic meta description.

Target length:
50-160 Arabic characters.

==================================================
KEYWORDS
==================================================

Generate exactly 3 to 5 English keywords.

Use only keywords genuinely related to the article.

Use uppercase ticker symbols where appropriate.

==================================================
SENTIMENT
==================================================

Choose exactly one:

Bullish
Bearish
Neutral

Use Neutral when the source does not establish a clear directional tone.

==================================================
IMPACT SCORE
==================================================

Choose an integer from 1 to 10.

1 = limited crypto significance.

10 = potentially major crypto ecosystem significance.

Do not assign a high score simply because the headline is dramatic.

==================================================
CATEGORY
==================================================

Choose exactly one:

Bitcoin
Ethereum
Regulation
DeFi
NFT
Mining
Market
Security
Blockchain

==================================================
LANGUAGE
==================================================

All Arabic fields must use professional Modern Standard Arabic.

Avoid machine-translation style.

Avoid unnecessary English words.

==================================================
PARAGRAPHS
==================================================

Use clear paragraphs separated by blank lines.

Do not use Markdown headings inside content_ar.

==================================================
OUTPUT
==================================================

Return ONLY valid JSON.

Use exactly these fields:

{
    "title_ar": "",
    "content_ar": "",
    "summary_ar": "",
    "meta_description_ar": "",
    "why_it_matters_ar": "",
    "analysis_ar": "",
    "context_ar": "",
    "what_to_watch_ar": "",
    "limitations_ar": "",
    "sentiment": "Neutral",
    "category": "Market",
    "impact_score": 5,
    "keywords": []
}

==================================================
SOURCE
==================================================

Source:
{$news->source}

Original URL:
{$news->url}

Original publication timestamp:
{$news->created_at}

==================================================
ARTICLE TITLE
==================================================

{$title}

==================================================
ARTICLE CONTENT
==================================================

{$content}

==================================================
FINAL INSTRUCTION
==================================================

Produce an original Arabic editorial article.

Minimum content_ar length:
400 characters.

Do not invent facts.

Do not use outside knowledge.

Do not provide financial advice.

Return JSON only.
PROMPT;


        /*
        |--------------------------------------------------------------------------
        | JSON Schema
        |--------------------------------------------------------------------------
        */

        $schema = [

            'type' =>
                'object',

            'properties' => [

                'title_ar' => [
                    'type' =>
                        'string',
                ],

                'content_ar' => [
                    'type' =>
                        'string',
                ],

                'summary_ar' => [
                    'type' =>
                        'string',
                ],

                'meta_description_ar' => [
                    'type' =>
                        'string',
                ],

                'why_it_matters_ar' => [
                    'type' =>
                        'string',
                ],

                'analysis_ar' => [
                    'type' =>
                        'string',
                ],

                'context_ar' => [
                    'type' =>
                        'string',
                ],

                'what_to_watch_ar' => [
                    'type' =>
                        'string',
                ],

                'limitations_ar' => [
                    'type' =>
                        'string',
                ],

                'sentiment' => [

                    'type' =>
                        'string',

                    'enum' => [
                        'Bullish',
                        'Bearish',
                        'Neutral',
                    ],
                ],

                'category' => [

                    'type' =>
                        'string',

                    'enum' => [

                        'Bitcoin',
                        'Ethereum',
                        'Regulation',
                        'DeFi',
                        'NFT',
                        'Mining',
                        'Market',
                        'Security',
                        'Blockchain',
                    ],
                ],

                'impact_score' => [
                    'type' =>
                        'integer',
                ],

                'keywords' => [

                    'type' =>
                        'array',

                    'items' => [
                        'type' =>
                            'string',
                    ],
                ],
            ],

            'required' => [

                'title_ar',
                'content_ar',
                'summary_ar',
                'meta_description_ar',
                'why_it_matters_ar',
                'analysis_ar',
                'context_ar',
                'what_to_watch_ar',
                'limitations_ar',
                'sentiment',
                'category',
                'impact_score',
                'keywords',
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | Payload
        |--------------------------------------------------------------------------
        */

        $payload = [

            'contents' => [

                [

                    'parts' => [

                        [

                            'text' =>
                                $prompt,
                        ],
                    ],
                ],
            ],

            'generationConfig' => [

                'temperature' =>
                    0.35,

                'response_mime_type' =>
                    'application/json',

                'response_schema' =>
                    $schema,
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | API Request
        |--------------------------------------------------------------------------
        */

        for (
            $attempt = 1;
            $attempt <=
            self::MAX_API_RETRIES + 1;
            $attempt++
        ) {

            try {

                $response =
                    Http::timeout(90)
                        ->acceptJson()
                        ->asJson()
                        ->post(
                            $url,
                            $payload
                        );


                /*
                |--------------------------------------------------------------------------
                | Successful Response
                |--------------------------------------------------------------------------
                */

                if (
                    $response->successful()
                ) {

                    $text =
                        data_get(
                            $response->json(),
                            'candidates.0.content.parts.0.text'
                        );


                    if (
                        !is_string($text) ||
                        trim($text) === ''
                    ) {

                        Log::error(
                            'Gemini returned empty response',
                            [
                                'news_id' =>
                                    $news->id,

                                'attempt' =>
                                    $attempt,
                            ]
                        );


                        return null;
                    }


                    $text =
                        trim($text);


                    /*
                    |--------------------------------------------------------------------------
                    | Remove Markdown Fences
                    |--------------------------------------------------------------------------
                    */

                    $text =
                        preg_replace(
                            '/^```(?:json)?\s*/i',
                            '',
                            $text
                        );


                    $text =
                        preg_replace(
                            '/\s*```$/',
                            '',
                            $text
                        );


                    $text =
                        trim(
                            $text
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Decode JSON
                    |--------------------------------------------------------------------------
                    */

                    $data =
                        json_decode(
                            $text,
                            true
                        );


                    if (
                        json_last_error() !==
                        JSON_ERROR_NONE
                    ) {

                        Log::error(
                            'Gemini JSON Error',
                            [
                                'news_id' =>
                                    $news->id,

                                'attempt' =>
                                    $attempt,

                                'error' =>
                                    json_last_error_msg(),
                            ]
                        );


                        return null;
                    }


                    if (
                        !is_array($data)
                    ) {

                        Log::error(
                            'Gemini JSON is not an array',
                            [
                                'news_id' =>
                                    $news->id,
                            ]
                        );


                        return null;
                    }


                    return $data;
                }


                /*
                |--------------------------------------------------------------------------
                | 429 - Quota Exhausted
                |--------------------------------------------------------------------------
                */

                if (
                    $response->status() === 429
                ) {

                    $body =
                        $response->json();


                    $message =
                        data_get(
                            $body,
                            'error.message',
                            'Gemini API quota exceeded.'
                        );


                    $retrySeconds =
                        $this->extractRetrySeconds(
                            $body
                        );


                    Log::warning(
                        'Gemini API quota exceeded',
                        [
                            'news_id' =>
                                $news->id,

                            'model' =>
                                self::GEMINI_MODEL,

                            'attempt' =>
                                $attempt,

                            'retry_seconds' =>
                                $retrySeconds,

                            'message' =>
                                $message,
                        ]
                    );


                    $this->error(
                        '🚫 Gemini API returned HTTP 429.'
                    );


                    $this->warn(
                        'Message: ' .
                        $message
                    );


                    if (
                        $retrySeconds !== null
                    ) {

                        $this->warn(
                            "Suggested retry delay: {$retrySeconds} seconds."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Do not retry automatically here.
                    |--------------------------------------------------------------------------
                    |
                    | Especially for:
                    |
                    | GenerateRequestsPerDayPerModel-FreeTier
                    |
                    */

                    return [

                        '__quota_exceeded' =>
                            true,

                        '__message' =>
                            $message,

                        '__retry_seconds' =>
                            $retrySeconds,
                    ];
                }


                /*
                |--------------------------------------------------------------------------
                | Temporary Server Errors
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $response->status(),
                        [
                            500,
                            502,
                            503,
                            504,
                        ],
                        true
                    )
                ) {

                    Log::warning(
                        'Temporary Gemini API error',
                        [
                            'news_id' =>
                                $news->id,

                            'status' =>
                                $response->status(),

                            'attempt' =>
                                $attempt,

                            'body' =>
                                $response->body(),
                        ]
                    );


                    if (
                        $attempt <=
                        self::MAX_API_RETRIES
                    ) {

                        $delay =
                            self::RETRY_BASE_SECONDS *
                            $attempt;


                        $this->warn(
                            "⚠️ Temporary API error. Retrying in {$delay}s..."
                        );


                        sleep(
                            $delay
                        );


                        continue;
                    }


                    return [

                        '__temporary_failure' =>
                            true,
                    ];
                }


                /*
                |--------------------------------------------------------------------------
                | Other HTTP Errors
                |--------------------------------------------------------------------------
                */

                Log::error(
                    'Gemini API Error',
                    [
                        'news_id' =>
                            $news->id,

                        'status' =>
                            $response->status(),

                        'attempt' =>
                            $attempt,

                        'body' =>
                            $response->body(),
                    ]
                );


                return [

                    '__api_failure' =>
                        true,
                ];


            } catch (\Throwable $e) {

                Log::error(
                    'Gemini Connection Error',
                    [
                        'news_id' =>
                            $news->id,

                        'attempt' =>
                            $attempt,

                        'message' =>
                            $e->getMessage(),
                    ]
                );


                if (
                    $attempt <=
                    self::MAX_API_RETRIES
                ) {

                    $delay =
                        self::RETRY_BASE_SECONDS *
                        $attempt;


                    $this->warn(
                        "⚠️ Connection error. Retrying in {$delay}s..."
                    );


                    sleep(
                        $delay
                    );


                    continue;
                }


                return [

                    '__temporary_failure' =>
                        true,
                ];
            }
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Extract Retry Seconds
    |--------------------------------------------------------------------------
    */

    private function extractRetrySeconds(
        array $body
    ): ?int {

        $details =
            data_get(
                $body,
                'error.details',
                []
            );


        if (
            !is_array($details)
        ) {

            return null;
        }


        foreach (
            $details as $detail
        ) {

            if (
                !is_array($detail)
            ) {

                continue;
            }


            $type =
                $detail['@type']
                ?? null;


            if (
                $type !==
                'type.googleapis.com/google.rpc.RetryInfo'
            ) {

                continue;
            }


            $retryDelay =
                $detail['retryDelay']
                ?? null;


            if (
                !is_string($retryDelay)
            ) {

                return null;
            }


            preg_match(
                '/(\d+(?:\.\d+)?)s/',
                $retryDelay,
                $matches
            );


            if (
                isset(
                    $matches[1]
                )
            ) {

                return (int) ceil(
                    (float) $matches[1]
                );
            }
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate AI Result
    |--------------------------------------------------------------------------
    */

    private function isValidResult(
        ?array $result
    ): bool {

        if (
            !is_array($result)
        ) {

            return false;
        }


        if (
            isset(
                $result['__quota_exceeded']
            )
            ||
            isset(
                $result['__temporary_failure']
            )
            ||
            isset(
                $result['__api_failure']
            )
        ) {

            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Required Fields
        |--------------------------------------------------------------------------
        */

        $required = [

            'title_ar',
            'content_ar',
            'summary_ar',
            'meta_description_ar',
            'why_it_matters_ar',
            'analysis_ar',
            'context_ar',
            'what_to_watch_ar',
            'limitations_ar',
            'sentiment',
            'category',
            'impact_score',
            'keywords',
        ];


        foreach (
            $required as $field
        ) {

            if (
                !array_key_exists(
                    $field,
                    $result
                )
            ) {

                Log::warning(
                    'AI validation failed',
                    [
                        'reason' =>
                            'missing required field',

                        'field' =>
                            $field,
                    ]
                );


                return false;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | String Fields
        |--------------------------------------------------------------------------
        */

        $stringFields = [

            'title_ar',
            'content_ar',
            'summary_ar',
            'meta_description_ar',
            'why_it_matters_ar',
            'analysis_ar',
            'context_ar',
            'what_to_watch_ar',
            'limitations_ar',
        ];


        foreach (
            $stringFields
            as $field
        ) {

            if (
                !is_string(
                    $result[$field]
                )
            ) {

                Log::warning(
                    'AI validation failed',
                    [
                        'reason' =>
                            'field is not string',

                        'field' =>
                            $field,

                        'type' =>
                            gettype(
                                $result[$field]
                            ),
                    ]
                );


                return false;
            }


            if (
                trim(
                    $result[$field]
                ) === ''
            ) {

                Log::warning(
                    'AI validation failed',
                    [
                        'reason' =>
                            'empty string field',

                        'field' =>
                            $field,
                    ]
                );


                return false;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate Lengths
        |--------------------------------------------------------------------------
        */

        $lengths = [

            'title_ar' =>
                mb_strlen(
                    trim(
                        $result['title_ar']
                    )
                ),

            'content_ar' =>
                mb_strlen(
                    trim(
                        $result['content_ar']
                    )
                ),

            'summary_ar' =>
                mb_strlen(
                    trim(
                        $result['summary_ar']
                    )
                ),

            'meta_description_ar' =>
                mb_strlen(
                    trim(
                        $result['meta_description_ar']
                    )
                ),

            'why_it_matters_ar' =>
                mb_strlen(
                    trim(
                        $result['why_it_matters_ar']
                    )
                ),

            'analysis_ar' =>
                mb_strlen(
                    trim(
                        $result['analysis_ar']
                    )
                ),

            'context_ar' =>
                mb_strlen(
                    trim(
                        $result['context_ar']
                    )
                ),

            'what_to_watch_ar' =>
                mb_strlen(
                    trim(
                        $result['what_to_watch_ar']
                    )
                ),

            'limitations_ar' =>
                mb_strlen(
                    trim(
                        $result['limitations_ar']
                    )
                ),
        ];


        Log::info(
            'AI validation field lengths',
            [
                'lengths' =>
                    $lengths,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Title
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['title_ar'] < 15 ||
            $lengths['title_ar'] > 180
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'invalid title_ar length',

                    'actual' =>
                        $lengths['title_ar'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Content
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['content_ar'] <
            self::MIN_CONTENT_AR_LENGTH
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'content_ar too short',

                    'required_minimum' =>
                        self::MIN_CONTENT_AR_LENGTH,

                    'actual_length' =>
                        $lengths['content_ar'],
                ]
            );


            return false;
        }


        if (
            $lengths['content_ar'] >
            self::MAX_CONTENT_AR_LENGTH
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'content_ar too long',

                    'maximum' =>
                        self::MAX_CONTENT_AR_LENGTH,

                    'actual_length' =>
                        $lengths['content_ar'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['summary_ar'] < 50
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'summary_ar too short',

                    'required_minimum' =>
                        50,

                    'actual_length' =>
                        $lengths['summary_ar'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Why It Matters
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['why_it_matters_ar'] < 100
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'why_it_matters_ar too short',

                    'required_minimum' =>
                        100,

                    'actual_length' =>
                        $lengths['why_it_matters_ar'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Analysis
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['analysis_ar'] < 180
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'analysis_ar too short',

                    'required_minimum' =>
                        180,

                    'actual_length' =>
                        $lengths['analysis_ar'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Context
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['context_ar'] < 100
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'context_ar too short',

                    'required_minimum' =>
                        100,

                    'actual_length' =>
                        $lengths['context_ar'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | What To Watch
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['what_to_watch_ar'] < 80
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'what_to_watch_ar too short',

                    'required_minimum' =>
                        80,

                    'actual_length' =>
                        $lengths['what_to_watch_ar'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Limitations
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['limitations_ar'] < 50
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'limitations_ar too short',

                    'required_minimum' =>
                        50,

                    'actual_length' =>
                        $lengths['limitations_ar'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Meta Description
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['meta_description_ar'] < 50
            ||
            $lengths['meta_description_ar'] > 180
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'invalid meta_description_ar length',

                    'minimum' =>
                        50,

                    'maximum' =>
                        180,

                    'actual_length' =>
                        $lengths['meta_description_ar'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Sentiment
        |--------------------------------------------------------------------------
        */

        $allowedSentiments = [

            'Bullish',
            'Bearish',
            'Neutral',
        ];


        if (
            !in_array(
                $result['sentiment'],
                $allowedSentiments,
                true
            )
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'invalid sentiment',

                    'value' =>
                        $result['sentiment'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        $allowedCategories = [

            'Bitcoin',
            'Ethereum',
            'Regulation',
            'DeFi',
            'NFT',
            'Mining',
            'Market',
            'Security',
            'Blockchain',
        ];


        if (
            !in_array(
                $result['category'],
                $allowedCategories,
                true
            )
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'invalid category',

                    'value' =>
                        $result['category'],
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Impact Score
        |--------------------------------------------------------------------------
        */

        if (
            !is_int(
                $result['impact_score']
            )
            &&
            !is_numeric(
                $result['impact_score']
            )
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'impact_score is not numeric',

                    'value' =>
                        $result['impact_score'],
                ]
            );


            return false;
        }


        $impactScore =
            (int)
            $result['impact_score'];


        if (
            $impactScore < 1 ||
            $impactScore > 10
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'impact_score outside allowed range',

                    'value' =>
                        $impactScore,
                ]
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Keywords
        |--------------------------------------------------------------------------
        */

        if (
            !is_array(
                $result['keywords']
            )
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'keywords is not an array',
                ]
            );


            return false;
        }


        $keywordCount =
            count(
                $result['keywords']
            );


        if (
            $keywordCount < 3 ||
            $keywordCount > 5
        ) {

            Log::warning(
                'AI validation failed',
                [
                    'reason' =>
                        'invalid keyword count',

                    'actual' =>
                        $keywordCount,
                ]
            );


            return false;
        }


        foreach (
            $result['keywords']
            as $keyword
        ) {

            if (
                !is_string(
                    $keyword
                )
                ||
                trim(
                    $keyword
                ) === ''
            ) {

                Log::warning(
                    'AI validation failed',
                    [
                        'reason' =>
                            'invalid keyword',
                    ]
                );


                return false;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validation Passed
        |--------------------------------------------------------------------------
        */

        Log::info(
            'AI validation passed',
            [
                'lengths' =>
                    $lengths,

                'sentiment' =>
                    $result['sentiment'],

                'category' =>
                    $result['category'],

                'impact_score' =>
                    $impactScore,

                'keyword_count' =>
                    $keywordCount,
            ]
        );


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Final Content Validation
    |--------------------------------------------------------------------------
    */

    private function validateFinalContent(
        string $titleAr,
        string $contentAr,
        string $summaryAr,
        string $whyItMattersAr,
        string $analysisAr,
        string $contextAr,
        string $whatToWatchAr,
        string $limitationsAr,
        string $metaDescriptionAr
    ): bool {

        if (
            mb_strlen(
                trim($titleAr)
            ) < 15
        ) {

            return false;
        }


        if (
            mb_strlen(
                trim($titleAr)
            ) > 180
        ) {

            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | IMPORTANT:
        |
        | Same 400 character rule used everywhere.
        |
        */

        if (
            mb_strlen(
                trim($contentAr)
            ) <
            self::MIN_CONTENT_AR_LENGTH
        ) {

            return false;
        }


        if (
            mb_strlen(
                trim($contentAr)
            ) >
            self::MAX_CONTENT_AR_LENGTH
        ) {

            return false;
        }


        if (
            mb_strlen(
                trim($summaryAr)
            ) < 50
        ) {

            return false;
        }


        if (
            mb_strlen(
                trim($whyItMattersAr)
            ) < 100
        ) {

            return false;
        }


        if (
            mb_strlen(
                trim($analysisAr)
            ) < 180
        ) {

            return false;
        }


        if (
            mb_strlen(
                trim($contextAr)
            ) < 100
        ) {

            return false;
        }


        if (
            mb_strlen(
                trim($whatToWatchAr)
            ) < 80
        ) {

            return false;
        }


        if (
            mb_strlen(
                trim($limitationsAr)
            ) < 50
        ) {

            return false;
        }


        if (
            mb_strlen(
                trim($metaDescriptionAr)
            ) < 50
            ||
            mb_strlen(
                trim($metaDescriptionAr)
            ) > 180
        ) {

            return false;
        }


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Build Slug
    |--------------------------------------------------------------------------
    */

    private function buildSlug(
        string $title,
        int|string $id
    ): string {

        $slug =
            Str::slug(
                $title
            );


        if (
            $slug === ''
        ) {

            $slug =
                'news';
        }


        return
            $slug .
            '-' .
            $id;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Keywords
    |--------------------------------------------------------------------------
    */

    private function normalizeKeywords(
        array $keywords
    ): array {

        return collect(
            $keywords
        )
            ->map(
                fn ($keyword) =>
                    trim(
                        (string) $keyword
                    )
            )
            ->filter()
            ->map(
                fn ($keyword) =>
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $keyword
                    )
            )
            ->unique(
                fn ($keyword) =>
                    mb_strtolower(
                        $keyword
                    )
            )
            ->take(5)
            ->values()
            ->toArray();
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Sentiment
    |--------------------------------------------------------------------------
    */

    private function normalizeSentiment(
        mixed $sentiment
    ): string {

        $allowed = [

            'Bullish',
            'Bearish',
            'Neutral',
        ];


        if (
            in_array(
                $sentiment,
                $allowed,
                true
            )
        ) {

            return $sentiment;
        }


        return 'Neutral';
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Category
    |--------------------------------------------------------------------------
    */

    private function normalizeCategory(
        mixed $category
    ): string {

        $allowed = [

            'Bitcoin',
            'Ethereum',
            'Regulation',
            'DeFi',
            'NFT',
            'Mining',
            'Market',
            'Security',
            'Blockchain',
        ];


        if (
            in_array(
                $category,
                $allowed,
                true
            )
        ) {

            return $category;
        }


        return 'Market';
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Impact Score
    |--------------------------------------------------------------------------
    */

    private function normalizeImpactScore(
        mixed $score
    ): int {

        if (
            !is_numeric(
                $score
            )
        ) {

            return 5;
        }


        return max(
            1,
            min(
                10,
                (int) $score
            )
        );
    }
}