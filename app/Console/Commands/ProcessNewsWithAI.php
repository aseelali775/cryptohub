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
                            {--limit=8 : Number of articles to process}';

    protected $description =
        'Analyze unprocessed crypto news and generate original Arabic editorial analysis using Gemini AI.';

    private const GEMINI_MODEL = 'gemini-3.6-flash';

    private const DEFAULT_BATCH_LIMIT = 10;

    private const MIN_SOURCE_LENGTH = 140;

    private const MIN_ARTICLE_LENGTH = 300;

    private const MAX_ARTICLE_LENGTH = 12000;

    private const MAX_SOURCE_LENGTH = 12000;

    /*
     * Delay between successfully completed article attempts.
     * This is intentionally kept at 20 seconds because the command
     * processes one article at a time.
     */
    private const RATE_LIMIT_SECONDS = 20;

    /*
     * Number of retries AFTER the first API attempt.
     *
     * Total attempts = MAX_API_RETRIES + 1
     */
    private const MAX_API_RETRIES = 3;

    /*
     * Base delay for exponential backoff.
     *
     * Attempt 1 retry: ~5 sec
     * Attempt 2 retry: ~10 sec
     * Attempt 3 retry: ~20 sec
     *
     * Jitter is added to avoid synchronized retries.
     */
    private const RETRY_BASE_SECONDS = 5;

    /*
     * Maximum retry delay.
     */
    private const MAX_RETRY_SECONDS = 60;

    /*
     * HTTP timeout for Gemini requests.
     */
    private const HTTP_TIMEOUT_SECONDS = 90;

    public function handle(): int
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            $this->error('❌ GEMINI_API_KEY is missing.');

            Log::error('Gemini API Key missing.');

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');

        if ($limit < 1) {
            $limit = self::DEFAULT_BATCH_LIMIT;
        }

        $this->newLine();

        $this->info('==============================================');
        $this->info('        AQL CRYPTO AI PROCESSING');
        $this->info('==============================================');

        $this->info('Model: ' . self::GEMINI_MODEL);
        $this->info('Batch limit: ' . $limit);

        // =========================================================
        // 1. Fetch ONLY pending & unprocessed articles
        // =========================================================

        $newsList = News::query()
            ->where('status', 'pending')
            ->where('ai_processed', false)
            ->whereNotNull('content_en')
            ->where('content_en', '!=', '')
            ->latest()
            ->limit($limit)
            ->get();

        if ($newsList->isEmpty()) {
            $this->newLine();
            $this->info('✅ No unprocessed articles found.');

            return self::SUCCESS;
        }

        $this->newLine();

        $this->info("Found {$newsList->count()} unprocessed articles.");

        $processed = 0;
        $failed = 0;
        $skipped = 0;
        $validationFailed = 0;
        $apiFailed = 0;

        foreach ($newsList as $news) {

            $this->newLine();

            $this->info(
                "Processing ID {$news->id}: {$news->title_en}"
            );

            try {

                $title = trim((string) $news->title_en);

                $content = trim(
                    mb_substr(
                        (string) $news->content_en,
                        0,
                        self::MAX_SOURCE_LENGTH
                    )
                );

                $contentLength = mb_strlen($content);

                // =====================================================
                // Basic article validation
                // =====================================================

                if (mb_strlen($title) < 5) {

                    $this->warn(
                        "⚠️ Article {$news->id} has an invalid title."
                    );

                    $skipped++;

                    continue;
                }

                if ($contentLength < self::MIN_SOURCE_LENGTH) {

                    $this->warn(
                        "⚠️ Article {$news->id} source content is too short for AI."
                    );

                    $skipped++;

                    continue;
                }

                // =====================================================
                // Gemini analysis
                // =====================================================

                $result = $this->analyzeWithGemini(
                    $news,
                    $title,
                    $content,
                    $apiKey
                );

                // =====================================================
                // Daily / hard quota
                // =====================================================

                if (
                    is_array($result) &&
                    ($result['__quota_exceeded'] ?? false)
                ) {

                    $this->error(
                        '🛑 Gemini API quota appears to be exhausted. '
                        . 'The current AI cycle has been stopped.'
                    );

                    Log::warning(
                        'Gemini processing stopped because quota was exceeded.',
                        [
                            'news_id' => $news->id,
                            'message' => $result['__message'] ?? null,
                            'status' => $result['__status'] ?? null,
                        ]
                    );

                    $apiFailed++;

                    break;
                }

                // =====================================================
                // Rate limit / temporary API failure
                // =====================================================

                if (
                    is_array($result) &&
                    ($result['__temporary_failure'] ?? false)
                ) {

                    $message = $result['__message']
                        ?? 'Temporary Gemini API failure.';

                    $status = $result['__status'] ?? null;

                    $this->error(
                        "❌ Temporary Gemini API failure for ID {$news->id}"
                        . ($status ? " [HTTP {$status}]" : '')
                    );

                    $this->line(
                        "   └─ {$message}"
                    );

                    $apiFailed++;

                    /*
                     * Do NOT mark the article as permanently failed.
                     *
                     * It remains:
                     * status = pending
                     * ai_processed = false
                     *
                     * so the next scheduled cycle can retry it.
                     */

                    continue;
                }

                // =========================================================
                // Relevance Gate
                // =========================================================

                if (
                    !is_array($result) ||
                    !array_key_exists('is_relevant', $result)
                ) {

                    $this->error(
                        "❌ AI relevance decision missing for #{$news->id}"
                    );

                    $news->update([
                        'status' => 'failed',
                        'rejection_reason' => 'missing_relevance_decision',
                        'ai_processed' => false,
                    ]);

                    $failed++;

                    continue;
                }

                // =========================================================
                // Rejected content
                // =========================================================

                if ($result['is_relevant'] === false) {

                    $reason = trim(
                        (string) (
                            $result['rejection_reason']
                            ?? 'off_topic'
                        )
                    );

                    if ($reason === '') {
                        $reason = 'off_topic';
                    }

                    $news->update([
                        'status' => 'rejected',
                        'rejection_reason' => mb_substr(
                            $reason,
                            0,
                            255
                        ),
                        'ai_processed' => false,
                    ]);

                    $this->warn(
                        "🚫 Rejected #{$news->id}: "
                        . "{$news->title_en} — {$reason}"
                    );

                    $skipped++;

                    continue;
                }

                // =========================================================
                // Validate AI result
                // =========================================================

                if (!$this->isValidResult($result)) {

                    $validationFailed++;

                    continue;
                }

                // =========================================================
                // Normalize output
                // =========================================================

                $slug = $this->buildSlug(
                    $title,
                    $news->id
                );

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

                    $validationFailed++;

                    continue;
                }

                $sentiment = $this->normalizeSentiment(
                    $result['sentiment'] ?? null
                );

                $category = $this->normalizeCategory(
                    $result['category'] ?? null
                );

                $impactScore = $this->normalizeImpactScore(
                    $result['impact_score'] ?? null
                );

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

                // =========================================================
                // Final content validation
                // =========================================================

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

                    continue;
                }

                // =========================================================
                // Publication state update
                // =========================================================

                $news->update([
                    'title_ar' => $titleAr,
                    'content_ar' => $contentAr,
                    'summary_ar' => $summaryAr,
                    'meta_description_ar' => $metaDescriptionAr,
                    'why_it_matters_ar' => $whyItMattersAr,
                    'analysis_ar' => $analysisAr,
                    'context_ar' => $contextAr,
                    'what_to_watch_ar' => $whatToWatchAr,
                    'limitations_ar' => $limitationsAr,
                    'sentiment' => $sentiment,
                    'category' => $category,
                    'impact_score' => $impactScore,
                    'keywords' => $keywords,
                    'slug' => $slug,
                    'ai_processed' => true,
                    'status' => 'published',
                    'rejection_reason' => null,
                ]);

                $processed++;

                $this->info(
                    "✅ AI editorial analysis saved and published for ID {$news->id}"
                );

            } catch (\Throwable $e) {

                $failed++;

                $this->error(
                    "❌ Processing failed for ID {$news->id}: "
                    . $e->getMessage()
                );

                Log::error(
                    'Unexpected AI news processing failure.',
                    [
                        'news_id' => $news->id,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );

                $news->update([
                    'status' => 'failed',
                    'rejection_reason' => 'ai_processing_failed',
                    'ai_processed' => false,
                ]);
            }

            // =========================================================
            // Delay between articles
            // =========================================================

            if ($news->id !== $newsList->last()->id) {

                $this->line(
                    '⏳ Waiting '
                    . self::RATE_LIMIT_SECONDS
                    . ' seconds before next article...'
                );

                sleep(self::RATE_LIMIT_SECONDS);
            }
        }

        // =========================================================
        // Clear dashboard caches
        // =========================================================

        Cache::forget('ai_market_dashboard_stats');
        Cache::forget('ai_market_impact_news');

        // =========================================================
        // Final statistics
        // =========================================================

        $this->newLine();

        $this->info(
            "📊 Processed: {$processed}"
            . " | ❌ Failed: {$failed}"
            . " | 🌐 API Failed: {$apiFailed}"
            . " | ⏭️ Skipped/Rejected: {$skipped}"
            . " | ⚠️ Validation Failed: {$validationFailed}"
        );

        $this->newLine();

        $this->info(
            '🚀 Aql Crypto AI Editorial Cycle Completed.'
        );

        /*
         * API failures are temporary by design and should not make
         * the whole Artisan command return FAILURE.
         *
         * A permanent application failure is still reported.
         */
        return ($failed > 0 && $processed === 0)
            ? self::FAILURE
            : self::SUCCESS;
    }

    // =====================================================================
    // GEMINI
    // =====================================================================

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

        // =========================================================
        // Prompt
        // =========================================================

        $prompt = <<<'PROMPT'
