<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RepairNewsWithAI extends Command
{
    protected $signature = 'news:repair-ai';

    protected $description =
        'Repair previously processed news articles with missing AI editorial fields.';

    /*
    |--------------------------------------------------------------------------
    | Gemini Configuration
    |--------------------------------------------------------------------------
    */

    private const GEMINI_MODEL = 'gemini-3.6-flash';

    /*
    |--------------------------------------------------------------------------
    | Number of articles processed in one command execution
    |--------------------------------------------------------------------------
    */

    private const BATCH_LIMIT = 10;

    /*
    |--------------------------------------------------------------------------
    | Delay between successful requests
    |--------------------------------------------------------------------------
    */

    private const RATE_LIMIT_SECONDS = 20;

    /*
    |--------------------------------------------------------------------------
    | Maximum source content sent to Gemini
    |--------------------------------------------------------------------------
    */

    private const MAX_SOURCE_LENGTH = 12000;

    /*
    |--------------------------------------------------------------------------
    | Temporary API retry configuration
    |--------------------------------------------------------------------------
    */

    private const MAX_API_RETRIES = 2;

    private const RETRY_BASE_SECONDS = 5;

    /*
    |--------------------------------------------------------------------------
    | Main handler
    |--------------------------------------------------------------------------
    */

    public function handle(): int
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {

            $this->error('❌ GEMINI_API_KEY is missing.');

            Log::error(
                'AI News Repair aborted: Gemini API key missing.'
            );

            return self::FAILURE;
        }

        $this->info('🔧 Starting Aql Crypto AI Repair Cycle...');

        $this->info(
            'Model: ' . self::GEMINI_MODEL
        );

        /*
        |--------------------------------------------------------------------------
        | Find incomplete processed articles
        |--------------------------------------------------------------------------
        */

        $newsList = News::query()
            ->where('ai_processed', true)
            ->where(function ($q) {

                $q->whereNull('title_ar')
                    ->orWhere('title_ar', '')
                    ->orWhereNull('content_ar')
                    ->orWhere('content_ar', '')
                    ->orWhereNull('summary_ar')
                    ->orWhere('summary_ar', '')
                    ->orWhereNull('why_it_matters_ar')
                    ->orWhere('why_it_matters_ar', '')
                    ->orWhereNull('analysis_ar')
                    ->orWhere('analysis_ar', '')
                    ->orWhereNull('context_ar')
                    ->orWhere('context_ar', '')
                    ->orWhereNull('what_to_watch_ar')
                    ->orWhere('what_to_watch_ar', '')
                    ->orWhereNull('limitations_ar')
                    ->orWhere('limitations_ar', '');
            })
            ->oldest()
            ->limit(self::BATCH_LIMIT)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Nothing to repair
        |--------------------------------------------------------------------------
        */

        if ($newsList->isEmpty()) {

            $this->info(
                '✅ No incomplete processed articles found.'
            );

            return self::SUCCESS;
        }

        $this->info(
            "🔧 Repair mode: {$newsList->count()} incomplete articles selected."
        );

        $repaired = 0;
        $failed = 0;
        $skipped = 0;

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
                | Determine exactly which fields are missing
                |--------------------------------------------------------------------------
                */

                $missingFields = $this->getMissingFields($news);

                if (empty($missingFields)) {

                    $this->info(
                        "ℹ️ Article {$news->id} has no repairable missing fields."
                    );

                    $skipped++;

                    continue;
                }

                $this->line(
                    'Missing fields: ' . implode(', ', $missingFields)
                );

                /*
                |--------------------------------------------------------------------------
                | Existing article information
                |--------------------------------------------------------------------------
                */

                $titleEn = trim(
                    (string) $news->title_en
                );

                $contentEn = trim(
                    mb_substr(
                        (string) $news->content_en,
                        0,
                        self::MAX_SOURCE_LENGTH
                    )
                );

                $titleAr = trim(
                    (string) $news->title_ar
                );

                $contentAr = trim(
                    (string) $news->content_ar
                );

                $summaryAr = trim(
                    (string) $news->summary_ar
                );

                /*
                |--------------------------------------------------------------------------
                | Build source material
                |--------------------------------------------------------------------------
                */

                $sourceMaterial = implode(
                    "\n\n",
                    array_filter([
                        'English title: ' . $titleEn,
                        'English source content: ' . $contentEn,
                        'Existing Arabic title: ' . $titleAr,
                        'Existing Arabic content: ' . $contentAr,
                        'Existing Arabic summary: ' . $summaryAr,
                        'Source: ' . ($news->source ?? ''),
                    ])
                );

                /*
                |--------------------------------------------------------------------------
                | Minimum factual material check
                |--------------------------------------------------------------------------
                */

                if (
                    mb_strlen(
                        trim($sourceMaterial)
                    ) < 200
                ) {

                    $this->warn(
                        "⚠️ Article {$news->id} does not contain enough factual material for safe repair."
                    );

                    Log::warning(
                        'AI repair skipped because available source material is insufficient',
                        [
                            'news_id' => $news->id,
                            'source_length' => mb_strlen($sourceMaterial),
                            'missing_fields' => $missingFields,
                        ]
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Ask Gemini to repair only missing fields
                |--------------------------------------------------------------------------
                */

                $result = $this->repairWithGemini(
                    $news,
                    $sourceMaterial,
                    $missingFields,
                    $apiKey
                );

                /*
                |--------------------------------------------------------------------------
                | Gemini quota exceeded
                |--------------------------------------------------------------------------
                */

                if (
                    is_array($result) &&
                    ($result['__quota_exceeded'] ?? false)
                ) {

                    $this->error(
                        '🛑 Gemini quota exhausted.'
                    );

                    $this->warn(
                        'Repair cycle stopped to avoid wasting further requests.'
                    );

                    Log::warning(
                        'AI repair cycle stopped because Gemini quota was exhausted',
                        [
                            'news_id' => $news->id,
                            'model' => self::GEMINI_MODEL,
                            'message' => $result['__message'] ?? null,
                            'retry_seconds' => $result['__retry_seconds'] ?? null,
                        ]
                    );

                    $failed++;

                    break;
                }

                /*
                |--------------------------------------------------------------------------
                | API temporary failure
                |--------------------------------------------------------------------------
                */

                if (
                    is_array($result) &&
                    ($result['__temporary_failure'] ?? false)
                ) {

                    $this->error(
                        "❌ Temporary Gemini API failure for ID {$news->id}"
                    );

                    $failed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                */

                if (
                    !$this->isValidResult(
                        $result,
                        $missingFields
                    )
                ) {

                    $this->error(
                        "❌ Repair result failed validation for ID {$news->id}"
                    );

                    Log::warning(
                        'AI repair result failed validation',
                        [
                            'news_id' => $news->id,
                            'missing_fields' => $missingFields,
                            'result' => $result,
                        ]
                    );

                    $failed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Build safe update array
                |--------------------------------------------------------------------------
                */

                $updates = [];

                foreach ($missingFields as $field) {

                    $existing = trim(
                        (string) $news->{$field}
                    );

                    $generated = trim(
                        (string) ($result[$field] ?? '')
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | NEVER overwrite existing content
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $existing === '' &&
                        $generated !== ''
                    ) {

                        $updates[$field] = $generated;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Optional AI fields
                |--------------------------------------------------------------------------
                */

                if (
                    empty($news->sentiment) &&
                    !empty($result['sentiment'])
                ) {

                    $updates['sentiment'] =
                        $result['sentiment'];
                }

                if (
                    empty($news->category) &&
                    !empty($result['category'])
                ) {

                    $updates['category'] =
                        $result['category'];
                }

                if (
                    empty($news->impact_score) &&
                    isset($result['impact_score'])
                ) {

                    $updates['impact_score'] =
                        (int) $result['impact_score'];
                }

                if (
                    empty($news->keywords) &&
                    !empty($result['keywords'])
                ) {

                    $updates['keywords'] =
                        $result['keywords'];
                }

                /*
                |--------------------------------------------------------------------------
                | Slug
                |--------------------------------------------------------------------------
                */

                if (empty($news->slug)) {

                    $slug = Str::slug(
                        $titleEn
                    );

                    if ($slug === '') {
                        $slug = 'news';
                    }

                    $updates['slug'] =
                        $slug . '-' . $news->id;
                }

                /*
                |--------------------------------------------------------------------------
                | Nothing generated
                |--------------------------------------------------------------------------
                */

                if (empty($updates)) {

                    $this->warn(
                        "⚠️ No missing fields could be safely repaired for ID {$news->id}"
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Save
                |--------------------------------------------------------------------------
                */

                $news->update(
                    $updates
                );

                $repaired++;

                $this->info(
                    "✅ AI repair saved for ID {$news->id}"
                );

                $this->line(
                    'Updated fields: ' .
                    implode(
                        ', ',
                        array_keys($updates)
                    )
                );

            } catch (\Throwable $e) {

                $failed++;

                $this->error(
                    "❌ Repair failed for ID {$news->id}: {$e->getMessage()}"
                );

                Log::error(
                    'AI News Repair Exception',
                    [
                        'news_id' => $news->id,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Delay between successful article requests
            |--------------------------------------------------------------------------
            */

            sleep(
                self::RATE_LIMIT_SECONDS
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Clear caches
        |--------------------------------------------------------------------------
        */

        Cache::forget(
            'ai_market_dashboard_stats'
        );

        Cache::forget(
            'ai_market_impact_news'
        );

        $this->newLine();

        $this->info(
            '🧹 AI Market cache cleared.'
        );

        $this->info(
            "📊 Repaired: {$repaired} | Failed: {$failed} | Skipped: {$skipped}"
        );

        $this->info(
            '🚀 Aql Crypto AI Repair Cycle Completed.'
        );

        /*
        |--------------------------------------------------------------------------
        | Exit status
        |--------------------------------------------------------------------------
        */

        return $failed > 0 && $repaired === 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Determine missing fields
    |--------------------------------------------------------------------------
    */

    private function getMissingFields(
        News $news
    ): array {

        $fields = [
            'title_ar',
            'content_ar',
            'summary_ar',
            
            'why_it_matters_ar',
            'analysis_ar',
            'context_ar',
            'what_to_watch_ar',
            'limitations_ar',
        ];

        $missing = [];

        foreach ($fields as $field) {

            if (
                trim(
                    (string) $news->{$field}
                ) === ''
            ) {

                $missing[] = $field;
            }
        }

        return $missing;
    }

    /*
    |--------------------------------------------------------------------------
    | Gemini repair request
    |--------------------------------------------------------------------------
    */

    private function repairWithGemini(
        News $news,
        string $sourceMaterial,
        array $missingFields,
        string $apiKey
    ): ?array {

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            self::GEMINI_MODEL,
            $apiKey
        );

        /*
        |--------------------------------------------------------------------------
        | Create list of requested fields
        |--------------------------------------------------------------------------
        */

        $fieldList = implode(
            "\n",
            array_map(
                fn ($field) => "- {$field}",
                $missingFields
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Prompt
        |--------------------------------------------------------------------------
        */

        $prompt = <<<PROMPT
You are repairing an existing Arabic cryptocurrency news article for Aql Crypto.

This is NOT a new article.

Your task is to generate ONLY the missing fields listed below.

IMPORTANT RULES:

1. Do not invent facts.

2. Do not use outside knowledge.

3. Do not add current market prices.

4. Do not add statistics that are not supplied.

5. Do not add people, companies, dates, quotes, regulations or events that are not present in the supplied material.

6. Existing Arabic content is part of the factual material.

7. Preserve the meaning of existing content.

8. If the supplied material is insufficient for a field, write a cautious limitation instead of inventing information.

9. The article is journalism, not financial advice.

10. Never recommend buying or selling assets.

11. Use professional Modern Standard Arabic.

12. Return ONLY valid JSON.

13. Generate ONLY the fields listed in MISSING FIELDS.

14. Do not generate fields that are not listed.

==================================================
MISSING FIELDS
==================================================

{$fieldList}

==================================================
AVAILABLE MATERIAL
==================================================

{$sourceMaterial}

==================================================
FIELD GUIDANCE
==================================================

title_ar:
A concise and accurate Arabic headline.

content_ar:
A factual Arabic version of the supplied article material. Do not invent information.

summary_ar:
A short factual summary.


why_it_matters_ar:
Explain why the reported event may matter, using only supplied facts. Do not make unsupported market predictions.

analysis_ar:
Provide careful editorial analysis based only on supplied facts. Clearly distinguish facts from interpretation.

context_ar:
Explain context only when that context is explicitly supported by the supplied material.

what_to_watch_ar:
Mention developments readers could reasonably monitor based only on the supplied facts. Do not make investment recommendations.

limitations_ar:
Clearly state relevant limitations when the source material does not provide enough information.

==================================================
OUTPUT
==================================================

Return a JSON object containing ONLY the requested missing fields.

PROMPT;

        /*
        |--------------------------------------------------------------------------
        | Dynamic schema
        |--------------------------------------------------------------------------
        */

        $properties = [];

        foreach ($missingFields as $field) {

            $properties[$field] = [
                'type' => 'string',
            ];
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
            'required' => $missingFields,
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
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],

            'generationConfig' => [
                'temperature' => 0.25,
                'response_mime_type' => 'application/json',
                'response_schema' => $schema,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | API request with limited retry
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

                if ($response->successful()) {

                    $text = data_get(
                        $response->json(),
                        'candidates.0.content.parts.0.text'
                    );

                    if (
                        !is_string($text) ||
                        trim($text) === ''
                    ) {

                        Log::error(
                            'Gemini Repair returned empty response',
                            [
                                'news_id' => $news->id,
                                'attempt' => $attempt,
                            ]
                        );

                        return null;
                    }

                    $text = trim($text);

                    /*
                    |--------------------------------------------------------------------------
                    | Remove Markdown JSON fences if returned unexpectedly
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

                    $text = trim($text);

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
                        json_last_error() !== JSON_ERROR_NONE
                    ) {

                        Log::error(
                            'Gemini Repair JSON Error',
                            [
                                'news_id' => $news->id,
                                'attempt' => $attempt,
                                'error' => json_last_error_msg(),
                                'response' => $text,
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
                | 429 - Quota / Rate Limit
                |--------------------------------------------------------------------------
                */

                if (
                    $response->status() === 429
                ) {

                    $body = $response->json();

                    $message = data_get(
                        $body,
                        'error.message',
                        'Gemini API quota exceeded.'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Extract RetryInfo
                    |--------------------------------------------------------------------------
                    */

                    $retrySeconds = null;

                    $details = data_get(
                        $body,
                        'error.details',
                        []
                    );

                    if (is_array($details)) {

                        foreach ($details as $detail) {

                            if (
                                isset($detail['@type']) &&
                                $detail['@type'] ===
                                'type.googleapis.com/google.rpc.RetryInfo'
                            ) {

                                $retryDelay =
                                    $detail['retryDelay'] ?? null;

                                if (
                                    is_string($retryDelay)
                                ) {

                                    preg_match(
                                        '/(\d+(?:\.\d+)?)s/',
                                        $retryDelay,
                                        $matches
                                    );

                                    if (
                                        isset($matches[1])
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
                        'Gemini Repair Quota Exceeded',
                        [
                            'news_id' => $news->id,
                            'model' => self::GEMINI_MODEL,
                            'attempt' => $attempt,
                            'retry_seconds' => $retrySeconds,
                            'message' => $message,
                            'body' => $response->body(),
                        ]
                    );

                    $this->error(
                        '🚫 Gemini API returned HTTP 429.'
                    );

                    $this->warn(
                        'Message: ' . $message
                    );

                    if ($retrySeconds !== null) {

                        $this->warn(
                            "Suggested retry delay: {$retrySeconds} seconds."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | IMPORTANT:
                    |
                    | Do NOT retry automatically when quota is exhausted.
                    |
                    |--------------------------------------------------------------------------
                    */

                    return [
                        '__quota_exceeded' => true,
                        '__message' => $message,
                        '__retry_seconds' => $retrySeconds,
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
                            'news_id' => $news->id,
                            'status' => $response->status(),
                            'attempt' => $attempt,
                            'body' => $response->body(),
                        ]
                    );

                    if (
                        $attempt <= self::MAX_API_RETRIES
                    ) {

                        $delay =
                            self::RETRY_BASE_SECONDS *
                            $attempt;

                        $this->warn(
                            "⚠️ Temporary API error. Retrying in {$delay}s..."
                        );

                        sleep($delay);

                        continue;
                    }

                    return [
                        '__temporary_failure' => true,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Other API errors
                |--------------------------------------------------------------------------
                */

                Log::error(
                    'Gemini Repair API Error',
                    [
                        'news_id' => $news->id,
                        'status' => $response->status(),
                        'attempt' => $attempt,
                        'body' => $response->body(),
                    ]
                );

                return null;

            } catch (\Throwable $e) {

                Log::error(
                    'Gemini Repair Connection Error',
                    [
                        'news_id' => $news->id,
                        'attempt' => $attempt,
                        'message' => $e->getMessage(),
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Retry connection failures
                |--------------------------------------------------------------------------
                */

                if (
                    $attempt <= self::MAX_API_RETRIES
                ) {

                    $delay =
                        self::RETRY_BASE_SECONDS *
                        $attempt;

                    $this->warn(
                        "⚠️ Connection error. Retrying in {$delay}s..."
                    );

                    sleep($delay);

                    continue;
                }

                return [
                    '__temporary_failure' => true,
                ];
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Gemini result
    |--------------------------------------------------------------------------
    */

    private function isValidResult(
        ?array $result,
        array $missingFields
    ): bool {

        if (!is_array($result)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Internal control fields are not valid AI results
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
        | Make sure all requested fields exist
        |--------------------------------------------------------------------------
        */

        foreach ($missingFields as $field) {

            if (
                !array_key_exists(
                    $field,
                    $result
                )
            ) {

                return false;
            }

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

        return true;
    }
}