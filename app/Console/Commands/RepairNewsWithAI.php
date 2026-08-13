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

    private const GEMINI_MODEL = 'gemini-3.6-flash';

    private const BATCH_LIMIT = 3;

    private const RATE_LIMIT_SECONDS = 20;

    private const MAX_SOURCE_LENGTH = 12000;

    public function handle(): int
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            $this->error('GEMINI_API_KEY is missing.');

            return self::FAILURE;
        }

        $this->info('🔧 Starting Aql Crypto AI Repair Cycle...');
        $this->info('Model: ' . self::GEMINI_MODEL);

        /*
        |--------------------------------------------------------------------------
        | Find ONLY previously processed but incomplete articles
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

        if ($newsList->isEmpty()) {
            $this->info('✅ No incomplete processed articles found.');

            return self::SUCCESS;
        }

        $this->info(
            "🔧 Repair mode: {$newsList->count()} incomplete articles selected."
        );

        $repaired = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($newsList as $news) {

            $this->newLine();

            $this->info(
                "Processing ID {$news->id}: {$news->title_en}"
            );

            try {

                /*
                |--------------------------------------------------------------------------
                | Existing information
                |--------------------------------------------------------------------------
                */

                $titleEn = trim((string) $news->title_en);

                $contentEn = trim(
                    mb_substr(
                        (string) $news->content_en,
                        0,
                        self::MAX_SOURCE_LENGTH
                    )
                );

                $titleAr = trim((string) $news->title_ar);
                $contentAr = trim((string) $news->content_ar);
                $summaryAr = trim((string) $news->summary_ar);

                /*
                |--------------------------------------------------------------------------
                | Source material
                |--------------------------------------------------------------------------
                |
                | Repair can use already existing Arabic fields as part of the
                | factual material. This is important for old articles where
                | content_en was stored as a very short RSS description.
                |--------------------------------------------------------------------------
                */

                $sourceMaterial = implode("\n\n", array_filter([
                    'English title: ' . $titleEn,
                    'English source content: ' . $contentEn,
                    'Existing Arabic title: ' . $titleAr,
                    'Existing Arabic content: ' . $contentAr,
                    'Existing Arabic summary: ' . $summaryAr,
                    'Source: ' . ($news->source ?? ''),
                ]));

                /*
                |--------------------------------------------------------------------------
                | Minimum available material
                |--------------------------------------------------------------------------
                */

                if (mb_strlen(trim($sourceMaterial)) < 200) {

                    $this->warn(
                        "⚠️ Article {$news->id} does not contain enough factual material for safe repair."
                    );

                    Log::warning(
                        'AI repair skipped because available source material is insufficient',
                        [
                            'news_id' => $news->id,
                            'source_length' => mb_strlen($sourceMaterial),
                        ]
                    );

                    $skipped++;

                    sleep(self::RATE_LIMIT_SECONDS);

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Ask Gemini to repair ONLY missing fields
                |--------------------------------------------------------------------------
                */

                $result = $this->repairWithGemini(
                    $news,
                    $sourceMaterial,
                    $apiKey
                );

                if (!$this->isValidResult($result)) {

                    $this->error(
                        "❌ Repair result failed validation for ID {$news->id}"
                    );

                    Log::warning(
                        'AI repair result failed validation',
                        [
                            'news_id' => $news->id,
                            'result' => $result,
                        ]
                    );

                    $failed++;

                    sleep(self::RATE_LIMIT_SECONDS);

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Preserve existing fields
                |--------------------------------------------------------------------------
                |
                | VERY IMPORTANT:
                | Existing valid content is NOT overwritten.
                |
                */

                $updates = [];

                $fields = [
                    'title_ar',
                    'content_ar',
                    'summary_ar',
                    'why_it_matters_ar',
                    'analysis_ar',
                    'context_ar',
                    'what_to_watch_ar',
                    'limitations_ar',
                    'meta_description_ar',
                ];

                foreach ($fields as $field) {

                    $existing = trim((string) $news->{$field});
                    $generated = trim((string) ($result[$field] ?? ''));

                    if ($existing === '' && $generated !== '') {
                        $updates[$field] = $generated;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Other AI fields
                |--------------------------------------------------------------------------
                */

                if (
                    empty($news->sentiment) &&
                    !empty($result['sentiment'])
                ) {
                    $updates['sentiment'] = $result['sentiment'];
                }

                if (
                    empty($news->category) &&
                    !empty($result['category'])
                ) {
                    $updates['category'] = $result['category'];
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

                    $slug = Str::slug($titleEn);

                    if ($slug === '') {
                        $slug = 'news';
                    }

                    $updates['slug'] =
                        $slug . '-' . $news->id;
                }

                /*
                |--------------------------------------------------------------------------
                | Make sure meaningful fields were actually repaired
                |--------------------------------------------------------------------------
                */

                if (empty($updates)) {

                    $this->warn(
                        "⚠️ No missing fields could be safely repaired for ID {$news->id}"
                    );

                    $skipped++;

                    sleep(self::RATE_LIMIT_SECONDS);

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Save
                |--------------------------------------------------------------------------
                */

                $news->update($updates);

                $repaired++;

                $this->info(
                    "✅ AI repair saved for ID {$news->id}"
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

            sleep(self::RATE_LIMIT_SECONDS);
        }

        /*
        |--------------------------------------------------------------------------
        | Clear caches
        |--------------------------------------------------------------------------
        */

        Cache::forget('ai_market_dashboard_stats');
        Cache::forget('ai_market_impact_news');

        $this->newLine();

        $this->info('🧹 AI Market cache cleared.');

        $this->info(
            "📊 Repaired: {$repaired} | Failed: {$failed} | Skipped: {$skipped}"
        );

        $this->info(
            '🚀 Aql Crypto AI Repair Cycle Completed.'
        );

        return $failed > 0 && $repaired === 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function repairWithGemini(
        News $news,
        string $sourceMaterial,
        string $apiKey
    ): ?array {

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            self::GEMINI_MODEL,
            $apiKey
        );

        $prompt = <<<PROMPT
You are repairing an existing Arabic cryptocurrency news article for Aql Crypto.

This is NOT a new article.

Some Arabic editorial fields already exist and some are missing.

Your task is to generate ONLY information that can be safely supported by the supplied material.

IMPORTANT RULES:

1. Do not invent facts.

2. Do not use outside knowledge.

3. Do not add current market prices.

4. Do not add statistics that are not supplied.

5. Do not add people, companies, dates, quotes, regulations or events that are not present in the material.

6. Existing Arabic content is part of the supplied factual material.

7. Preserve existing meaning.

8. If the source is insufficient to support a field, write a cautious limitation rather than inventing information.

9. The article is journalism, not financial advice.

10. Do not recommend buying or selling assets.

11. Use professional Modern Standard Arabic.

12. Return ONLY valid JSON.

The goal is to complete missing editorial fields safely.

==================================================
AVAILABLE MATERIAL
==================================================

{$sourceMaterial}

==================================================
OUTPUT
==================================================

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

IMPORTANT:

If an existing Arabic title, article or summary already exists, do not rewrite it unless necessary.

For missing analytical fields, use only the supplied facts.

If there is not enough information for a detailed analysis, explicitly state the limitation instead of inventing context.

PROMPT;

        $schema = [
            'type' => 'object',

            'properties' => [

                'title_ar' => ['type' => 'string'],
                'content_ar' => ['type' => 'string'],
                'summary_ar' => ['type' => 'string'],
                'meta_description_ar' => ['type' => 'string'],
                'why_it_matters_ar' => ['type' => 'string'],
                'analysis_ar' => ['type' => 'string'],
                'context_ar' => ['type' => 'string'],
                'what_to_watch_ar' => ['type' => 'string'],
                'limitations_ar' => ['type' => 'string'],

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

        try {

            $response = Http::timeout(90)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            if (!$response->successful()) {

                Log::error(
                    'Gemini Repair API Error',
                    [
                        'news_id' => $news->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return null;
            }

            $text = data_get(
                $response->json(),
                'candidates.0.content.parts.0.text'
            );

            if (!is_string($text) || trim($text) === '') {

                Log::error(
                    'Gemini Repair returned empty response',
                    [
                        'news_id' => $news->id,
                    ]
                );

                return null;
            }

            $text = trim($text);

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

            $data = json_decode(
                trim($text),
                true
            );

            if (json_last_error() !== JSON_ERROR_NONE) {

                Log::error(
                    'Gemini Repair JSON Error',
                    [
                        'news_id' => $news->id,
                        'error' => json_last_error_msg(),
                        'response' => $text,
                    ]
                );

                return null;
            }

            return is_array($data)
                ? $data
                : null;

        } catch (\Throwable $e) {

            Log::error(
                'Gemini Repair Connection Error',
                [
                    'news_id' => $news->id,
                    'message' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    private function isValidResult(?array $result): bool
    {
        if (!is_array($result)) {
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

        foreach ($required as $field) {
            if (!array_key_exists($field, $result)) {
                return false;
            }
        }

        if (
            !in_array(
                $result['sentiment'],
                ['Bullish', 'Bearish', 'Neutral'],
                true
            )
        ) {
            return false;
        }

        if (
            !in_array(
                $result['category'],
                [
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
                true
            )
        ) {
            return false;
        }

        if (
            !is_numeric($result['impact_score']) ||
            (int) $result['impact_score'] < 1 ||
            (int) $result['impact_score'] > 10
        ) {
            return false;
        }

        if (!is_array($result['keywords'])) {
            return false;
        }

        if (
            count($result['keywords']) < 3 ||
            count($result['keywords']) > 5
        ) {
            return false;
        }

        foreach ($result['keywords'] as $keyword) {
            if (
                !is_string($keyword) ||
                trim($keyword) === ''
            ) {
                return false;
            }
        }

        return true;
    }
}