You are the senior editorial analyst for Aql Crypto, an Arabic cryptocurrency news and market-analysis platform.

Your task is to transform the supplied factual source material into an ORIGINAL Arabic editorial news analysis.

This is NOT a literal translation.

This is NOT a sentence-by-sentence rewrite.

=========================================================

RELEVANCE GATE — CRITICAL

=========================================================

قبل كتابة أي تحليل عربي، يجب أولاً تحديد ما إذا كان المصدر

يتعلق بشكل جوهري بمجال Aql Crypto.

Aql Crypto منصة أخبار وتحليل متخصصة في:

- Bitcoin
- Ethereum
- العملات الرقمية
- Blockchain
- DeFi
- NFTs
- Mining
- Crypto Security
- Crypto Regulation
- Crypto infrastructure
- Stablecoins
- Crypto exchanges
- Crypto-related institutional activity
- Crypto-related macroeconomic developments
- Crypto-related AI developments عندما يكون الارتباط بالكريبتو جوهرياً

يجب رفض الخبر إذا كان موضوعه الأساسي خارج مجال العملات الرقمية

والبلوكشين، حتى لو وردت فيه كلمة مرتبطة بالكريبتو بشكل عابر.

أمثلة يجب رفضها:

- Crypto casinos
- Online casinos
- Roulette
- Slots
- Blackjack
- Poker
- Gambling sites
- Sports betting platforms
- iGaming
- Casino VIP programs
- Lottery news
- أخبار الجرائم أو المشاهير أو الفيروسات أو الرياضة أو السياسة

  التي لا تحتوي على ارتباط جوهري بالكريبتو.

