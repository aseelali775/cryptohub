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
    protected $signature = 'news:process-ai';

    protected $description = 'Analyze crypto news and generate original Arabic editorial analysis using Gemini AI.';

    /**
     * Gemini model.
     *
     * Gemini 2.5 Flash is currently a stable model suitable
     * for high-volume text processing and structured JSON output.
     */
   private const GEMINI_MODEL = 'gemini-3.6-flash';

    /**
     * Maximum number of articles processed in one command run.
     */
    private const BATCH_LIMIT = 3;

    /**
     * Seconds between requests.
     */
    private const RATE_LIMIT_SECONDS = 20;

    /**
     * Maximum source content sent to Gemini.
     */
    private const MAX_SOURCE_LENGTH = 12000;

    public function handle(): int
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            $this->error('GEMINI_API_KEY is missing.');

            Log::error('Gemini API Key missing.');

            return Command::FAILURE;
        }

        $this->info('Starting Aql Crypto AI Editorial Analysis...');
        $this->info('Model: ' . self::GEMINI_MODEL);

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
            ->limit(self::BATCH_LIMIT)
            ->get();

        if ($newsList->isEmpty()) {
            $this->info('No new articles.');

            return Command::SUCCESS;
        }

        $processed = 0;
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
                | Prepare source material
                |--------------------------------------------------------------------------
                */

                $title = trim((string) $news->title_en);

                $content = trim(
                    mb_substr(
                        (string) $news->content_en,
                        0,
                        self::MAX_SOURCE_LENGTH
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Validate source
                |--------------------------------------------------------------------------
                */

                if (mb_strlen($title) < 5) {
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

                if (mb_strlen($content) < 200) {
                    $this->warn(
                        "⚠️ Article {$news->id} is too short. Skipping."
                    );

                    Log::warning(
                        'AI skipped short article',
                        [
                            'news_id' => $news->id,
                            'content_length' => mb_strlen($content),
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
                | Validate AI result
                |--------------------------------------------------------------------------
                */

                if (!$this->isValidResult($result)) {
                    $this->error(
                        "❌ AI result failed validation for ID {$news->id}"
                    );

                    Log::warning(
                        'AI result failed validation',
                        [
                            'news_id' => $news->id,
                            'result' => $result,
                        ]
                    );

                    $failed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Build slug
                |--------------------------------------------------------------------------
                */

                $slug = $this->buildSlug($title, $news->id);

                /*
                |--------------------------------------------------------------------------
                | Normalize keywords
                |--------------------------------------------------------------------------
                */

                $keywords = $this->normalizeKeywords(
                    $result['keywords']
                );

                if (count($keywords) < 3) {
                    $this->error(
                        "❌ Invalid keywords for ID {$news->id}"
                    );

                    Log::warning(
                        'AI keywords validation failed',
                        [
                            'news_id' => $news->id,
                            'keywords' => $result['keywords'] ?? null,
                        ]
                    );

                    $failed++;

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
                    (string) (
                        $result['summary_ar']
                        ?? $result['meta_description_ar']
                        ?? ''
                    )
                );

                $metaDescriptionAr = trim(
                    (string) (
                        $result['meta_description_ar']
                        ?? $summaryAr
                    )
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

                if (!$this->validateFinalContent(
                    $titleAr,
                    $contentAr,
                    $summaryAr,
                    $whyItMattersAr,
                    $analysisAr,
                    $contextAr,
                    $whatToWatchAr,
                    $limitationsAr,
                    $metaDescriptionAr
                )) {
                    $this->error(
                        "❌ Final content validation failed for ID {$news->id}"
                    );

                    Log::warning(
                        'AI final content validation failed',
                        [
                            'news_id' => $news->id,
                        ]
                    );

                    $failed++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Save
                |--------------------------------------------------------------------------
                */

                $news->update([
                    'slug' => $slug,

                    'title_ar' => $titleAr,

                    'content_ar' => $contentAr,

                    /*
                    |--------------------------------------------------------------------------
                    | Important:
                    | summary_ar contains the article summary,
                    | NOT necessarily the meta description.
                    |--------------------------------------------------------------------------
                    */

                    'summary_ar' => $summaryAr,

                    'why_it_matters_ar' => $whyItMattersAr,

                    'analysis_ar' => $analysisAr,

                    'context_ar' => $contextAr,

                    'what_to_watch_ar' => $whatToWatchAr,

                    'limitations_ar' => $limitationsAr,

                    'keywords' => $keywords,

                    'sentiment' => $sentiment,

                    'category' => $category,

                    'impact_score' => $impactScore,

                    /*
                    |--------------------------------------------------------------------------
                    | Mark processed ONLY after successful validation and save.
                    |--------------------------------------------------------------------------
                    */

                    'ai_processed' => true,
                ]);

                $processed++;

                $this->info(
                    "✅ AI editorial analysis saved for ID {$news->id}"
                );

            } catch (\Throwable $e) {

                $failed++;

                $this->error(
                    "❌ Processing failed for ID {$news->id}: {$e->getMessage()}"
                );

                Log::error(
                    'AI News Processing Exception',
                    [
                        'news_id' => $news->id,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Rate limiting
            |--------------------------------------------------------------------------
            */

            sleep(self::RATE_LIMIT_SECONDS);
        }

        /*
        |--------------------------------------------------------------------------
        | Clear AI caches
        |--------------------------------------------------------------------------
        */

        Cache::forget('ai_market_dashboard_stats');
        Cache::forget('ai_market_impact_news');

        /*
        |--------------------------------------------------------------------------
        | Final report
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info('🧹 AI Market cache cleared.');

        $this->info(
            "📊 Processed: {$processed} | Failed: {$failed} | Skipped: {$skipped}"
        );

        $this->info(
            '🚀 Aql Crypto AI Editorial Cycle Completed.'
        );

        return $failed > 0 && $processed === 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }


    /**
     * Send article to Gemini for editorial analysis.
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

Your task is NOT to translate the source article.

Your task is NOT to mechanically rewrite the source article.

Your task is to transform the supplied factual source material into an ORIGINAL Arabic editorial news analysis.

The final article must provide genuine editorial value by explaining facts, context, implications, uncertainty, and what readers should monitor.

The output must remain strictly grounded in the supplied source material.

==================================================
1. ORIGINALITY
==================================================

Write independently in professional Modern Standard Arabic.

Do not translate sentence by sentence.

Do not copy sentences from the source.

Do not preserve the source article's paragraph structure.

Do not merely replace English words with Arabic words.

Reorganize the information naturally.

Combine related facts where useful.

Explain why the facts matter.

The final text should read like an independently produced Arabic editorial analysis.

However:

Originality does NOT mean inventing information.

Never add facts that are not supported by the source.

==================================================
2. SOURCE FIDELITY
==================================================

The supplied source is the factual foundation.

Preserve accurately:

- names
- companies
- organizations
- dates
- numbers
- percentages
- prices
- cryptocurrency names
- ticker symbols
- proposals
- technical terms
- quotations
- events

Never invent:

- people
- companies
- partnerships
- investments
- statistics
- blockchain data
- market movements
- prices
- regulatory decisions
- technical developments
- quotes
- sources
- citations

If a fact is not available in the source, do not present it as a fact.
 
IMPORTANT SOURCE BOUNDARY:

You are strictly limited to the information contained in the supplied source material.

Do not use your general knowledge, training knowledge, current market knowledge, or outside information to add facts.

Never introduce:

- current cryptocurrency prices
- market capitalization
- trading volume
- percentage changes
- new dates
- new statistics
- new blockchain data
- additional quotes
- additional people
- additional companies
- regulatory information

unless that information is explicitly present in the supplied source material.

If the source does not contain market data, do not mention market data.

If the source does not provide enough information to support a claim, omit the claim.
==================================================
3. IMPORTANT DISTINCTION: FACT VS ANALYSIS
==================================================

Clearly distinguish between:

A. Facts directly supported by the source.

B. Reasonable editorial interpretation.

C. Uncertainty or missing information.

When making an interpretation, use cautious language such as:

"قد يشير ذلك إلى"

"من المحتمل أن"

"قد يعكس"

"قد يؤدي إلى"

"يعتمد التأثير على"

"لا يمكن الجزم بأن"

Never present an inference as a confirmed fact.

==================================================
4. ARTICLE STRUCTURE
==================================================

Create a complete Arabic editorial article.

The article should naturally cover the following:

A. WHAT HAPPENED?

Explain the central event clearly in the opening.

The reader should understand the main development immediately.

B. IMPORTANT DETAILS

Explain the most important facts, numbers, people, organizations and technical details.

Do not repeat the same fact unnecessarily.

C. CONTEXT

Explain the background needed to understand the event.

Clarify technical concepts when useful.

The context must be derived from the source and established general understanding of the concepts described in the source.

Do not introduce unsupported current events.

D. AQL CRYPTO ANALYSIS

Provide genuine editorial interpretation based ONLY on facts explicitly contained in the supplied source material.

Explain:

- what the development may mean
- why the development matters
- what mechanism is important
- what could change as a result
- what remains uncertain

The analysis may connect facts already provided in the source and explain their implications.

IMPORTANT:

Do NOT introduce new factual information from your general knowledge, training knowledge, current market knowledge, or outside information.

Do NOT add:

- current cryptocurrency prices
- market capitalization
- trading volume
- market movements
- new statistics
- new percentages
- new dates
- new blockchain data
- additional people
- additional companies
- additional quotes
- regulatory information

unless explicitly stated in the supplied source material.

If the source does not contain market data, do not mention market data.

The analysis may contain reasonable editorial inference, but inference must be clearly expressed as inference and must not be presented as established fact.

Use cautious language when appropriate, such as:

- قد يشير ذلك إلى
- قد يعكس
- من المحتمل أن
- يمكن أن يعني
- قد يؤدي إلى
- قد يزيد من أهمية
- لا يعني بالضرورة
- لا يمكن الجزم بأن

Do not produce generic statements such as:

"هذا الخبر مهم جداً"

"قد يؤثر على السوق"

unless you explain WHY.

The analysis must contain clear reasoning.

Do not make guaranteed price predictions.

Do not tell readers to buy or sell anything.

Do not provide financial advice.

F. WHAT TO WATCH

Identify realistic next developments or indicators directly connected to the story.

Do not create imaginary future events.

G. LIMITATIONS

Explain important missing information, uncertainty, conflicting claims, or limits of the available source.

==================================================
5. VALUE-ADDED REQUIREMENT
==================================================

The final article must provide more value than a simple rewrite.

Value should come from:

- explaining implications
- connecting facts
- clarifying technical concepts
- explaining why an event matters
- distinguishing facts from interpretation
- identifying uncertainty
- identifying relevant things to monitor

Do not increase length with filler.

Do not repeat the same idea using different words.

==================================================
6. FINANCIAL SAFETY
==================================================

This is journalism and market analysis.

It is NOT financial advice.

Never:

- recommend buying
- recommend selling
- tell readers to invest
- guarantee profits
- guarantee losses
- guarantee price increases
- guarantee price decreases
- predict an exact future price
- claim that an asset will definitely rise
- claim that an asset will definitely fall

==================================================
7. TITLE
==================================================

Create a concise Arabic SEO title.

The title must:

- accurately describe the main event
- avoid exaggeration
- avoid clickbait
- avoid unsupported claims

==================================================
8. SUMMARY
==================================================

Create a concise Arabic summary.

The summary should describe the most important factual development.

Do not turn the summary into an opinion.

==================================================
9. META DESCRIPTION
==================================================

Create an Arabic SEO meta description.

Maximum approximately 160 Arabic characters.

It should describe the article naturally.

Do not use clickbait.

==================================================
10. KEYWORDS
==================================================

Generate exactly 3 to 5 English keywords.

Use Title Case for normal words.

Use uppercase ticker symbols.

Only use keywords genuinely related to the article.

If a specific cryptocurrency is central to the article, include its name and ticker when appropriate.

Examples:

Bitcoin
BTC
Ethereum
ETH
Solana
SOL

Do NOT add irrelevant keywords just to increase keyword count.

==================================================
11. SENTIMENT
==================================================

Choose exactly one:

Bullish
Bearish
Neutral

Sentiment must describe the likely market tone of THIS specific event.

Do not classify an article as Bullish or Bearish simply because it discusses an important event.

If the available information does not support a clear direction, use:

Neutral

==================================================
12. IMPACT SCORE
==================================================

Choose an integer from 1 to 10.

1 = very limited cryptocurrency relevance

10 = potentially major cryptocurrency ecosystem significance

Consider:

- scale
- technical importance
- governance importance
- market relevance
- number of users or organizations potentially affected

Do not assign a high score merely because the article sounds dramatic.

==================================================
13. CATEGORY
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
14. LANGUAGE
==================================================

All Arabic fields must be written in professional Modern Standard Arabic.

Avoid machine-translation style.

Avoid unnecessary English words.

Use established Arabic cryptocurrency terminology where appropriate.

==================================================
15. PARAGRAPHS
==================================================

Use clear paragraphs.

Separate paragraphs with two newline characters.

Do not create one giant paragraph.

Do not use Markdown headings inside content_ar.

The frontend will handle the visual structure.

==================================================
16. JSON OUTPUT
==================================================

Return ONLY valid JSON.

No Markdown.

No explanation outside JSON.

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

Original publication timestamp stored by Aql Crypto:
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

Do not copy the source.

Do not perform literal translation.

Do not invent facts.

Do not fabricate citations.

Do not provide investment advice.

Do not make unsupported predictions.

Clearly distinguish factual reporting from editorial interpretation.

Return JSON only.
PROMPT;

        /*
        |--------------------------------------------------------------------------
        | Replace prompt variables safely
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
                (string) ($news->source ?? 'Unknown'),
                (string) ($news->url ?? ''),
                (string) ($news->created_at ?? ''),
                $title,
                $content,
            ],
            $prompt
        );

        /*
        |--------------------------------------------------------------------------
        | Structured JSON schema
        |--------------------------------------------------------------------------
        */

        $schema = [
            'type' => 'object',

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
        | Gemini payload
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
                'temperature' => 0.35,

                'response_mime_type' => 'application/json',

                'response_schema' => $schema,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Request
        |--------------------------------------------------------------------------
        */

        try {

            $response = Http::timeout(90)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            /*
            |--------------------------------------------------------------------------
            | HTTP error
            |--------------------------------------------------------------------------
            */

            if (!$response->successful()) {

                Log::error(
                    'Gemini API Error',
                    [
                        'news_id' => $news->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | Extract Gemini text
            |--------------------------------------------------------------------------
            */

            $text = data_get(
                $response->json(),
                'candidates.0.content.parts.0.text'
            );

            if (!is_string($text) || trim($text) === '') {

                Log::error(
                    'Gemini returned empty response',
                    [
                        'news_id' => $news->id,
                        'response' => $response->json(),
                    ]
                );

                return null;
            }

            $text = trim($text);

            /*
            |--------------------------------------------------------------------------
            | Decode JSON
            |--------------------------------------------------------------------------
            |
            | Structured output should already be valid JSON.
            | The fallback cleanup below protects against accidental
            | markdown fences from the model.
            |
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

            $data = json_decode(
                $text,
                true
            );

            if (
                json_last_error() !== JSON_ERROR_NONE
            ) {

                Log::error(
                    'Gemini JSON Error',
                    [
                        'news_id' => $news->id,
                        'error' => json_last_error_msg(),
                        'response' => $text,
                    ]
                );

                return null;
            }

            if (!is_array($data)) {

                Log::error(
                    'Gemini JSON is not an array',
                    [
                        'news_id' => $news->id,
                    ]
                );

                return null;
            }

            return $data;

        } catch (\Throwable $e) {

            Log::error(
                'Gemini Connection Error',
                [
                    'news_id' => $news->id,
                    'message' => $e->getMessage(),
                ]
            );

            return null;
        }
    }


    /**
     * Validate AI output before saving.
     */
    private function isValidResult(?array $result): bool
    {
        if (!is_array($result)) {
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

        foreach ($required as $field) {

            if (!array_key_exists($field, $result)) {
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

        foreach ($stringFields as $field) {

            if (
                !is_string($result[$field]) ||
                trim($result[$field]) === ''
            ) {
                return false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Minimum article length
        |--------------------------------------------------------------------------
        */

        $contentLength = mb_strlen(
            trim($result['content_ar'])
        );

        if ($contentLength < 600) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Maximum article length
        |--------------------------------------------------------------------------
        |
        | Prevent accidental extremely long output.
        |
        */

        if ($contentLength > 12000) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Analytical value
        |--------------------------------------------------------------------------
        */

        if (
            mb_strlen(
                trim($result['analysis_ar'])
            ) < 180
        ) {
            return false;
        }

        if (
            mb_strlen(
                trim($result['why_it_matters_ar'])
            ) < 100
        ) {
            return false;
        }

        if (
            mb_strlen(
                trim($result['context_ar'])
            ) < 100
        ) {
            return false;
        }

        if (
            mb_strlen(
                trim($result['what_to_watch_ar'])
            ) < 80
        ) {
            return false;
        }

        if (
            mb_strlen(
                trim($result['limitations_ar'])
            ) < 50
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Meta description
        |--------------------------------------------------------------------------
        */

        $metaLength = mb_strlen(
            trim($result['meta_description_ar'])
        );

        if ($metaLength < 50 || $metaLength > 180) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Sentiment
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $result['sentiment'],
                [
                    'Bullish',
                    'Bearish',
                    'Neutral',
                ],
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
            !is_int($result['impact_score']) &&
            !is_numeric($result['impact_score'])
        ) {
            return false;
        }

        $impactScore = (int) $result['impact_score'];

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
            !is_array($result['keywords'])
        ) {
            return false;
        }

        $keywordCount = count(
            $result['keywords']
        );

        if (
            $keywordCount < 3 ||
            $keywordCount > 5
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


    /**
     * Final validation before database update.
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

        if (mb_strlen($titleAr) < 15) {
            return false;
        }

        if (mb_strlen($titleAr) > 180) {
            return false;
        }

        if (mb_strlen($contentAr) < 800) {
            return false;
        }

        if (mb_strlen($summaryAr) < 50) {
            return false;
        }

        if (mb_strlen($whyItMattersAr) < 100) {
            return false;
        }

        if (mb_strlen($analysisAr) < 180) {
            return false;
        }

        if (mb_strlen($contextAr) < 100) {
            return false;
        }

        if (mb_strlen($whatToWatchAr) < 80) {
            return false;
        }

        if (mb_strlen($limitationsAr) < 50) {
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


    /**
     * Build a stable slug.
     */
    private function buildSlug(
        string $title,
        int|string $id
    ): string {

        $slug = Str::slug($title);

        /*
        |--------------------------------------------------------------------------
        | Arabic titles may produce an empty slug depending on configuration.
        |--------------------------------------------------------------------------
        */

        if ($slug === '') {
            $slug = 'news';
        }

        return $slug . '-' . $id;
    }


    /**
     * Normalize keywords.
     */
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


    /**
     * Normalize sentiment.
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


    /**
     * Normalize category.
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


    /**
     * Normalize impact score.
     */
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