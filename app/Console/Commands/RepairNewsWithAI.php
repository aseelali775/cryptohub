<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RepairNewsWithAI extends Command
{
    protected $signature = 'news:repair-ai
                            {--limit=3 : Number of articles to repair per run}
                            {--ids= : Comma-separated article IDs to repair only}';

    protected $description =
        'Complete only missing or weak editorial AI sections without rewriting healthy article data.';

    /*
    |--------------------------------------------------------------------------
    | Gemini Configuration
    |--------------------------------------------------------------------------
    */

    private const GEMINI_MODEL = 'gemini-3.6-flash';

    /*
    |--------------------------------------------------------------------------
    | Small batch to protect Gemini quota.
    |--------------------------------------------------------------------------
    */

    private const DEFAULT_BATCH_LIMIT = 3;

    /*
    |--------------------------------------------------------------------------
    | Delay between Gemini requests.
    |--------------------------------------------------------------------------
    */

    private const RATE_LIMIT_SECONDS = 20;

    /*
    |--------------------------------------------------------------------------
    | Retry temporary errors only.
    |--------------------------------------------------------------------------
    */

    private const MAX_API_RETRIES = 1;

    private const RETRY_BASE_SECONDS = 5;

    /*
    |--------------------------------------------------------------------------
    | Maximum English source content sent to Gemini.
    |--------------------------------------------------------------------------
    */

    private const MAX_SOURCE_LENGTH = 6000;

    /*
    |--------------------------------------------------------------------------
    | Editorial field thresholds
    |--------------------------------------------------------------------------
    */

    private const MIN_ANALYSIS_AR = 300;

    private const MIN_CONTEXT_AR = 250;

    private const MIN_WHAT_TO_WATCH_AR = 200;

    private const MIN_LIMITATIONS_AR = 150;

    /*
    |--------------------------------------------------------------------------
    | Allowed fields.
    |
    | IMPORTANT:
    | Do not add title_ar, content_ar, summary_ar, keywords,
    | category, sentiment, impact_score or slug here.
    |--------------------------------------------------------------------------
    */

    private array $editorialFields = [
        'analysis_ar',
        'context_ar',
        'what_to_watch_ar',
        'limitations_ar',
    ];

    /*
    |--------------------------------------------------------------------------
    | Main Handler
    |--------------------------------------------------------------------------
    */

    public function handle(): int
    {
        $this->printHeader();

        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            $this->error(
                '❌ GEMINI_API_KEY is missing.'
            );

            Log::error(
                'Editorial repair aborted: Gemini API key missing.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Get only articles that actually need editorial repair.
        |--------------------------------------------------------------------------
        */

        $newsList = $this->getRepairableNews();

        if ($newsList->isEmpty()) {
            $this->info(
                '✅ No articles need editorial repair.'
            );

            return self::SUCCESS;
        }

        $this->info(
            "Found {$newsList->count()} article(s) requiring editorial completion."
        );

        $this->newLine();

        $processed = 0;
        $failed = 0;
        $skipped = 0;
        $apiRequests = 0;
        $quotaExceeded = false;
        $changed = false;

        /*
        |--------------------------------------------------------------------------
        | Process selected articles
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
                | Determine only the four fields that need repair.
                |--------------------------------------------------------------------------
                */

                $missingFields = $this->getMissingEditorialFields(
                    $news
                );

                if (empty($missingFields)) {

                    $this->line(
                        '✅ Article already has acceptable editorial fields.'
                    );

                    $skipped++;

                    continue;
                }

                $this->line(
                    'Fields requiring repair: ' .
                    implode(
                        ', ',
                        $missingFields
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Source material
                |--------------------------------------------------------------------------
                */

                $sourceMaterial = $this->buildSourceMaterial(
                    $news
                );

                if (
                    mb_strlen(
                        trim($sourceMaterial)
                    ) < 200
                ) {
                    $this->warn(
                        '⚠️ Not enough source material for safe editorial repair.'
                    );

                    Log::warning(
                        'Editorial repair skipped due to insufficient source material',
                        [
                            'news_id' => $news->id,
                            'source_length' => mb_strlen($sourceMaterial),
                            'fields' => $missingFields,
                        ]
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | One Gemini request for all missing fields of this article.
                |--------------------------------------------------------------------------
                */

                $apiRequests++;

                $result = $this->repairWithGemini(
                    $news,
                    $sourceMaterial,
                    $missingFields,
                    $apiKey
                );

                /*
                |--------------------------------------------------------------------------
                | Quota exhausted
                |--------------------------------------------------------------------------
                */

                if (
                    is_array($result) &&
                    ($result['__quota_exceeded'] ?? false)
                ) {
                    $quotaExceeded = true;

                    $this->error(
                        '🛑 Gemini quota/rate limit reached.'
                    );

                    $this->warn(
                        'Processing stopped immediately. No further requests will be sent.'
                    );

                    Log::warning(
                        'Editorial repair cycle stopped because Gemini quota was exceeded',
                        [
                            'news_id' => $news->id,
                            'model' => self::GEMINI_MODEL,
                            'retry_seconds' =>
                                $result['__retry_seconds'] ?? null,
                        ]
                    );

                    $failed++;

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
                        "❌ Temporary Gemini failure for ID {$news->id}"
                    );

                    $failed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validate generated fields.
                |--------------------------------------------------------------------------
                */

                $validation = $this->validateRepairResult(
                    $result,
                    $missingFields
                );

                if (!$validation['valid']) {

                    $this->error(
                        "❌ Repair validation failed for ID {$news->id}"
                    );

                    foreach ($validation['errors'] as $error) {
                        $this->line(
                            "  - {$error}"
                        );
                    }

                    Log::warning(
                        'Editorial repair validation failed',
                        [
                            'news_id' => $news->id,
                            'fields' => $missingFields,
                            'errors' => $validation['errors'],
                            'result' => $result,
                        ]
                    );

                    $failed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Build safe update array.
                |
                | Only fields that STILL need repair are saved.
                |--------------------------------------------------------------------------
                */

                $updates = [];

                foreach ($missingFields as $field) {

                    $generated = trim(
                        (string) (
                            $result[$field] ?? ''
                        )
                    );

                    if (
                        $generated === ''
                    ) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Protect against replacing a field that became healthy.
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$this->fieldNeedsRepair(
                            $news,
                            $field
                        )
                    ) {
                        continue;
                    }

                    $updates[$field] = $generated;
                }

                /*
                |--------------------------------------------------------------------------
                | Nothing safe to save.
                |--------------------------------------------------------------------------
                */

                if (empty($updates)) {

                    $this->warn(
                        "⚠️ No safely repairable fields returned for ID {$news->id}"
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Save ONLY the four editorial fields.
                |--------------------------------------------------------------------------
                */

                $news->update(
                    $updates
                );

                $processed++;
                $changed = true;

                $this->info(
                    "✅ Editorial repair saved for ID {$news->id}"
                );

                $this->line(
                    'Updated fields: ' .
                    implode(
                        ', ',
                        array_keys($updates)
                    )
                );

                foreach ($updates as $field => $value) {

                    $this->line(
                        "{$field}: " .
                        mb_strlen(
                            trim($value)
                        ) .
                        ' chars'
                    );
                }

            } catch (\Throwable $e) {

                $failed++;

                $this->error(
                    "❌ Repair failed for ID {$news->id}: {$e->getMessage()}"
                );

                Log::error(
                    'Editorial repair exception',
                    [
                        'news_id' => $news->id,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Delay only when a Gemini request was made.
            |--------------------------------------------------------------------------
            */

            if (!$quotaExceeded) {
                sleep(
                    self::RATE_LIMIT_SECONDS
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Clear caches only when something was actually changed.
        |--------------------------------------------------------------------------
        */

        if ($changed) {

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
        }

        /*
        |--------------------------------------------------------------------------
        | Final report
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            'AQL CRYPTO EDITORIAL REPAIR COMPLETED'
        );

        $this->info(
            '=============================================='
        );

        $this->line(
            "Articles repaired : {$processed}"
        );

        $this->line(
            "Gemini requests   : {$apiRequests}"
        );

        $this->line(
            "Failed            : {$failed}"
        );

        $this->line(
            "Skipped            : {$skipped}"
        );

        if ($quotaExceeded) {
            $this->warn(
                'Gemini quota was reached and the cycle stopped safely.'
            );
        }

        $this->newLine();

        $this->comment(
            'Only analysis_ar, context_ar, what_to_watch_ar and limitations_ar were eligible for repair.'
        );

        $this->comment(
            'Healthy existing fields were never overwritten.'
        );

        /*
        |--------------------------------------------------------------------------
        | Exit code
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
    | Select only articles needing editorial repair.
    |--------------------------------------------------------------------------
    */

    private function getRepairableNews()
    {
        $query = News::query()
            ->where(
                'ai_processed',
                true
            )
            ->where(function ($q) {

                $q->whereNull('analysis_ar')
                    ->orWhere('analysis_ar', '')
                    ->orWhereRaw(
                        'CHAR_LENGTH(TRIM(analysis_ar)) < ?',
                        [
                            self::MIN_ANALYSIS_AR,
                        ]
                    )

                    ->orWhereNull('context_ar')
                    ->orWhere('context_ar', '')
                    ->orWhereRaw(
                        'CHAR_LENGTH(TRIM(context_ar)) < ?',
                        [
                            self::MIN_CONTEXT_AR,
                        ]
                    )

                    ->orWhereNull('what_to_watch_ar')
                    ->orWhere('what_to_watch_ar', '')
                    ->orWhereRaw(
                        'CHAR_LENGTH(TRIM(what_to_watch_ar)) < ?',
                        [
                            self::MIN_WHAT_TO_WATCH_AR,
                        ]
                    )

                    ->orWhereNull('limitations_ar')
                    ->orWhere('limitations_ar', '')
                    ->orWhereRaw(
                        'CHAR_LENGTH(TRIM(limitations_ar)) < ?',
                        [
                            self::MIN_LIMITATIONS_AR,
                        ]
                    );
            })
            ->orderBy('id');

        /*
        |--------------------------------------------------------------------------
        | Optional IDs
        |--------------------------------------------------------------------------
        */

        $ids = trim(
            (string) $this->option('ids')
        );

        if ($ids !== '') {

            $idList = collect(
                explode(
                    ',',
                    $ids
                )
            )
                ->map(
                    fn ($id) =>
                        (int) trim($id)
                )
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (!empty($idList)) {

                $query->whereIn(
                    'id',
                    $idList
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Limit
        |--------------------------------------------------------------------------
        */

        $limit = (int) $this->option('limit');

        if ($limit <= 0) {
            $limit =
                self::DEFAULT_BATCH_LIMIT;
        }

        return $query
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Determine fields that need repair.
    |--------------------------------------------------------------------------
    */

    private function getMissingEditorialFields(
        News $news
    ): array {

        $fields = [];

        foreach (
            $this->editorialFields as $field
        ) {

            if (
                $this->fieldNeedsRepair(
                    $news,
                    $field
                )
            ) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /*
    |--------------------------------------------------------------------------
    | Determine whether a field is missing or weak.
    |--------------------------------------------------------------------------
    */

    private function fieldNeedsRepair(
        News $news,
        string $field
    ): bool {

        $value = trim(
            (string) $news->{$field}
        );

        if ($value === '') {
            return true;
        }

        $minimum = match ($field) {

            'analysis_ar' =>
                self::MIN_ANALYSIS_AR,

            'context_ar' =>
                self::MIN_CONTEXT_AR,

            'what_to_watch_ar' =>
                self::MIN_WHAT_TO_WATCH_AR,

            'limitations_ar' =>
                self::MIN_LIMITATIONS_AR,

            default =>
                0,
        };

        return (
            $minimum > 0 &&
            mb_strlen($value) < $minimum
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Build compact source material.
    |--------------------------------------------------------------------------
    */

    private function buildSourceMaterial(
        News $news
    ): string {

        $titleEn = trim(
            (string) $news->title_en
        );

        $titleAr = trim(
            (string) $news->title_ar
        );

        $contentEn = trim(
            mb_substr(
                (string) $news->content_en,
                0,
                self::MAX_SOURCE_LENGTH
            )
        );

        $summaryAr = trim(
            (string) $news->summary_ar
        );

        $whyAr = trim(
            (string) $news->why_it_matters_ar
        );

        return implode(
            "\n\n",
            array_filter([
                'SOURCE: ' .
                    (string) (
                        $news->source ?? ''
                    ),

                'TITLE EN: ' .
                    $titleEn,

                'TITLE AR: ' .
                    $titleAr,

                'ORIGINAL ARTICLE: ' .
                    $contentEn,

                'EXISTING SUMMARY: ' .
                    $summaryAr,

                'EXISTING WHY IT MATTERS: ' .
                    $whyAr,
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Gemini request.
    |--------------------------------------------------------------------------
    */

    private function repairWithGemini(
        News $news,
        string $sourceMaterial,
        array $fields,
        string $apiKey
    ): ?array {

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            self::GEMINI_MODEL,
            $apiKey
        );

        $fieldInstructions = [];

        foreach ($fields as $field) {

            $fieldInstructions[] =
                $this->getFieldInstruction(
                    $field
                );
        }

        $requestedFields = implode(
            "\n\n",
            $fieldInstructions
        );

        $prompt = <<<PROMPT
You are the senior Arabic editorial analyst for Aql Crypto.

You are repairing an existing cryptocurrency news article.

IMPORTANT:

Generate ONLY the missing or weak editorial fields requested below.

Do NOT rewrite the article.

Do NOT rewrite the title.

Do NOT rewrite content_ar.

Do NOT rewrite summary_ar.

Do NOT rewrite why_it_matters_ar.

Do NOT generate keywords.

Do NOT generate category.

Do NOT generate sentiment.

Do NOT generate impact_score.

Do NOT generate slug.

Do NOT generate meta description.

Do NOT modify healthy fields.

Use ONLY the supplied source material.

Do NOT use outside knowledge.

Do NOT invent:
- facts
- dates
- prices
- statistics
- people
- companies
- quotes
- regulations
- market movements
- blockchain data

If a conclusion is an interpretation, clearly express it as an interpretation.

Use professional Modern Standard Arabic.

Do not provide financial advice.

Do not recommend buying or selling.

Do not make guaranteed predictions.

==================================================
FIELDS TO GENERATE
==================================================

{$requestedFields}

==================================================
SOURCE MATERIAL
==================================================

{$sourceMaterial}

==================================================
QUALITY REQUIREMENTS
==================================================

analysis_ar:
Provide meaningful editorial analysis based on the supplied facts.
Explain implications and uncertainty.
Avoid generic filler.

context_ar:
Explain the background needed to understand this particular article.
Use only information supported by the supplied material.

what_to_watch_ar:
Identify realistic developments directly connected to this story.
Do not invent future events.

limitations_ar:
Explain what the source does not establish or what remains uncertain.

==================================================
OUTPUT
==================================================

Return ONLY valid JSON.

Return ONLY the requested fields.

PROMPT;

        /*
        |--------------------------------------------------------------------------
        | Dynamic response schema.
        |--------------------------------------------------------------------------
        */

        $properties = [];

        foreach ($fields as $field) {

            $properties[$field] = [
                'type' => 'string',
            ];
        }

        $schema = [
            'type' => 'object',

            'properties' => $properties,

            'required' => $fields,

            'additionalProperties' => false,
        ];

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

                'temperature' => 0.2,

                'response_mime_type' =>
                    'application/json',

                'response_schema' =>
                    $schema,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | API request
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
                | Success
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

                        Log::warning(
                            'Gemini editorial repair returned empty response',
                            [
                                'news_id' =>
                                    $news->id,
                            ]
                        );

                        return null;
                    }

                    $text = trim($text);

                    /*
                    |--------------------------------------------------------------------------
                    | Remove unexpected markdown fences.
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
                    | Decode JSON.
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
                            'Gemini editorial repair JSON error',
                            [
                                'news_id' =>
                                    $news->id,

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
                | 429 quota/rate limit
                |--------------------------------------------------------------------------
                */

                if (
                    $response->status() === 429
                ) {

                    $body = $response->json();

                    $message = data_get(
                        $body,
                        'error.message',
                        'Gemini quota exceeded.'
                    );

                    $retrySeconds =
                        $this->extractRetrySeconds(
                            $body
                        );

                    $this->error(
                        '🚫 Gemini API returned HTTP 429.'
                    );

                    $this->warn(
                        $message
                    );

                    if (
                        $retrySeconds !== null
                    ) {

                        $this->warn(
                            "Suggested retry delay: {$retrySeconds} seconds."
                        );
                    }

                    Log::warning(
                        'Gemini editorial repair quota exceeded',
                        [
                            'news_id' =>
                                $news->id,

                            'model' =>
                                self::GEMINI_MODEL,

                            'retry_seconds' =>
                                $retrySeconds,

                            'message' =>
                                $message,
                        ]
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
                | Temporary server errors.
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
                        'Temporary Gemini editorial repair error',
                        [
                            'news_id' =>
                                $news->id,

                            'status' =>
                                $response->status(),

                            'attempt' =>
                                $attempt,
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
                | Other errors.
                |--------------------------------------------------------------------------
                */

                Log::error(
                    'Gemini editorial repair HTTP error',
                    [
                        'news_id' =>
                            $news->id,

                        'status' =>
                            $response->status(),

                        'body' =>
                            $response->body(),
                    ]
                );

                return null;

            } catch (\Throwable $e) {

                Log::error(
                    'Gemini editorial repair connection error',
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
    | Field-specific instructions.
    |--------------------------------------------------------------------------
    */

    private function getFieldInstruction(
        string $field
    ): string {

        return match ($field) {

            'analysis_ar' =>
                'analysis_ar: Original Arabic editorial analysis. Minimum 300 characters. Explain why the specific facts may matter and distinguish interpretation from fact.',

            'context_ar' =>
                'context_ar: Relevant Arabic background and context derived from the supplied article. Minimum 250 characters.',

            'what_to_watch_ar' =>
                'what_to_watch_ar: Specific developments or indicators directly connected to the story that readers can monitor. Minimum 200 characters.',

            'limitations_ar' =>
                'limitations_ar: Important uncertainty, missing evidence, source limitations, or facts that cannot be established from the material. Minimum 150 characters.',

            default =>
                "{$field}: Generate a valid Arabic editorial field.",
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Validate response.
    |--------------------------------------------------------------------------
    */

    private function validateRepairResult(
        ?array $result,
        array $requestedFields
    ): array {

        $errors = [];

        if (!is_array($result)) {

            return [
                'valid' => false,

                'errors' => [
                    'Result is not an array.',
                ],
            ];
        }

        if (
            isset($result['__quota_exceeded']) ||
            isset($result['__temporary_failure'])
        ) {

            return [
                'valid' => false,

                'errors' => [
                    'Internal control result received.',
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Do not allow unexpected fields.
        |--------------------------------------------------------------------------
        */

        foreach (
            array_keys($result) as $field
        ) {

            if (
                !in_array(
                    $field,
                    $requestedFields,
                    true
                )
            ) {

                $errors[] =
                    "Unexpected field returned: {$field}";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate requested fields.
        |--------------------------------------------------------------------------
        */

        foreach ($requestedFields as $field) {

            if (
                !array_key_exists(
                    $field,
                    $result
                )
            ) {

                $errors[] =
                    "{$field}: missing";

                continue;
            }

            if (
                !is_string(
                    $result[$field]
                )
            ) {

                $errors[] =
                    "{$field}: not a string";

                continue;
            }

            $value = trim(
                $result[$field]
            );

            if ($value === '') {

                $errors[] =
                    "{$field}: empty";

                continue;
            }

            $minimum =
                $this->getMinimumLength(
                    $field
                );

            if (
                $minimum > 0 &&
                mb_strlen($value) < $minimum
            ) {

                $errors[] =
                    "{$field}: too short. Required {$minimum}, actual " .
                    mb_strlen($value);
            }
        }

        return [
            'valid' =>
                empty($errors),

            'errors' =>
                $errors,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Minimum field length.
    |--------------------------------------------------------------------------
    */

    private function getMinimumLength(
        string $field
    ): int {

        return match ($field) {

            'analysis_ar' =>
                self::MIN_ANALYSIS_AR,

            'context_ar' =>
                self::MIN_CONTEXT_AR,

            'what_to_watch_ar' =>
                self::MIN_WHAT_TO_WATCH_AR,

            'limitations_ar' =>
                self::MIN_LIMITATIONS_AR,

            default =>
                0,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Retry-After extractor.
    |--------------------------------------------------------------------------
    */

    private function extractRetrySeconds(
        array $body
    ): ?int {

        $details = data_get(
            $body,
            'error.details',
            []
        );

        if (!is_array($details)) {
            return null;
        }

        foreach ($details as $detail) {

            if (
                ($detail['@type'] ?? '') ===
                'type.googleapis.com/google.rpc.RetryInfo'
            ) {

                $retryDelay =
                    $detail['retryDelay'] ??
                    null;

                if (
                    is_string($retryDelay) &&
                    preg_match(
                        '/(\d+(?:\.\d+)?)s/',
                        $retryDelay,
                        $matches
                    )
                ) {

                    return (int) ceil(
                        (float) $matches[1]
                    );
                }
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Header.
    |--------------------------------------------------------------------------
    */

    private function printHeader(): void
    {
        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            '        AQL CRYPTO EDITORIAL REPAIR'
        );

        $this->info(
            '=============================================='
        );

        $this->line(
            'Repair target: analysis_ar'
        );

        $this->line(
            'Repair target: context_ar'
        );

        $this->line(
            'Repair target: what_to_watch_ar'
        );

        $this->line(
            'Repair target: limitations_ar'
        );

        $this->line(
            'Healthy fields will never be overwritten.'
        );

        $this->line(
            'Gemini 429 stops the cycle immediately.'
        );

        $this->newLine();

        $this->info(
            'Model: ' .
            self::GEMINI_MODEL
        );

        $this->info(
            'Batch limit: ' .
            ((int) $this->option('limit') > 0
                ? $this->option('limit')
                : self::DEFAULT_BATCH_LIMIT)
        );
    }
}