مهم جداً:

لا ترفض الخبر بسبب كلمة واحدة فقط.

وجود الكلمات التالية وحدها لا يعني أن الخبر غير متعلق بالكريبتو:

- betting
- bet
- AI
- artificial intelligence
- bank
- security
- regulation
- market
- gambling

يجب فهم السياق الكامل للعنوان والمحتوى.

مثال:

"Ethereum is betting its future on quantum security and AI"

هذا خبر متعلق بـ Ethereum وبالتالي يجب اعتباره relevant.

بينما:

"6 Crypto Casinos for Roulette Players"

هذا خبر عن الكازينوهات والقمار، وليس خبراً جوهرياً عن العملات

الرقمية، ولذلك يجب اعتباره غير relevant.

إذا كان الخبر غير متعلق بشكل جوهري بالكريبتو:

"is_relevant": false

ضع سبباً مختصراً في:

"rejection_reason"

ولا تحاول اختراع زاوية كريبتو للخبر.

في حالة الرفض، لا تكتب تحليلاً عربياً ولا ملخصاً ولا تصنيفاً إخبارياً.

إذا كان الخبر متعلقاً بالكريبتو:

"is_relevant": true

ثم أكمل التحليل التحريري المعتاد.

==================================================

SOURCE FIDELITY

==================================================

