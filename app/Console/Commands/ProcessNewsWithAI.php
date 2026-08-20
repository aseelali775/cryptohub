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
                            {--limit=3 : Number of articles to process}';

    protected $description =
        'Analyze unprocessed crypto news and generate original Arabic editorial analysis using Gemini AI.';

    /*
    |--------------------------------------------------------------------------
    | Gemini Configuration
    |--------------------------------------------------------------------------
    */

    private const GEMINI_MODEL = 'gemini-3.6-flash';

    /*
    |--------------------------------------------------------------------------
    | Processing Configuration
    |--------------------------------------------------------------------------
    */

    private const DEFAULT_BATCH_LIMIT = 3;

    /*
    |--------------------------------------------------------------------------
    | Minimum source content required before sending to AI
    |--------------------------------------------------------------------------
    |
    | Articles with less than 400 characters are considered too weak
    | for safe editorial generation and will be skipped.
    |
    */

    private const MIN_SOURCE_LENGTH = 140;

    /*
    |--------------------------------------------------------------------------
    | Minimum generated Arabic article length
    |--------------------------------------------------------------------------
    */

    private const MIN_ARTICLE_LENGTH = 300;

    /*
    |--------------------------------------------------------------------------
    | Maximum generated Arabic article length
    |--------------------------------------------------------------------------
    */

    private const MAX_ARTICLE_LENGTH = 12000;

    /*
    |--------------------------------------------------------------------------
    | Maximum source content sent to Gemini
    |--------------------------------------------------------------------------
    */

    private const MAX_SOURCE_LENGTH = 12000;

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    */

    private const RATE_LIMIT_SECONDS = 20;

    /*
    |--------------------------------------------------------------------------
    | Temporary API Retry Configuration
    |--------------------------------------------------------------------------
    */

    private const MAX_API_RETRIES = 2;

    private const RETRY_BASE_SECONDS = 5;

    /*
    |--------------------------------------------------------------------------
    | Handle
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
        | Resolve limit
        |--------------------------------------------------------------------------
        */

        $limit = (int) $this->option('limit');

        if ($limit < 1) {
            $limit = self::DEFAULT_BATCH_LIMIT;
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

        $this->info(
            'Minimum source: ' . self::MIN_SOURCE_LENGTH . ' chars'
        );

        $this->info(
            'Minimum article: ' . self::MIN_ARTICLE_LENGTH . ' chars'
        );

        /*
        |--------------------------------------------------------------------------
        | Get unprocessed news
        |--------------------------------------------------------------------------
        */

        $newsList = News::query()
            ->where('ai_processed', false)
            ->whereNotNull('content_en')
            ->where('content_en', '!=', '')
            ->latest()
            ->limit($limit)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Nothing to process
        |--------------------------------------------------------------------------
        */

        if ($newsList->isEmpty()) {

            $this->newLine();

            $this->info(
                '✅ No unprocessed articles found.'
            );

            return self::SUCCESS;
        }

        $this->newLine();

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

        /*
        |--------------------------------------------------------------------------
        | Process articles
        |--------------------------------------------------------------------------
        */

        foreach ($newsList as $news) {

            $this->newLine();

            $this->info(
                "Processing ID {$news->id}: {$news->title_en}"
            );

            try {

                /*
                |--------------------------------------------------------------------------
                | Prepare source material
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

                $contentLength = mb_strlen(
                    $content
                );

                /*
                |--------------------------------------------------------------------------
                | Validate title
                |--------------------------------------------------------------------------
                */

                if (mb_strlen($title) < 5) {

                    $this->warn(
                        "⚠️ Article {$news->id} has an invalid title."
                    );

                    Log::warning(
                        'AI skipped article because title is too short',
                        [
                            'news_id' => $news->id,
                            'title_length' => mb_strlen($title),
                        ]
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validate source content
                |--------------------------------------------------------------------------
                */

                if ($contentLength < self::MIN_SOURCE_LENGTH) {

                    $this->warn(
                        "⚠️ Article {$news->id} source content is too short for AI."
                    );

                    $this->line(
                        "Content length: {$contentLength}"
                    );

                    $this->line(
                        'Minimum required: ' .
                        self::MIN_SOURCE_LENGTH
                    );

                    $this->line(
                        'Recommended action: REFETCH CONTENT.'
                    );

                    Log::warning(
                        'AI skipped article because original content is too short',
                        [
                            'news_id' => $news->id,
                            'content_length' => $contentLength,
                            'minimum_required' => self::MIN_SOURCE_LENGTH,
                        ]
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Analyze with Gemini
                |--------------------------------------------------------------------------
                */

                $result = $this->analyzeWithGemini(
                    $news,
                    $title,
                    $content,
                    $apiKey
                );

                /*
                |--------------------------------------------------------------------------
                | API quota failure
                |--------------------------------------------------------------------------
                */

                if (
                    is_array($result) &&
                    ($result['__quota_exceeded'] ?? false)
                ) {

                    $this->error(
                        '🛑 Gemini API quota/rate limit reached.'
                    );

                    $this->warn(
                        'Processing cycle stopped.'
                    );

                    if (
                        !empty(
                            $result['__retry_seconds']
                        )
                    ) {

                        $this->line(
                            'Suggested retry delay: ' .
                            $result['__retry_seconds'] .
                            ' seconds.'
                        );
                    }

                    Log::warning(
                        'AI processing stopped because Gemini quota was exceeded',
                        [
                            'news_id' => $news->id,
                            'model' => self::GEMINI_MODEL,
                            'message' => $result['__message'] ?? null,
                            'retry_seconds' =>
                                $result['__retry_seconds'] ?? null,
                        ]
                    );

                    $apiFailed++;

                    break;
                }

                /*
                |--------------------------------------------------------------------------
                | Temporary API failure
                |--------------------------------------------------------------------------
                */

                if (
                    is_array($result) &&
                    ($result['__temporary_failure'] ?? false)
                ) {

                    $this->error(
                        "❌ Temporary Gemini API failure for ID {$news->id}"
                    );

                    $apiFailed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validate AI result structure
                |--------------------------------------------------------------------------
                */

                if (
                    !$this->isValidResult(
                        $result
                    )
                ) {

                    $this->error(
                        "❌ AI result validation failed for ID {$news->id}"
                    );

                    Log::warning(
                        'AI result failed validation',
                        [
                            'news_id' => $news->id,
                            'result' => $result,
                        ]
                    );

                    $validationFailed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Build slug
                |--------------------------------------------------------------------------
                */

                $slug = $this->buildSlug(
                    $title,
                    $news->id
                );

                /*
                |--------------------------------------------------------------------------
                | Normalize keywords
                |--------------------------------------------------------------------------
                */

                $keywords = $this->normalizeKeywords(
                    $result['keywords']
                );

                if (
                    count($keywords) < 3 ||
                    count($keywords) > 5
                ) {

                    $this->error(
                        "❌ Invalid keywords for ID {$news->id}"
                    );

                    Log::warning(
                        'AI keywords validation failed',
                        [
                            'news_id' => $news->id,
                            'keywords' =>
                                $result['keywords'] ?? null,
                        ]
                    );

                    $validationFailed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Normalize sentiment
                |--------------------------------------------------------------------------
                */

                $sentiment = $this->normalizeSentiment(
                    $result['sentiment'] ?? null
                );

                /*
                |--------------------------------------------------------------------------
                | Normalize category
                |--------------------------------------------------------------------------
                */

                $category = $this->normalizeCategory(
                    $result['category'] ?? null
                );

                /*
                |--------------------------------------------------------------------------
                | Normalize impact score
                |--------------------------------------------------------------------------
                */

                $impactScore = $this->normalizeImpactScore(
                    $result['impact_score'] ?? null
                );

                /*
                |--------------------------------------------------------------------------
                | Prepare Arabic fields
                |--------------------------------------------------------------------------
                */

                $titleAr = trim(
                    (string) $result['title_ar']
                );

                $contentAr = trim(
                    (string) $result['content_ar']
                );

                $summaryAr = trim(
                    (string) $result['summary_ar']
                );

                $metaDescriptionAr = trim(
                    (string) $result['meta_description_ar']
                );

                $whyItMattersAr = trim(
                    (string) $result['why_it_matters_ar']
                );

                $analysisAr = trim(
                    (string) $result['analysis_ar']
                );

                $contextAr = trim(
                    (string) $result['context_ar']
                );

                $whatToWatchAr = trim(
                    (string) $result['what_to_watch_ar']
                );

                $limitationsAr = trim(
                    (string) $result['limitations_ar']
                );

                /*
                |--------------------------------------------------------------------------
                | Final content validation
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

                    $this->error(
                        "❌ Final content validation failed for ID {$news->id}"
                    );

                    Log::warning(
                        'AI final content validation failed',
                        [
                            'news_id' => $news->id,

                            'lengths' => [
                                'title_ar' =>
                                    mb_strlen($titleAr),

                                'content_ar' =>
                                    mb_strlen($contentAr),

                                'summary_ar' =>
                                    mb_strlen($summaryAr),

                                'why_it_matters_ar' =>
                                    mb_strlen($whyItMattersAr),

                                'analysis_ar' =>
                                    mb_strlen($analysisAr),

                                'context_ar' =>
                                    mb_strlen($contextAr),

                                'what_to_watch_ar' =>
                                    mb_strlen($whatToWatchAr),

                                'limitations_ar' =>
                                    mb_strlen($limitationsAr),

                                'meta_description_ar' =>
                                    mb_strlen($metaDescriptionAr),
                            ],
                        ]
                    );

                    $validationFailed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Save article
                |--------------------------------------------------------------------------
                |
                | ai_processed becomes TRUE only after all validations pass.
                |
                */

                $news->update([

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

                    'meta_description_ar' =>
                        $metaDescriptionAr,

                    'keywords' =>
                        $keywords,

                    'sentiment' =>
                        $sentiment,

                    'category' =>
                        $category,

                    'impact_score' =>
                        $impactScore,

                    'ai_processed' =>
                        true,
                ]);

                $processed++;

                /*
                |--------------------------------------------------------------------------
                | Success output
                |--------------------------------------------------------------------------
                */

                $this->info(
                    "✅ AI editorial analysis saved for ID {$news->id}"
                );

                $this->line(
                    'Arabic article length: ' .
                    mb_strlen($contentAr) .
                    ' chars'
                );

                $this->line(
                    'Category: ' .
                    $category
                );

                $this->line(
                    'Sentiment: ' .
                    $sentiment
                );

                $this->line(
                    'Impact score: ' .
                    $impactScore .
                    '/10'
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
            | Rate limiting
            |--------------------------------------------------------------------------
            */

            if (
                $news->id !== $newsList->last()->id
            ) {

                sleep(
                    self::RATE_LIMIT_SECONDS
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Clear AI caches
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
        | Final report
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            '🧹 AI Market cache cleared.'
        );

        $this->newLine();

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

        $this->newLine();

        $this->info(
            '🚀 Aql Crypto AI Editorial Cycle Completed.'
        );

        /*
        |--------------------------------------------------------------------------
        | Exit status
        |--------------------------------------------------------------------------
        */

        return (
            $failed > 0 &&
            $processed === 0
        )
            ? self::FAILURE
            : self::SUCCESS;
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

        $prompt = <<<'PROMPT'
You are the senior editorial analyst for Aql Crypto, an Arabic cryptocurrency news and market-analysis platform.

Your task is to transform the supplied factual source material into an ORIGINAL Arabic editorial news analysis.

This is NOT a literal translation.

This is NOT a sentence-by-sentence rewrite.

The final article must provide genuine editorial value by explaining:

- what happened
- important details
- relevant context
- why the event matters
- reasonable implications
- what readers should monitor
- limitations and uncertainty

==================================================
SOURCE FIDELITY
==================================================

The supplied source material is the factual foundation.

Use ONLY information contained in the supplied material.

Do NOT use:

- general knowledge
- training knowledge
- current market knowledge
- outside websites
- outside statistics
- outside events

Never invent:

- people
- companies
- partnerships
- investments
- statistics
- prices
- trading volumes
- market capitalization
- blockchain data
- regulations
- quotes
- dates
- sources
- citations

If a fact is not present in the source, do not present it as fact.

==================================================
ORIGINALITY
==================================================

Write independently in professional Modern Standard Arabic.

Do not copy sentences.

Do not preserve the source paragraph structure.

Do not mechanically translate.

Reorganize information naturally.

Explain why the facts matter.

==================================================
FACT VS ANALYSIS
==================================================

Clearly distinguish facts from editorial interpretation.

When making an inference, use cautious language such as:

قد يشير ذلك إلى

قد يعكس

من المحتمل أن

يمكن أن يعني

قد يؤدي إلى

يعتمد التأثير على

لا يمكن الجزم بأن

Never present speculation as confirmed fact.

==================================================
ARTICLE STRUCTURE
==================================================

The article should naturally explain:

1. WHAT HAPPENED?

Explain the central event immediately.

2. IMPORTANT DETAILS

Explain important facts, numbers, organizations and technical details.

3. CONTEXT

Explain only context supported by the supplied material.

4. AQL CRYPTO ANALYSIS

Provide genuine editorial analysis based ONLY on supplied facts.

Explain:

- why the event matters
- what mechanism is important
- possible implications
- what remains uncertain

Do not invent market data.

Do not make guaranteed predictions.

Do not recommend buying or selling.

5. WHAT TO WATCH

Identify realistic developments or indicators directly connected to the story.

6. LIMITATIONS

Explain missing information or uncertainty.

==================================================
FINANCIAL SAFETY
==================================================

This is journalism, not financial advice.

Never:

- recommend buying
- recommend selling
- guarantee profit
- guarantee loss
- predict exact future prices
- claim an asset will definitely rise
- claim an asset will definitely fall

==================================================
TITLE
==================================================

Create a concise Arabic SEO title.

Avoid clickbait and exaggeration.

==================================================
SUMMARY
==================================================

Create a concise factual Arabic summary.

==================================================
META DESCRIPTION
==================================================

Create an Arabic SEO meta description.

Target approximately 120-160 Arabic characters.

Maximum 180 characters.

==================================================
KEYWORDS
==================================================

Generate exactly 3 to 5 English keywords.

Use Title Case for normal words.

Use uppercase ticker symbols.

Only use keywords genuinely related to the article.

==================================================
SENTIMENT
==================================================

Choose exactly one:

Bullish
Bearish
Neutral

Use Neutral when the supplied information does not support a clear direction.

==================================================
IMPACT SCORE
==================================================

Choose an integer from 1 to 10.

1 = very limited crypto relevance.

10 = potentially major crypto ecosystem significance.

Do not assign a high score simply because the headline sounds dramatic.

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

==================================================
PARAGRAPHS
==================================================

Use clear paragraphs.

Separate paragraphs using two newline characters.

Do not use Markdown headings inside content_ar.

==================================================
LENGTH REQUIREMENTS
==================================================

content_ar MUST contain at least 400 Arabic characters.

content_ar MUST NOT exceed 12000 characters.

summary_ar MUST contain at least 50 characters.

why_it_matters_ar MUST contain at least 100 characters.

analysis_ar MUST contain at least 180 characters.

context_ar MUST contain at least 100 characters.

what_to_watch_ar MUST contain at least 80 characters.

limitations_ar MUST contain at least 50 characters.

meta_description_ar MUST contain between 50 and 180 characters.

Do not fill the article with repetitive sentences merely to reach the minimum length.

==================================================
JSON OUTPUT
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
SOURCE INFORMATION
==================================================

Source:
{{SOURCE}}

Original URL:
{{URL}}

Original publication timestamp:
{{DATE}}

==================================================
ARTICLE TITLE
==================================================

{{TITLE}}

==================================================
ARTICLE CONTENT
==================================================

{{CONTENT}}

==================================================
FINAL INSTRUCTION
==================================================

Produce an original Arabic editorial analysis based strictly on the supplied material.

Do not copy.

Do not perform literal translation.

Do not invent facts.

Do not fabricate citations.

Do not provide financial advice.

Do not make unsupported predictions.

Return JSON only.
PROMPT;

        /*
        |--------------------------------------------------------------------------
        | Replace variables
        |--------------------------------------------------------------------------
        */

        $prompt = str_replace(
            [
                '{{SOURCE}}',
                '{{URL}}',
                '{{DATE}}',
                '{{TITLE}}',
                '{{CONTENT}}',
            ],
            [
                (string) (
                    $news->source ??
                    'Unknown'
                ),

                (string) (
                    $news->url ??
                    ''
                ),

                (string) (
                    $news->created_at ??
                    ''
                ),

                $title,

                $content,
            ],
            $prompt
        );

        /*
        |--------------------------------------------------------------------------
        | Structured JSON Schema
        |--------------------------------------------------------------------------
        */

        $schema = [

            'type' =>
                'object',

            'properties' => [

                'title_ar' => [
                    'type' => 'string',
                ],

                'content_ar' => [
                    'type' => 'string',
                ],

                'summary_ar' => [
                    'type' => 'string',
                ],

                'meta_description_ar' => [
                    'type' => 'string',
                ],

                'why_it_matters_ar' => [
                    'type' => 'string',
                ],

                'analysis_ar' => [
                    'type' => 'string',
                ],

                'context_ar' => [
                    'type' => 'string',
                ],

                'what_to_watch_ar' => [
                    'type' => 'string',
                ],

                'limitations_ar' => [
                    'type' => 'string',
                ],

                'sentiment' => [
                    'type' => 'string',
                    'enum' => [
                        'Bullish',
                        'Bearish',
                        'Neutral',
                    ],
                ],

                'category' => [
                    'type' => 'string',
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
                    'type' => 'integer',
                ],

                'keywords' => [
                    'type' => 'array',

                    'items' => [
                        'type' => 'string',
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
        | Gemini Payload
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
            $attempt <= self::MAX_API_RETRIES + 1;
            $attempt++
        ) {

            try {

                $response = Http::timeout(90)
                    ->acceptJson()
                    ->asJson()
                    ->post(
                        $url,
                        $payload
                    );

                /*
                |--------------------------------------------------------------------------
                | Successful response
                |--------------------------------------------------------------------------
                */

                if (
                    $response->successful()
                ) {

                    $text = data_get(
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

                    $text = trim(
                        $text
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Remove Markdown fences
                    |--------------------------------------------------------------------------
                    */

                    $text = preg_replace(
                        '/^```(?:json)?\s*/i',
                        '',
                        $text
                    );

                    $text = preg_replace(
                        '/\s*```$/',
                        '',
                        $text
                    );

                    $text = trim(
                        $text
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Decode JSON
                    |--------------------------------------------------------------------------
                    */

                    $data = json_decode(
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

                                'response' =>
                                    $text,
                            ]
                        );

                        return null;
                    }

                    return is_array($data)
                        ? $data
                        : null;
                }

                /*
                |--------------------------------------------------------------------------
                | 429 Quota / Rate Limit
                |--------------------------------------------------------------------------
                */

                if (
                    $response->status() === 429
                ) {

                    $body =
                        $response->json();

                    $message = data_get(
                        $body,
                        'error.message',
                        'Gemini API quota exceeded.'
                    );

                    $retrySeconds = null;

                    $details = data_get(
                        $body,
                        'error.details',
                        []
                    );

                    if (
                        is_array($details)
                    ) {

                        foreach (
                            $details as $detail
                        ) {

                            if (
                                isset(
                                    $detail['@type']
                                ) &&
                                $detail['@type'] ===
                                'type.googleapis.com/google.rpc.RetryInfo'
                            ) {

                                $retryDelay =
                                    $detail['retryDelay'] ??
                                    null;

                                if (
                                    is_string(
                                        $retryDelay
                                    )
                                ) {

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

                                        $retrySeconds =
                                            (int) ceil(
                                                (float) $matches[1]
                                            );
                                    }
                                }

                                break;
                            }
                        }
                    }

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

                            'body' =>
                                $response->body(),
                        ]
                    );

                    $this->error(
                        '🚫 Gemini API returned HTTP 429.'
                    );

                    $this->warn(
                        'Message: ' .
                        $message
                    );

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
                | Temporary server errors
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
                | Other API errors
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

                return null;

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
    | Validate AI Result
    |--------------------------------------------------------------------------
    */

    private function isValidResult(
        ?array $result
    ): bool {

        if (
            !is_array($result)
        ) {

            Log::warning(
                'AI validation failed: result is not an array.'
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Internal control responses
        |--------------------------------------------------------------------------
        */

        if (
            isset($result['__quota_exceeded']) ||
            isset($result['__temporary_failure'])
        ) {

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Required fields
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
                    'AI validation failed: missing field',
                    [
                        'field' =>
                            $field,
                    ]
                );

                return false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | String fields
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
            $stringFields as $field
        ) {

            if (
                !is_string(
                    $result[$field]
                )
            ) {

                return false;
            }

            if (
                trim(
                    $result[$field]
                ) === ''
            ) {

                return false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Length validation
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
                'AI validation failed: invalid title length',
                [
                    'length' =>
                        $lengths['title_ar'],
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Main article
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['content_ar'] <
            self::MIN_ARTICLE_LENGTH
        ) {

            Log::warning(
                'AI validation failed: content_ar too short',
                [
                    'minimum' =>
                        self::MIN_ARTICLE_LENGTH,

                    'actual' =>
                        $lengths['content_ar'],
                ]
            );

            return false;
        }

        if (
            $lengths['content_ar'] >
            self::MAX_ARTICLE_LENGTH
        ) {

            Log::warning(
                'AI validation failed: content_ar too long',
                [
                    'maximum' =>
                        self::MAX_ARTICLE_LENGTH,

                    'actual' =>
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

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Why it matters
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['why_it_matters_ar'] < 100
        ) {

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

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | What to watch
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['what_to_watch_ar'] < 80
        ) {

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

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Meta description
        |--------------------------------------------------------------------------
        */

        if (
            $lengths['meta_description_ar'] < 50 ||
            $lengths['meta_description_ar'] > 180
        ) {

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

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Impact score
        |--------------------------------------------------------------------------
        */

        if (
            !is_int(
                $result['impact_score']
            ) &&
            !is_numeric(
                $result['impact_score']
            )
        ) {

            return false;
        }

        $impactScore =
            (int) $result['impact_score'];

        if (
            $impactScore < 1 ||
            $impactScore > 10
        ) {

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

            return false;
        }

        foreach (
            $result['keywords'] as $keyword
        ) {

            if (
                !is_string(
                    $keyword
                ) ||
                trim(
                    $keyword
                ) === ''
            ) {

                return false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Success
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
            mb_strlen($titleAr) < 15 ||
            mb_strlen($titleAr) > 180
        ) {

            return false;
        }

        if (
            mb_strlen($contentAr) <
            self::MIN_ARTICLE_LENGTH
        ) {

            return false;
        }

        if (
            mb_strlen($contentAr) >
            self::MAX_ARTICLE_LENGTH
        ) {

            return false;
        }

        if (
            mb_strlen($summaryAr) < 50
        ) {

            return false;
        }

        if (
            mb_strlen($whyItMattersAr) < 100
        ) {

            return false;
        }

        if (
            mb_strlen($analysisAr) < 180
        ) {

            return false;
        }

        if (
            mb_strlen($contextAr) < 100
        ) {

            return false;
        }

        if (
            mb_strlen($whatToWatchAr) < 80
        ) {

            return false;
        }

        if (
            mb_strlen($limitationsAr) < 50
        ) {

            return false;
        }

        if (
            mb_strlen($metaDescriptionAr) < 50 ||
            mb_strlen($metaDescriptionAr) > 180
        ) {

            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Build Stable Slug
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

        return in_array(
            $sentiment,
            [
                'Bullish',
                'Bearish',
                'Neutral',
            ],
            true
        )
            ? $sentiment
            : 'Neutral';
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Category
    |--------------------------------------------------------------------------
    */

    private function normalizeCategory(
        mixed $category
    ): string {

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

        return in_array(
            $category,
            $allowedCategories,
            true
        )
            ? $category
            : 'Market';
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