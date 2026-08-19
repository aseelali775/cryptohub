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
    protected $signature = 'news:repair-ai
                            {--limit=3 : Number of articles to inspect per run}
                            {--ids= : Comma-separated article IDs to repair only}';

    protected $description =
        'Safely repair only missing or weak AI fields without rewriting healthy article data.';

    /*
    |--------------------------------------------------------------------------
    | Gemini
    |--------------------------------------------------------------------------
    */

    private const GEMINI_MODEL = 'gemini-3.6-flash';

    /*
    |--------------------------------------------------------------------------
    | Important:
    |
    | Keep this small because Gemini free-tier request quota is limited.
    |--------------------------------------------------------------------------
    */

    private const DEFAULT_BATCH_LIMIT = 3;

    /*
    |--------------------------------------------------------------------------
    | Seconds between Gemini requests.
    |--------------------------------------------------------------------------
    */

    private const RATE_LIMIT_SECONDS = 20;

    /*
    |--------------------------------------------------------------------------
    | Retry configuration
    |
    | 429 is NEVER retried.
    | Only temporary server/network errors may retry once.
    |--------------------------------------------------------------------------
    */

    private const MAX_API_RETRIES = 1;

    private const RETRY_BASE_SECONDS = 5;

    /*
    |--------------------------------------------------------------------------
    | Maximum source content sent to Gemini.
    |--------------------------------------------------------------------------
    */

    private const MAX_SOURCE_LENGTH = 12000;

    /*
    |--------------------------------------------------------------------------
    | Quality thresholds
    |--------------------------------------------------------------------------
    */

    private const MIN_TITLE_AR = 40;

    private const MIN_CONTENT_AR = 400;

    private const MIN_SUMMARY_AR = 100;

    private const MIN_WHY_IT_MATTERS_AR = 150;

    private const MIN_ANALYSIS_AR = 300;

    private const MIN_CONTEXT_AR = 250;

    private const MIN_WHAT_TO_WATCH_AR = 200;

    private const MIN_LIMITATIONS_AR = 150;

    private const MIN_KEYWORDS = 3;

    private const MAX_KEYWORDS = 5;

    /*
    |--------------------------------------------------------------------------
    | Allowed AI categories.
    |--------------------------------------------------------------------------
    */

    private array $allowedCategories = [
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

    /*
    |--------------------------------------------------------------------------
    | Main handler
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
                'News repair aborted because Gemini API key is missing.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Select articles
        |--------------------------------------------------------------------------
        */

        $newsList = $this->getRepairableNews();

        if ($newsList->isEmpty()) {

            $this->info(
                '✅ No repairable news articles found.'
            );

            return self::SUCCESS;
        }

        $this->info(
            "Found {$newsList->count()} article(s) requiring repair."
        );

        $this->newLine();

        $repaired = 0;

        $failed = 0;

        $skipped = 0;

        $apiRequests = 0;

        $localRepairs = 0;

        $quotaExceeded = false;

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
                | Determine exactly what is wrong.
                |--------------------------------------------------------------------------
                */

                $repairPlan = $this->buildRepairPlan(
                    $news
                );

                /*
                |--------------------------------------------------------------------------
                | Nothing to repair.
                |--------------------------------------------------------------------------
                */

                if (
                    empty($repairPlan['ai_fields']) &&
                    empty($repairPlan['local_fields'])
                ) {

                    $this->line(
                        '✅ Article already satisfies repair rules.'
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Display diagnosis.
                |--------------------------------------------------------------------------
                */

                if (
                    !empty($repairPlan['ai_fields'])
                ) {

                    $this->line(
                        '🤖 AI fields: ' .
                        implode(
                            ', ',
                            $repairPlan['ai_fields']
                        )
                    );
                }

                if (
                    !empty($repairPlan['local_fields'])
                ) {

                    $this->line(
                        '🔧 Local fields: ' .
                        implode(
                            ', ',
                            $repairPlan['local_fields']
                        )
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Local repairs first.
                |
                | These do NOT consume Gemini quota.
                |--------------------------------------------------------------------------
                */

                $updates = [];

                /*
                |----------------------------------------------------------------------
                | Slug
                |----------------------------------------------------------------------
                */

                if (
                    in_array(
                        'slug',
                        $repairPlan['local_fields'],
                        true
                    )
                ) {

                    $slug = $this->buildSlug(
                        $news->title_en,
                        $news->id
                    );

                    if (
                        $slug !== '' &&
                        empty($news->slug)
                    ) {

                        $updates['slug'] = $slug;
                    }
                }

                /*
                |----------------------------------------------------------------------
                | Keywords
                |
                | Only local repair when keywords are the only AI-type
                | problem. This protects Gemini quota.
                |----------------------------------------------------------------------
                */

                if (
                    in_array(
                        'keywords',
                        $repairPlan['local_fields'],
                        true
                    )
                ) {

                    $keywords = $this->buildLocalKeywords(
                        $news
                    );

                    if (
                        count($keywords) >= self::MIN_KEYWORDS
                    ) {

                        $updates['keywords'] = $keywords;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Normalize invalid metadata locally where safe.
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        'sentiment',
                        $repairPlan['local_fields'],
                        true
                    )
                ) {

                    /*
                     * Neutral is the safest fallback when no reliable
                     * sentiment value exists.
                     */

                    $updates['sentiment'] = 'Neutral';
                }

                if (
                    in_array(
                        'impact_score',
                        $repairPlan['local_fields'],
                        true
                    )
                ) {

                    $updates['impact_score'] = 5;
                }

                /*
                |--------------------------------------------------------------------------
                | Category:
                |
                | Only use a local fallback if the existing value is empty.
                | Invalid existing categories are sent to Gemini.
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        'category_local',
                        $repairPlan['local_fields'],
                        true
                    )
                ) {

                    $updates['category'] = 'Market';
                }

                /*
                |--------------------------------------------------------------------------
                | Save local repairs before calling Gemini.
                |--------------------------------------------------------------------------
                */

                if (!empty($updates)) {

                    $news->update(
                        $updates
                    );

                    $localRepairs += count(
                        $updates
                    );

                    $this->line(
                        '🔧 Local repairs saved: ' .
                        implode(
                            ', ',
                            array_keys($updates)
                        )
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Refresh model after local update.
                |--------------------------------------------------------------------------
                */

                $news->refresh();

                /*
                |--------------------------------------------------------------------------
                | If AI is not needed, finish here.
                |--------------------------------------------------------------------------
                */

                if (
                    empty($repairPlan['ai_fields'])
                ) {

                    $this->info(
                        "✅ Local repair completed for ID {$news->id}"
                    );

                    $repaired++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Build source material.
                |--------------------------------------------------------------------------
                */

                $sourceMaterial =
                    $this->buildSourceMaterial(
                        $news
                    );

                if (
                    mb_strlen(
                        trim($sourceMaterial)
                    ) < 200
                ) {

                    $this->warn(
                        '⚠️ Not enough factual material for safe AI repair.'
                    );

                    Log::warning(
                        'AI repair skipped because source material is insufficient',
                        [
                            'news_id' => $news->id,
                            'source_length' =>
                                mb_strlen(
                                    $sourceMaterial
                                ),
                            'ai_fields' =>
                                $repairPlan['ai_fields'],
                        ]
                    );

                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Request Gemini.
                |--------------------------------------------------------------------------
                */

                $apiRequests++;

                $result = $this->repairWithGemini(
                    $news,
                    $sourceMaterial,
                    $repairPlan['ai_fields'],
                    $apiKey
                );

                /*
                |--------------------------------------------------------------------------
                | Quota exhausted.
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
                        'Processing cycle stopped immediately.'
                    );

                    Log::warning(
                        'AI repair cycle stopped because Gemini quota was exceeded',
                        [
                            'news_id' =>
                                $news->id,
                            'model' =>
                                self::GEMINI_MODEL,
                            'retry_seconds' =>
                                $result['__retry_seconds'] ?? null,
                        ]
                    );

                    $failed++;

                    break;
                }

                /*
                |--------------------------------------------------------------------------
                | Temporary failure.
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
                | Validate generated response.
                |--------------------------------------------------------------------------
                */

                $validation =
                    $this->validateRepairResult(
                        $result,
                        $repairPlan['ai_fields']
                    );

                if (!$validation['valid']) {

                    $this->error(
                        "❌ Repair validation failed for ID {$news->id}"
                    );

                    Log::warning(
                        'AI repair validation failed',
                        [
                            'news_id' =>
                                $news->id,
                            'fields' =>
                                $repairPlan['ai_fields'],
                            'errors' =>
                                $validation['errors'],
                            'result' =>
                                $result,
                        ]
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Do not save anything from an invalid result.
                    |--------------------------------------------------------------------------
                    */

                    $failed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Apply ONLY fields that:
                |
                | 1. Were requested.
                | 2. Are currently missing/weak.
                | 3. Passed field-specific validation.
                |--------------------------------------------------------------------------
                */

                $aiUpdates = [];

                foreach (
                    $repairPlan['ai_fields']
                    as $field
                ) {

                    $currentValue =
                        trim(
                            (string)
                            $news->{$field}
                        );

                    $generatedValue =
                        trim(
                            (string)
                            ($result[$field] ?? '')
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Never overwrite healthy existing data.
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $this->fieldNeedsRepair(
                            $news,
                            $field
                        ) &&
                        $generatedValue !== ''
                    ) {

                        $aiUpdates[$field] =
                            $generatedValue;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Additional AI metadata.
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        'category',
                        $repairPlan['ai_fields'],
                        true
                    ) &&
                    isset($result['category']) &&
                    $this->isValidCategory(
                        $result['category']
                    ) &&
                    $this->fieldNeedsRepair(
                        $news,
                        'category'
                    )
                ) {

                    $aiUpdates['category'] =
                        $result['category'];
                }

                if (
                    in_array(
                        'sentiment',
                        $repairPlan['ai_fields'],
                        true
                    ) &&
                    isset($result['sentiment']) &&
                    $this->isValidSentiment(
                        $result['sentiment']
                    ) &&
                    $this->fieldNeedsRepair(
                        $news,
                        'sentiment'
                    )
                ) {

                    $aiUpdates['sentiment'] =
                        $result['sentiment'];
                }

                if (
                    in_array(
                        'impact_score',
                        $repairPlan['ai_fields'],
                        true
                    ) &&
                    isset($result['impact_score']) &&
                    $this->isValidImpactScore(
                        $result['impact_score']
                    ) &&
                    $this->fieldNeedsRepair(
                        $news,
                        'impact_score'
                    )
                ) {

                    $aiUpdates['impact_score'] =
                        (int)
                        $result['impact_score'];
                }

                /*
                |--------------------------------------------------------------------------
                | Keywords from Gemini if local repair did not solve them.
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        'keywords',
                        $repairPlan['ai_fields'],
                        true
                    ) &&
                    isset($result['keywords']) &&
                    $this->fieldNeedsRepair(
                        $news,
                        'keywords'
                    )
                ) {

                    $keywords =
                        $this->normalizeKeywords(
                            $result['keywords']
                        );

                    if (
                        count($keywords) >=
                        self::MIN_KEYWORDS
                    ) {

                        $aiUpdates['keywords'] =
                            $keywords;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Safety:
                | Never save AI updates into fields that became healthy
                | while the request was running.
                |--------------------------------------------------------------------------
                */

                $safeUpdates = [];

                foreach (
                    $aiUpdates as $field => $value
                ) {

                    if (
                        $this->fieldNeedsRepair(
                            $news,
                            $field
                        )
                    ) {

                        $safeUpdates[$field] =
                            $value;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Save AI repair.
                |--------------------------------------------------------------------------
                */

                if (!empty($safeUpdates)) {

                    $news->update(
                        $safeUpdates
                    );

                    $repaired++;

                    $this->info(
                        "✅ AI repair saved for ID {$news->id}"
                    );

                    $this->line(
                        'Updated fields: ' .
                        implode(
                            ', ',
                            array_keys(
                                $safeUpdates
                            )
                        )
                    );

                    foreach (
                        $safeUpdates as $field => $value
                    ) {

                        if (
                            is_string($value)
                        ) {

                            $this->line(
                                "{$field}: " .
                                mb_strlen(
                                    trim($value)
                                ) .
                                ' chars'
                            );
                        }
                    }
                } else {

                    $this->warn(
                        "⚠️ Gemini returned no safely repairable fields for ID {$news->id}"
                    );

                    $skipped++;
                }

            } catch (\Throwable $e) {

                $failed++;

                $this->error(
                    "❌ Repair failed for ID {$news->id}: {$e->getMessage()}"
                );

                Log::error(
                    'AI News Repair Exception',
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
            | Delay only after an actual Gemini request.
            |--------------------------------------------------------------------------
            */

            if (
                !$quotaExceeded &&
                $apiRequests > 0
            ) {

                sleep(
                    self::RATE_LIMIT_SECONDS
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Clear caches only if changes were made.
        |--------------------------------------------------------------------------
        */

        if (
            $repaired > 0 ||
            $localRepairs > 0
        ) {

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
            'AQL CRYPTO AI REPAIR COMPLETED'
        );

        $this->info(
            '=============================================='
        );

        $this->line(
            "Repaired articles : {$repaired}"
        );

        $this->line(
            "Local fixes        : {$localRepairs}"
        );

        $this->line(
            "Gemini requests    : {$apiRequests}"
        );

        $this->line(
            "Failed             : {$failed}"
        );

        $this->line(
            "Skipped            : {$skipped}"
        );

        if ($quotaExceeded) {

            $this->warn(
                'Gemini quota was reached and the cycle was stopped safely.'
            );
        }

        $this->newLine();

        $this->comment(
            'Existing healthy article fields were not overwritten.'
        );

        /*
        |--------------------------------------------------------------------------
        | Exit code
        |--------------------------------------------------------------------------
        */

        return (
            $failed > 0 &&
            $repaired === 0
        )
            ? self::FAILURE
            : self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Select repairable articles
    |--------------------------------------------------------------------------
    */

    private function getRepairableNews()
    {
        $query =
            News::query()
                ->where(
                    'ai_processed',
                    true
                )
                ->orderBy('id');

        /*
        |--------------------------------------------------------------------------
        | Specific IDs
        |--------------------------------------------------------------------------
        */

        $ids =
            trim(
                (string)
                $this->option('ids')
            );

        if ($ids !== '') {

            $idList =
                collect(
                    explode(
                        ',',
                        $ids
                    )
                )
                    ->map(
                        fn ($id) =>
                            (int)
                            trim($id)
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

        $limit =
            (int)
            $this->option('limit');

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
    | Build repair plan
    |--------------------------------------------------------------------------
    */

    private function buildRepairPlan(
        News $news
    ): array {

        $aiFields = [];

        $localFields = [];

        /*
        |--------------------------------------------------------------------------
        | Title
        |--------------------------------------------------------------------------
        */

        if (
            $this->fieldNeedsRepair(
                $news,
                'title_ar'
            )
        ) {

            $aiFields[] =
                'title_ar';
        }

        /*
        |--------------------------------------------------------------------------
        | Arabic article
        |--------------------------------------------------------------------------
        */

        if (
            $this->fieldNeedsRepair(
                $news,
                'content_ar'
            )
        ) {

            $aiFields[] =
                'content_ar';
        }

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        if (
            $this->fieldNeedsRepair(
                $news,
                'summary_ar'
            )
        ) {

            $aiFields[] =
                'summary_ar';
        }

        /*
        |--------------------------------------------------------------------------
        | Why it matters
        |--------------------------------------------------------------------------
        */

        if (
            $this->fieldNeedsRepair(
                $news,
                'why_it_matters_ar'
            )
        ) {

            $aiFields[] =
                'why_it_matters_ar';
        }

        /*
        |--------------------------------------------------------------------------
        | Analysis
        |--------------------------------------------------------------------------
        */

        if (
            $this->fieldNeedsRepair(
                $news,
                'analysis_ar'
            )
        ) {

            $aiFields[] =
                'analysis_ar';
        }

        /*
        |--------------------------------------------------------------------------
        | Context
        |--------------------------------------------------------------------------
        */

        if (
            $this->fieldNeedsRepair(
                $news,
                'context_ar'
            )
        ) {

            $aiFields[] =
                'context_ar';
        }

        /*
        |--------------------------------------------------------------------------
        | What to watch
        |--------------------------------------------------------------------------
        */

        if (
            $this->fieldNeedsRepair(
                $news,
                'what_to_watch_ar'
            )
        ) {

            $aiFields[] =
                'what_to_watch_ar';
        }

        /*
        |--------------------------------------------------------------------------
        | Limitations
        |--------------------------------------------------------------------------
        */

        if (
            $this->fieldNeedsRepair(
                $news,
                'limitations_ar'
            )
        ) {

            $aiFields[] =
                'limitations_ar';
        }

        /*
        |--------------------------------------------------------------------------
        | Keywords
        |
        | If article already has all editorial fields healthy,
        | local repair is preferred.
        |--------------------------------------------------------------------------
        */

        if (
            $this->keywordsNeedRepair(
                $news
            )
        ) {

            if (
                empty($aiFields)
            ) {

                $localFields[] =
                    'keywords';

            } else {

                $aiFields[] =
                    'keywords';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        $category =
            trim(
                (string)
                $news->category
            );

        if ($category === '') {

            /*
             * Missing category can safely receive a neutral
             * default without Gemini.
             */

            $localFields[] =
                'category_local';

        } elseif (
            !$this->isValidCategory(
                $category
            )
        ) {

            /*
             * Invalid category should be decided by AI
             * from the source material.
             */

            $aiFields[] =
                'category';
        }

        /*
        |--------------------------------------------------------------------------
        | Sentiment
        |--------------------------------------------------------------------------
        */

        $sentiment =
            trim(
                (string)
                $news->sentiment
            );

        if ($sentiment === '') {

            $aiFields[] =
                'sentiment';

        } elseif (
            !$this->isValidSentiment(
                $sentiment
            )
        ) {

            $aiFields[] =
                'sentiment';
        }

        /*
        |--------------------------------------------------------------------------
        | Impact
        |--------------------------------------------------------------------------
        */

        $impact =
            $news->impact_score;

        if (
            $impact === null ||
            $impact === '' ||
            !is_numeric($impact) ||
            (int) $impact < 1 ||
            (int) $impact > 10
        ) {

            $aiFields[] =
                'impact_score';
        }

        /*
        |--------------------------------------------------------------------------
        | Slug
        |
        | Always local.
        |--------------------------------------------------------------------------
        */

        if (
            trim(
                (string)
                $news->slug
            ) === ''
        ) {

            $localFields[] =
                'slug';
        }

        return [
            'ai_fields' =>
                array_values(
                    array_unique(
                        $aiFields
                    )
                ),

            'local_fields' =>
                array_values(
                    array_unique(
                        $localFields
                    )
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Build source material
    |--------------------------------------------------------------------------
    */

    private function buildSourceMaterial(
        News $news
    ): string {

        $titleEn =
            trim(
                (string)
                $news->title_en
            );

        $contentEn =
            trim(
                mb_substr(
                    (string)
                    $news->content_en,
                    0,
                    self::MAX_SOURCE_LENGTH
                )
            );

        $titleAr =
            trim(
                (string)
                $news->title_ar
            );

        $contentAr =
            trim(
                (string)
                $news->content_ar
            );

        $summaryAr =
            trim(
                (string)
                $news->summary_ar
            );

        $whyAr =
            trim(
                (string)
                $news->why_it_matters_ar
            );

        $analysisAr =
            trim(
                (string)
                $news->analysis_ar
            );

        $contextAr =
            trim(
                (string)
                $news->context_ar
            );

        $watchAr =
            trim(
                (string)
                $news->what_to_watch_ar
            );

        $limitationsAr =
            trim(
                (string)
                $news->limitations_ar
            );

        return implode(
            "\n\n",
            array_filter([
                'SOURCE: ' .
                    (string)
                    ($news->source ?? ''),

                'URL: ' .
                    (string)
                    ($news->url ?? ''),

                'TITLE EN: ' .
                    $titleEn,

                'ORIGINAL ENGLISH ARTICLE: ' .
                    $contentEn,

                'EXISTING ARABIC TITLE: ' .
                    $titleAr,

                'EXISTING ARABIC ARTICLE: ' .
                    $contentAr,

                'EXISTING SUMMARY: ' .
                    $summaryAr,

                'EXISTING WHY IT MATTERS: ' .
                    $whyAr,

                'EXISTING ANALYSIS: ' .
                    $analysisAr,

                'EXISTING CONTEXT: ' .
                    $contextAr,

                'EXISTING WHAT TO WATCH: ' .
                    $watchAr,

                'EXISTING LIMITATIONS: ' .
                    $limitationsAr,
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Gemini repair
    |--------------------------------------------------------------------------
    */

    private function repairWithGemini(
        News $news,
        string $sourceMaterial,
        array $missingFields,
        string $apiKey
    ): ?array {

        $url =
            sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                self::GEMINI_MODEL,
                $apiKey
            );

        $fieldDescriptions = [];

        foreach (
            $missingFields as $field
        ) {

            $fieldDescriptions[] =
                $this->getFieldInstruction(
                    $field
                );
        }

        $fieldsText =
            implode(
                "\n\n",
                $fieldDescriptions
            );

        $prompt = <<<PROMPT
You are the senior repair editor for Aql Crypto.

You are NOT creating a new article.

You are repairing ONLY the missing or weak fields listed below.

CRITICAL RULE:

Do NOT rewrite healthy existing fields.

Do NOT return fields that were not requested.

Do NOT invent facts.

Do NOT use outside knowledge.

Do NOT use current market information unless it exists in the supplied material.

Do NOT invent dates, prices, statistics, people, companies, regulations or events.

Use ONLY the supplied source material and existing article information.

The supplied existing Arabic fields are factual editorial material and may be used as context.

The original English article is the primary source.

==================================================
FIELDS TO REPAIR
==================================================

{$fieldsText}

==================================================
SOURCE MATERIAL
==================================================

{$sourceMaterial}

==================================================
EDITORIAL RULES
==================================================

Write professional Modern Standard Arabic.

Do not translate sentence by sentence.

Do not copy the source article.

Do not introduce facts not contained in the source.

Preserve all factual numbers and names accurately.

When interpretation is necessary, clearly use cautious language such as:

"قد يشير ذلك إلى"

"قد يعكس"

"من المحتمل أن"

"يمكن أن يعني"

"لا يمكن الجزم بأن"

Do not give investment advice.

Do not recommend buying or selling.

Do not make guaranteed price predictions.

Do not create unsupported market forecasts.

==================================================
OUTPUT
==================================================

Return ONLY valid JSON.

Return ONLY the requested fields.

PROMPT;

        /*
        |--------------------------------------------------------------------------
        | Dynamic schema
        |--------------------------------------------------------------------------
        */

        $properties = [];

        foreach (
            $missingFields as $field
        ) {

            switch ($field) {

                case 'keywords':

                    $properties[$field] = [
                        'type' =>
                            'array',

                        'items' => [
                            'type' =>
                                'string',
                        ],
                    ];

                    break;

                case 'category':

                    $properties[$field] = [
                        'type' =>
                            'string',

                        'enum' =>
                            $this->allowedCategories,
                    ];

                    break;

                case 'sentiment':

                    $properties[$field] = [
                        'type' =>
                            'string',

                        'enum' => [
                            'Bullish',
                            'Bearish',
                            'Neutral',
                        ],
                    ];

                    break;

                case 'impact_score':

                    $properties[$field] = [
                        'type' =>
                            'integer',
                    ];

                    break;

                default:

                    $properties[$field] = [
                        'type' =>
                            'string',
                    ];

                    break;
            }
        }

        $schema = [
            'type' =>
                'object',

            'properties' =>
                $properties,

            'required' =>
                $missingFields,

            'additionalProperties' =>
                false,
        ];

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
                    0.2,

                'response_mime_type' =>
                    'application/json',

                'response_schema' =>
                    $schema,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Request
        |--------------------------------------------------------------------------
        */

        for (
            $attempt = 1;
            $attempt <= self::MAX_API_RETRIES + 1;
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
                | Success
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

                        Log::warning(
                            'Gemini repair returned empty response',
                            [
                                'news_id' =>
                                    $news->id,
                            ]
                        );

                        return null;
                    }

                    $text =
                        trim($text);

                    /*
                    |--------------------------------------------------------------------------
                    | Defensive cleanup
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
                        trim($text);

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
                            'Gemini repair JSON decode failed',
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
                | 429
                |
                | NEVER retry automatically.
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
                        'Gemini repair quota exceeded',
                        [
                            'news_id' =>
                                $news->id,

                            'model' =>
                                self::GEMINI_MODEL,

                            'retry_seconds' =>
                                $retrySeconds,

                            'message' =>
                                $message,

                            'body' =>
                                $response->body(),
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
                        'Temporary Gemini repair API error',
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
                | Other HTTP errors
                |--------------------------------------------------------------------------
                */

                Log::error(
                    'Gemini repair HTTP error',
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
                    'Gemini repair connection error',
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
    | Field instructions
    |--------------------------------------------------------------------------
    */

    private function getFieldInstruction(
        string $field
    ): string {

        return match ($field) {

            'title_ar' =>
                'title_ar: Arabic SEO headline, minimum 40 characters, factual and concise.',

            'content_ar' =>
                'content_ar: Complete Arabic article based only on the supplied source, minimum 400 characters. Do not invent information.',

            'summary_ar' =>
                'summary_ar: Factual Arabic summary, minimum 100 characters.',

            'why_it_matters_ar' =>
                'why_it_matters_ar: Explain why this specific event matters, minimum 150 characters. Do not use generic filler.',

            'analysis_ar' =>
                'analysis_ar: Original editorial analysis based only on supplied facts, minimum 300 characters. Clearly distinguish interpretation from fact.',

            'context_ar' =>
                'context_ar: Explain relevant background supported by the supplied material, minimum 250 characters.',

            'what_to_watch_ar' =>
                'what_to_watch_ar: Specific future developments or indicators directly connected to this story, minimum 200 characters.',

            'limitations_ar' =>
                'limitations_ar: Explain missing information, uncertainty or source limitations, minimum 150 characters.',

            'keywords' =>
                'keywords: 3 to 5 accurate English keywords directly related to this article.',

            'category' =>
                'category: Select exactly one allowed category that best matches the article.',

            'sentiment' =>
                'sentiment: Bullish, Bearish or Neutral based only on the specific event described.',

            'impact_score' =>
                'impact_score: Integer from 1 to 10 reflecting the significance of this article to the crypto ecosystem.',

            default =>
                "{$field}: Generate a valid value based strictly on supplied information.",
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Validate repair result
    |--------------------------------------------------------------------------
    */

    private function validateRepairResult(
        ?array $result,
        array $requestedFields
    ): array {

        $errors = [];

        if (
            !is_array($result)
        ) {

            return [
                'valid' =>
                    false,

                'errors' =>
                    [
                        'Result is not an array.',
                    ],
            ];
        }

        if (
            isset($result['__quota_exceeded']) ||
            isset($result['__temporary_failure'])
        ) {

            return [
                'valid' =>
                    false,

                'errors' =>
                    [
                        'Internal control result received.',
                    ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validate requested fields
        |--------------------------------------------------------------------------
        */

        foreach (
            $requestedFields as $field
        ) {

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
                $field === 'keywords'
            ) {

                $keywords =
                    $this->normalizeKeywords(
                        $result[$field]
                    );

                if (
                    count($keywords) <
                    self::MIN_KEYWORDS ||
                    count($keywords) >
                    self::MAX_KEYWORDS
                ) {

                    $errors[] =
                        'keywords: invalid count';
                }

                continue;
            }

            if (
                $field === 'category'
            ) {

                if (
                    !$this->isValidCategory(
                        $result[$field]
                    )
                ) {

                    $errors[] =
                        'category: invalid value';
                }

                continue;
            }

            if (
                $field === 'sentiment'
            ) {

                if (
                    !$this->isValidSentiment(
                        $result[$field]
                    )
                ) {

                    $errors[] =
                        'sentiment: invalid value';
                }

                continue;
            }

            if (
                $field === 'impact_score'
            ) {

                if (
                    !$this->isValidImpactScore(
                        $result[$field]
                    )
                ) {

                    $errors[] =
                        'impact_score: invalid value';
                }

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

            $value =
                trim(
                    $result[$field]
                );

            if (
                $value === ''
            ) {

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
                mb_strlen($value) <
                $minimum
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
    | Minimum length for every field
    |--------------------------------------------------------------------------
    */

    private function getMinimumLength(
        string $field
    ): int {

        return match ($field) {

            'title_ar' =>
                self::MIN_TITLE_AR,

            'content_ar' =>
                self::MIN_CONTENT_AR,

            'summary_ar' =>
                self::MIN_SUMMARY_AR,

            'why_it_matters_ar' =>
                self::MIN_WHY_IT_MATTERS_AR,

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
    | Determine if field really needs repair
    |--------------------------------------------------------------------------
    */

    private function fieldNeedsRepair(
        News $news,
        string $field
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Standard text fields
        |--------------------------------------------------------------------------
        */

        $text =
            trim(
                (string)
                $news->{$field}
            );

        return match ($field) {

            'title_ar' =>
                $text === '' ||
                mb_strlen($text) <
                self::MIN_TITLE_AR,

            'content_ar' =>
                $text === '' ||
                mb_strlen($text) <
                self::MIN_CONTENT_AR,

            'summary_ar' =>
                $text === '' ||
                mb_strlen($text) <
                self::MIN_SUMMARY_AR,

            'why_it_matters_ar' =>
                $text === '' ||
                mb_strlen($text) <
                self::MIN_WHY_IT_MATTERS_AR,

            'analysis_ar' =>
                $text === '' ||
                mb_strlen($text) <
                self::MIN_ANALYSIS_AR,

            'context_ar' =>
                $text === '' ||
                mb_strlen($text) <
                self::MIN_CONTEXT_AR,

            'what_to_watch_ar' =>
                $text === '' ||
                mb_strlen($text) <
                self::MIN_WHAT_TO_WATCH_AR,

            'limitations_ar' =>
                $text === '' ||
                mb_strlen($text) <
                self::MIN_LIMITATIONS_AR,

            'category' =>
                $text === '' ||
                !$this->isValidCategory($text),

            'sentiment' =>
                $text === '' ||
                !$this->isValidSentiment($text),

            'impact_score' =>
                $text === '' ||
                !$this->isValidImpactScore(
                    $news->{$field}
                ),

            'keywords' =>
                $this->keywordsNeedRepair($news),

            default =>
                $text === '',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Keywords repair check
    |--------------------------------------------------------------------------
    */

    private function keywordsNeedRepair(
        News $news
    ): bool {

        $keywords =
            $this->decodeKeywords(
                $news->keywords
            );

        if (
            !is_array($keywords)
        ) {

            return true;
        }

        $keywords =
            $this->normalizeKeywords(
                $keywords
            );

        return count($keywords) <
            self::MIN_KEYWORDS;
    }

    /*
    |--------------------------------------------------------------------------
    | Decode keywords safely
    |--------------------------------------------------------------------------
    */

    private function decodeKeywords(
        mixed $value
    ): array {

        if (
            is_array($value)
        ) {

            return $value;
        }

        if (
            is_string($value)
        ) {

            $decoded =
                json_decode(
                    $value,
                    true
                );

            if (
                is_array($decoded)
            ) {

                return $decoded;
            }
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize keywords
    |--------------------------------------------------------------------------
    */

    private function normalizeKeywords(
        mixed $keywords
    ): array {

        if (
            !is_array($keywords)
        ) {

            return [];
        }

        return collect(
            $keywords
        )
            ->map(
                fn ($keyword) =>
                    trim(
                        (string)
                        $keyword
                    )
            )
            ->filter()
            ->map(
                fn ($keyword) =>
                    preg_replace(
                        '/\s+/u',
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
            ->take(
                self::MAX_KEYWORDS
            )
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Local keyword generator
    |
    | Used only when keywords are the only remaining problem.
    | This saves Gemini requests.
    |--------------------------------------------------------------------------
    */

    private function buildLocalKeywords(
        News $news
    ): array {

        $keywords = [];

        $text = implode(
            ' ',
            [
                (string)
                $news->title_en,

                (string)
                $news->title_ar,

                (string)
                $news->content_en,
            ]
        );

        $knownTerms = [
            'Bitcoin',
            'BTC',
            'Ethereum',
            'ETH',
            'Solana',
            'SOL',
            'XRP',
            'Ripple',
            'BNB',
            'Bybit',
            'Coinbase',
            'Binance',
            'Kraken',
            'Tether',
            'USDT',
            'USDC',
            'DeFi',
            'NFT',
            'Stablecoin',
            'Mining',
            'ETF',
            'Regulation',
            'Security',
            'Blockchain',
            'AI',
            'Artificial Intelligence',
            'Tokenization',
            'RWA',
            'Ethereum',
            'Avalanche',
            'Polkadot',
            'Cardano',
            'ADA',
            'DOT',
            'TRON',
            'TRX',
            'Chainlink',
            'LINK',
            'Hyperliquid',
            'HYPE',
            'Polymarket',
            'Kalshi',
            'Strategy',
            'MSTR',
            'BlackRock',
            'Bitget',
            'Coldcard',
            'CryptoQuant',
            'Robinhood',
            'Meta',
            'OpenAI',
        ];

        foreach (
            $knownTerms as $term
        ) {

            if (
                stripos(
                    $text,
                    $term
                ) !== false
            ) {

                $keywords[] =
                    $term;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Add category when valid.
        |--------------------------------------------------------------------------
        */

        if (
            $this->isValidCategory(
                $news->category
            )
        ) {

            $keywords[] =
                $news->category;
        }

        $keywords =
            $this->normalizeKeywords(
                $keywords
            );

        /*
        |--------------------------------------------------------------------------
        | Ensure at least 3 keywords.
        |--------------------------------------------------------------------------
        */

        if (
            count($keywords) < 3
        ) {

            $fallbacks = [
                'Crypto',
                'Digital Assets',
                'Blockchain',
                'Cryptocurrency',
                'Crypto Market',
            ];

            foreach (
                $fallbacks as $fallback
            ) {

                $keywords[] =
                    $fallback;

                $keywords =
                    $this->normalizeKeywords(
                        $keywords
                    );

                if (
                    count($keywords) >=
                    self::MIN_KEYWORDS
                ) {

                    break;
                }
            }
        }

        return array_slice(
            $keywords,
            0,
            self::MAX_KEYWORDS
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Category
    |--------------------------------------------------------------------------
    */

    private function isValidCategory(
        mixed $category
    ): bool {

        return is_string($category)
            &&
            in_array(
                trim($category),
                $this->allowedCategories,
                true
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Sentiment
    |--------------------------------------------------------------------------
    */

    private function isValidSentiment(
        mixed $sentiment
    ): bool {

        return is_string($sentiment)
            &&
            in_array(
                trim($sentiment),
                [
                    'Bullish',
                    'Bearish',
                    'Neutral',
                ],
                true
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Impact score
    |--------------------------------------------------------------------------
    */

    private function isValidImpactScore(
        mixed $score
    ): bool {

        if (
            !is_numeric($score)
        ) {

            return false;
        }

        $score =
            (int)
            $score;

        return $score >= 1 &&
            $score <= 10;
    }

    /*
    |--------------------------------------------------------------------------
    | Slug
    |--------------------------------------------------------------------------
    */

    private function buildSlug(
        string $title,
        int|string $id
    ): string {

        $slug =
            Str::slug(
                trim($title)
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
    | Retry seconds extractor
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
                ($detail['@type'] ?? '') ===
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

                    if (
                        preg_match(
                            '/(\d+(?:\.\d+)?)s/',
                            $retryDelay,
                            $matches
                        )
                    ) {

                        return
                            (int)
                            ceil(
                                (float)
                                $matches[1]
                            );
                    }
                }
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    private function printHeader(): void
    {
        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            '        AQL CRYPTO AI REPAIR'
        );

        $this->info(
            '=============================================='
        );

        $this->line(
            'Only missing or weak fields will be repaired.'
        );

        $this->line(
            'Healthy existing fields will never be rewritten.'
        );

        $this->line(
            'Gemini 429 will stop the cycle immediately.'
        );

        $this->line(
            'Local fixes do not consume Gemini requests.'
        );

        $this->newLine();

        $this->info(
            'Model: ' .
            self::GEMINI_MODEL
        );

        $this->info(
            'Default batch: ' .
            self::DEFAULT_BATCH_LIMIT
        );
    }
}