Use ONLY information contained in the supplied material.

Never invent quotes, prices, volumes, or facts.

=========================================================

OUTPUT RULES

=========================================================

يجب أن يكون is_relevant هو القرار الأول.

إذا كان is_relevant = false:

- rejection_reason يجب أن يحتوي على سبب قصير وواضح.

- جميع الحقول التحريرية الأخرى يمكن أن تكون فارغة.

- لا تخترع أي علاقة بالكريبتو.

- لا تحاول إعادة صياغة الخبر كخبر كريبتو.

- لا تستخدم category أو sentiment أو impact_score لإجبار الخبر

  على أن يصبح صالحاً للنشر.

إذا كان is_relevant = true:

- rejection_reason = ""

- يجب إنتاج جميع الحقول التحريرية المطلوبة.

- يجب اختيار category من القائمة المسموح بها فقط.

==================================================

JSON OUTPUT

==================================================

Return ONLY valid JSON.

Use exactly these fields:

{
  "is_relevant": true,
  "rejection_reason": "",
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

Source: {{SOURCE}}

Original publication timestamp: {{DATE}}

==================================================

ARTICLE TITLE

==================================================

{{TITLE}}

==================================================

ARTICLE CONTENT

==================================================

{{CONTENT}}

PROMPT;

        $prompt = str_replace(
            [
                '{{SOURCE}}',
                '{{URL}}',
                '{{DATE}}',
                '{{TITLE}}',
                '{{CONTENT}}',
            ],
            [
                (string) ($news->source ?? 'Unknown'),
                (string) ($news->url ?? ''),
                (string) ($news->created_at ?? ''),
                $title,
                $content,
            ],
            $prompt
        );

        // =========================================================
        // Gemini response schema
        // =========================================================

        $schema = [
            'type' => 'OBJECT',

            'properties' => [

                'is_relevant' => [
                    'type' => 'BOOLEAN',
                ],

                'rejection_reason' => [
                    'type' => 'STRING',
                ],

                'title_ar' => [
                    'type' => 'STRING',
                ],

                'content_ar' => [
                    'type' => 'STRING',
                ],

                'summary_ar' => [
                    'type' => 'STRING',
                ],

                'meta_description_ar' => [
                    'type' => 'STRING',
                ],

                'why_it_matters_ar' => [
                    'type' => 'STRING',
                ],

                'analysis_ar' => [
                    'type' => 'STRING',
                ],

                'context_ar' => [
                    'type' => 'STRING',
                ],

                'what_to_watch_ar' => [
                    'type' => 'STRING',
                ],

                'limitations_ar' => [
                    'type' => 'STRING',
                ],

                'sentiment' => [
                    'type' => 'STRING',
                ],

                'category' => [
                    'type' => 'STRING',
                ],

                'impact_score' => [
                    'type' => 'INTEGER',
                ],

                'keywords' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'STRING',
                    ],
                ],
            ],

            'required' => [

                'is_relevant',
                'rejection_reason',
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

        // =========================================================
        // Gemini payload
        // =========================================================

        $payload = [

            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],

            'generationConfig' => [

                'temperature' => 0.35,

                'response_mime_type' => 'application/json',

                'response_schema' => $schema,
            ],
        ];

        // =========================================================
        // Retry loop
        // =========================================================

        $totalAttempts = self::MAX_API_RETRIES + 1;

        for (
            $attempt = 1;
            $attempt <= $totalAttempts;
            $attempt++
        ) {

            try {

                Log::info(
                    'Gemini API request starting.',
                    [
                        'news_id' => $news->id,
                        'model' => self::GEMINI_MODEL,
                        'attempt' => $attempt,
                        'max_attempts' => $totalAttempts,
                        'source_length' => mb_strlen($content),
                        'prompt_length' => mb_strlen($prompt),
                    ]
                );

                $response = Http::timeout(
                    self::HTTP_TIMEOUT_SECONDS
                )
                    ->acceptJson()
                    ->asJson()
                    ->post(
                        $url,
                        $payload
                    );

                // =====================================================
                // Successful response
                // =====================================================

                if ($response->successful()) {

                    $json = $response->json();

                    $text = data_get(
                        $json,
                        'candidates.0.content.parts.0.text'
                    );

                    if (
                        !is_string($text) ||
                        trim($text) === ''
                    ) {

                        Log::error(
                            'Gemini returned empty content.',
                            [
                                'news_id' => $news->id,
                                'status' => $response->status(),
                                'response' => mb_substr(
                                    $response->body(),
                                    0,
                                    10000
                                ),
                            ]
                        );

                        return null;
                    }

                    $text = trim($text);

                    // =================================================
                    // Clean possible Markdown JSON fences
                    // =================================================

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

                    $text = trim($text);

                    // =================================================
                    // JSON decode
                    // =================================================

                    $data = json_decode(
                        $text,
                        true
                    );

                    if (
                        json_last_error() !== JSON_ERROR_NONE
                    ) {

                        Log::error(
                            'Gemini JSON decode failed.',
                            [
                                'news_id' => $news->id,
                                'status' => $response->status(),
                                'json_error' => json_last_error_msg(),
                                'raw_response' => mb_substr(
                                    $text,
                                    0,
                                    10000
                                ),
                            ]
                        );

                        return null;
                    }

                    if (!is_array($data)) {

                        Log::error(
                            'Gemini returned non-array result.',
                            [
                                'news_id' => $news->id,
                                'raw_response' => mb_substr(
                                    $text,
                                    0,
                                    10000
                                ),
                            ]
                        );

                        return null;
                    }

                    Log::info(
                        'Gemini raw result received',
                        [
                            'news_id' => $news->id,
                            'status' => $response->status(),
                            'attempt' => $attempt,
                            'is_relevant' => $data['is_relevant'] ?? null,
                            'keys' => array_keys($data),
                        ]
                    );

                    return $data;
                }

                // =====================================================
                // HTTP ERROR DIAGNOSTICS
                // =====================================================

                $status = $response->status();

                $responseBody = $response->body();

                $responseJson = $response->json();

                $apiMessage =
                    data_get(
                        $responseJson,
                        'error.message'
                    )
                    ??
                    data_get(
                        $responseJson,
                        'error.details.0'
                    )
                    ??
                    mb_substr(
                        trim($responseBody),
                        0,
                        2000
                    );

                Log::warning(
                    'Gemini API request failed.',
                    [
                        'news_id' => $news->id,
                        'status' => $status,
                        'attempt' => $attempt,
                        'max_attempts' => $totalAttempts,
                        'message' => $apiMessage,
                        'retry_after' => $response->header(
                            'Retry-After'
                        ),
                        'response' => mb_substr(
                            $responseBody,
                            0,
                            10000
                        ),
                    ]
                );

                // =====================================================
                // 429 RATE LIMIT / RESOURCE EXHAUSTED
                // =====================================================

                if ($status === 429) {

                    /*
                     * IMPORTANT:
                     *
                     * HTTP 429 does NOT automatically mean that the
                     * daily quota is exhausted.
                     *
                     * We inspect the Gemini error payload.
                     */

                    $errorStatus = strtoupper(
                        (string) data_get(
                            $responseJson,
                            'error.status',
                            ''
                        )
                    );

                    $errorReason = strtolower(
                        (string) data_get(
                            $responseJson,
                            'error.details.0.reason',
                            ''
                        )
                    );

                    $errorMessage = strtolower(
                        (string) (
                            data_get(
                                $responseJson,
                                'error.message',
                                ''
                            )
                        )
                    );

                    $isDailyQuota =
                        str_contains(
                            $errorMessage,
                            'per day'
                        )
                        ||
                        str_contains(
                            $errorMessage,
                            'daily'
                        )
                        ||
                        str_contains(
                            $errorMessage,
                            'quota exceeded'
                        )
                        &&
                        (
                            str_contains(
                                $errorMessage,
                                'limit: 0'
                            )
                            ||
                            str_contains(
                                $errorMessage,
                                'requests per day'
                            )
                        );

                    /*
                     * If the response explicitly indicates a quota
                     * exhaustion rather than a temporary rate limit,
                     * stop the batch.
                     */

                    if (
                        $isDailyQuota ||
                        $errorReason === 'quota_exceeded'
                    ) {

                        Log::error(
                            'Gemini daily quota appears exhausted.',
                            [
                                'news_id' => $news->id,
                                'status' => $status,
                                'attempt' => $attempt,
                                'error_status' => $errorStatus,
                                'error_reason' => $errorReason,
                                'message' => $apiMessage,
                            ]
                        );

                        return [
                            '__quota_exceeded' => true,
                            '__message' => $apiMessage,
                            '__status' => $status,
                        ];
                    }

                    /*
                     * Otherwise treat 429 as a temporary rate limit
                     * and retry.
                     */

                    if ($attempt < $totalAttempts) {

                        $retrySeconds =
                            $this->calculateRetryDelay(
                                $attempt,
                                $response->header(
                                    'Retry-After'
                                )
                            );

                        $this->warn(
                            "⚠️ Gemini rate limit for ID {$news->id} "
                            . "[HTTP 429]. "
                            . "Retrying in {$retrySeconds}s..."
                        );

                        sleep($retrySeconds);

                        continue;
                    }

                    return [
                        '__temporary_failure' => true,
                        '__status' => 429,
                        '__message' => $apiMessage
                            ?: 'Gemini API rate limit reached after retries.',
                    ];
                }

                // =====================================================
                // Temporary server errors
                // =====================================================

                if (
                    in_array(
                        $status,
                        [408, 500, 502, 503, 504],
                        true
                    )
                ) {

                    if ($attempt < $totalAttempts) {

                        $retrySeconds =
                            $this->calculateRetryDelay(
                                $attempt
                            );

                        $this->warn(
                            "⚠️ Gemini temporary HTTP {$status} "
                            . "for ID {$news->id}. "
                            . "Retrying in {$retrySeconds}s..."
                        );

                        sleep($retrySeconds);

                        continue;
                    }

                    return [
                        '__temporary_failure' => true,
                        '__status' => $status,
                        '__message' => $apiMessage
                            ?: "Gemini temporary HTTP {$status} error.",
                    ];
                }

                // =====================================================
                // Other HTTP errors
                // =====================================================

                Log::error(
                    'Gemini API non-retryable HTTP error.',
                    [
                        'news_id' => $news->id,
                        'status' => $status,
                        'message' => $apiMessage,
                        'response' => mb_substr(
                            $responseBody,
                            0,
                            10000
                        ),
                    ]
                );

                return null;

            } catch (\Throwable $e) {

                /*
                 * This catches:
                 *
                 * - connection failures
                 * - timeout exceptions
                 * - SSL problems
                 * - DNS problems
                 * - cURL failures
                 * - unexpected HTTP client exceptions
                 */

                Log::warning(
                    'Gemini API request exception.',
                    [
                        'news_id' => $news->id,
                        'attempt' => $attempt,
                        'max_attempts' => $totalAttempts,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]
                );

                if ($attempt < $totalAttempts) {

                    $retrySeconds =
                        $this->calculateRetryDelay(
                            $attempt
                        );

                    $this->warn(
                        "⚠️ Gemini connection error for ID {$news->id}. "
                        . "Retrying in {$retrySeconds}s..."
                    );

                    sleep($retrySeconds);

                    continue;
                }

                return [
                    '__temporary_failure' => true,
                    '__status' => null,
                    '__message' => $e->getMessage(),
                ];
            }
        }

        return [
            '__temporary_failure' => true,
            '__status' => null,
            '__message' => 'Gemini API request failed after all retries.',
        ];
    }

    // =====================================================================
    // RETRY DELAY
    // =====================================================================

    private function calculateRetryDelay(
        int $attempt,
        ?string $retryAfter = null
    ): int {

        /*
         * If Gemini provides Retry-After, prefer it.
         */

        if (
            is_string($retryAfter) &&
            is_numeric($retryAfter)
        ) {

            $retryAfterSeconds = max(
                1,
                (int) $retryAfter
            );

            return min(
                $retryAfterSeconds,
                self::MAX_RETRY_SECONDS
            );
        }

        /*
         * Exponential backoff:
         *
         * attempt 1 = 5 seconds
         * attempt 2 = 10 seconds
         * attempt 3 = 20 seconds
         */

        $baseDelay =
            self::RETRY_BASE_SECONDS
            * (2 ** max(0, $attempt - 1));

        /*
         * Add small random jitter:
         *
         * 0-30% of the base delay.
         */

        $jitter = random_int(
            0,
            max(
                1,
                (int) floor($baseDelay * 0.30)
            )
        );

        return min(
            $baseDelay + $jitter,
            self::MAX_RETRY_SECONDS
        );
    }

    // =====================================================================
    // RESULT VALIDATION
    // =====================================================================

    private function isValidResult(
        ?array $result
    ): bool {

        if (!is_array($result)) {

            $this->error(
                '❌ AI result is not an array.'
            );

            return false;
        }

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

        $missing = [];

        foreach ($required as $field) {

            if (!array_key_exists($field, $result)) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {

            $this->error(
                '❌ Missing AI fields: '
                . implode(', ', $missing)
            );

            Log::error(
                'Gemini result validation failed',
                [
                    'missing_fields' => $missing,
                    'result_keys' => array_keys($result),
                ]
            );

            return false;
        }

        return true;
    }

    // =====================================================================
    // FINAL CONTENT VALIDATION
    // =====================================================================

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

        $checks = [

            'title_ar' => [

                mb_strlen($titleAr) >= 15 &&
                mb_strlen($titleAr) <= 180,

                mb_strlen($titleAr),

                '15-180',
            ],

            'content_ar' => [

                mb_strlen($contentAr) >= self::MIN_ARTICLE_LENGTH &&
                mb_strlen($contentAr) <= self::MAX_ARTICLE_LENGTH,

                mb_strlen($contentAr),

                self::MIN_ARTICLE_LENGTH
                . '-'
                . self::MAX_ARTICLE_LENGTH,
            ],

            'summary_ar' => [

                mb_strlen($summaryAr) >= 50,

                mb_strlen($summaryAr),

                '>=50',
            ],

            'why_it_matters_ar' => [

                mb_strlen($whyItMattersAr) >= 100,

                mb_strlen($whyItMattersAr),

                '>=100',
            ],

            'analysis_ar' => [

                mb_strlen($analysisAr) >= 180,

                mb_strlen($analysisAr),

                '>=180',
            ],

            'context_ar' => [

                mb_strlen($contextAr) >= 100,

                mb_strlen($contextAr),

                '>=100',
            ],

            'what_to_watch_ar' => [

                mb_strlen($whatToWatchAr) >= 80,

                mb_strlen($whatToWatchAr),

                '>=80',
            ],

            'limitations_ar' => [

                mb_strlen($limitationsAr) >= 50,

                mb_strlen($limitationsAr),

                '>=50',
            ],

            'meta_description_ar' => [

                mb_strlen($metaDescriptionAr) >= 50 &&
                mb_strlen($metaDescriptionAr) <= 180,

                mb_strlen($metaDescriptionAr),

                '50-180',
            ],
        ];

        $valid = true;

        foreach (
            $checks
            as $field => [$passed, $length, $expected]
        ) {

            if (!$passed) {

                $this->error(
                    "❌ {$field}: "
                    . "length={$length}, "
                    . "expected={$expected}"
                );

                $valid = false;
            }
        }

        return $valid;
    }

    // =====================================================================
    // SLUG
    // =====================================================================

    private function buildSlug(
        string $title,
        int|string $id
    ): string {

        $slug = Str::slug($title);

        if ($slug === '') {
            $slug = 'news';
        }

        return $slug . '-' . $id;
    }

    // =====================================================================
    // KEYWORDS
    // =====================================================================

    private function normalizeKeywords(
        array $keywords
    ): array {

        return collect($keywords)

            ->map(
                fn ($keyword) =>
                    trim((string) $keyword)
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
                    mb_strtolower($keyword)
            )

            ->take(5)

            ->values()

            ->toArray();
    }

    // =====================================================================
    // SENTIMENT
    // =====================================================================

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

    // =====================================================================
    // CATEGORY
    // =====================================================================

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

    // =====================================================================
    // IMPACT SCORE
    // =====================================================================

    private function normalizeImpactScore(
        mixed $score
    ): int {

        if (!is_numeric($score)) {
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