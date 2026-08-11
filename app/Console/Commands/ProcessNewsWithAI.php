<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProcessNewsWithAI extends Command
{
    protected $signature = 'news:process-ai';

    protected $description = 'Analyze crypto news and generate original Arabic editorial analysis using Gemini AI.';

    public function handle()
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            $this->error('GEMINI_API_KEY is missing.');
            Log::error('Gemini API Key missing.');
            return Command::FAILURE;
        }

        $this->info('Starting Aql Crypto AI Editorial Analysis...');

        $newsList = News::where('ai_processed', false)
            ->whereNotNull('content_en')
            ->where('content_en', '!=', '')
            ->latest()
            ->limit(3)
            ->get();

        if ($newsList->isEmpty()) {
            $this->info('No new articles.');
            return Command::SUCCESS;
        }

        foreach ($newsList as $news) {
            $this->info("Processing ID {$news->id}: {$news->title_en}");

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
                        12000
                    )
                );

                if (mb_strlen($content) < 200) {
                    $this->warn(
                        "⚠️ Article {$news->id} is too short. Skipping."
                    );

                    Log::warning(
                        'AI skipped short article',
                        ['news_id' => $news->id]
                    );

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

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Build slug
                |--------------------------------------------------------------------------
                */

                $slug = Str::slug($title) . '-' . $news->id;

                /*
                |--------------------------------------------------------------------------
                | Keywords
                |--------------------------------------------------------------------------
                */

                $keywords = [];

                if (isset($result['keywords']) && is_array($result['keywords'])) {
                    $keywords = collect($result['keywords'])
                        ->map(fn ($keyword) => trim((string) $keyword))
                        ->filter()
                        ->unique()
                        ->take(5)
                        ->values()
                        ->toArray();
                }

                /*
                |--------------------------------------------------------------------------
                | Normalize sentiment
                |--------------------------------------------------------------------------
                */

                $sentiment = $result['sentiment'] ?? 'Neutral';

                if (!in_array($sentiment, [
                    'Bullish',
                    'Bearish',
                    'Neutral'
                ], true)) {
                    $sentiment = 'Neutral';
                }

                /*
                |--------------------------------------------------------------------------
                | Normalize impact score
                |--------------------------------------------------------------------------
                */

                $impactScore = (int) ($result['impact_score'] ?? 5);

                $impactScore = max(
                    1,
                    min(10, $impactScore)
                );

                /*
                |--------------------------------------------------------------------------
                | Save
                |--------------------------------------------------------------------------
                */

                $news->update([
    'slug' => $slug,

    'title_ar' => trim($result['title_ar']),

    'content_ar' => trim($result['content_ar']),

    'summary_ar' => trim(
        $result['meta_description_ar']
        ?? $result['summary_ar']
    ),

    'why_it_matters_ar' => trim(
        $result['why_it_matters_ar']
    ),

    'analysis_ar' => trim(
        $result['analysis_ar']
    ),

    'context_ar' => trim(
        $result['context_ar']
    ),

    'what_to_watch_ar' => trim(
        $result['what_to_watch_ar']
    ),

    'limitations_ar' => trim(
        $result['limitations_ar']
    ),

    'keywords' => $keywords,

    'sentiment' => $sentiment,

    'category' => $result['category'] ?? 'Market',

    'impact_score' => $impactScore,

    'ai_processed' => true,
]);

                $this->info(
                    "✅ AI editorial analysis saved for ID {$news->id}"
                );

            } catch (\Throwable $e) {

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

            sleep(5);
        }

        /*
        |--------------------------------------------------------------------------
        | Clear AI Market cache
        |--------------------------------------------------------------------------
        */

        Cache::forget('ai_market_dashboard_stats');
        Cache::forget('ai_market_impact_news');

        $this->info('🧹 AI Market cache cleared.');
        $this->info('🚀 Aql Crypto AI Editorial Cycle Completed.');

        return Command::SUCCESS;
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

        $url =
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}";

        /*
        |--------------------------------------------------------------------------
        | Editorial Prompt
        |--------------------------------------------------------------------------
        */

        $prompt = <<<PROMPT
You are the senior editorial analyst for Aql Crypto, an Arabic cryptocurrency news and market-analysis platform.

Your task is NOT to translate or mechanically rewrite the supplied article.

Your task is to transform the supplied factual source material into an ORIGINAL Arabic editorial article that provides additional context and useful analysis while remaining strictly faithful to the available facts.

==================================================
EDITORIAL PRINCIPLES
==================================================

1. ORIGINALITY

Do not copy sentences from the source.

Do not perform literal translation.

Do not simply replace English words with Arabic words.

Write naturally in professional Modern Standard Arabic.

The article must read like an independently written Arabic news analysis.

==================================================
2. FACTUAL ACCURACY
==================================================

The supplied article is the factual source.

Never invent:

- people
- companies
- partnerships
- prices
- dates
- percentages
- statistics
- investments
- quotes
- blockchain data
- market movements
- regulatory decisions
- technical developments

If the source does not provide a fact, do not state it as fact.

If something cannot be determined from the source, clearly indicate that it is uncertain.

Do not fabricate citations.

==================================================
3. ARTICLE STRUCTURE
==================================================

Write the Arabic article using a useful journalistic structure.

The article should contain:

A. What happened?

Clearly explain the main event.

B. Important details

Explain the important numbers, people, companies, proposals, technologies or developments mentioned in the source.

C. Context

Explain why the event matters within the cryptocurrency/blockchain ecosystem.

The context must be based only on information that can reasonably be derived from the supplied material.

D. Aql Crypto Analysis

Provide an independent analytical interpretation.

Do NOT make guaranteed price predictions.

Do NOT tell readers to buy or sell anything.

Do NOT invent market data.

Use cautious language such as:

- قد يشير ذلك إلى
- من المحتمل أن
- قد يؤدي إلى
- يعتمد التأثير على
- لا يمكن الجزم بأن

when appropriate.

E. Why it matters

Explain clearly why a cryptocurrency investor, developer, company or ordinary reader should care about this development.

F. What to watch

Identify realistic developments or indicators that readers should monitor next.

Only mention things logically connected to the supplied facts.

G. Limitations

If important information is missing or uncertain, explain the limitation.

==================================================
4. VALUE-ADDED REQUIREMENT
==================================================

The final article must provide value beyond the source.

Do not make the article longer simply by adding filler.

Value should come from:

- explaining implications
- connecting facts
- clarifying technical concepts
- explaining market relevance
- identifying uncertainty
- identifying what readers should monitor

==================================================
5. FINANCIAL SAFETY
==================================================

This is journalism and analysis, NOT financial advice.

Never:

- recommend buying
- recommend selling
- guarantee profits
- guarantee price increases
- predict an exact future price
- claim an asset will definitely rise or fall

==================================================
6. TITLE
==================================================

Create a concise Arabic SEO title.

Do not exaggerate.

Do not use clickbait.

==================================================
7. SUMMARY
==================================================

Create a concise Arabic summary describing the most important fact.

==================================================
8. META DESCRIPTION
==================================================

Create an Arabic SEO meta description.

Maximum approximately 160 characters.

==================================================
9. KEYWORDS
==================================================

Generate exactly 3 to 5 English keywords.

Use Title Case for normal words.

Use uppercase ticker symbols.

If a specific cryptocurrency is central to the article, include its name and ticker when appropriate.

Examples:

Bitcoin
BTC
Ethereum
ETH
Solana
SOL

Do not add irrelevant keywords.

==================================================
10. SENTIMENT
==================================================

Choose exactly one:

Bullish
Bearish
Neutral

Sentiment must describe the likely market tone of the specific event.

If the information is insufficient to determine a direction, use Neutral.

==================================================
11. IMPACT SCORE
==================================================

Choose an integer from 1 to 10.

1 = very limited potential market relevance

10 = potentially major cryptocurrency market significance

Do not assign a high score merely because the article sounds important.

==================================================
12. CATEGORY
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
13. LANGUAGE
==================================================

All Arabic fields must be written in professional Modern Standard Arabic.

Do not use machine-translation style.

Do not overuse English terminology.

Use established Arabic cryptocurrency terminology when appropriate.

==================================================
14. JSON
==================================================

Return ONLY valid JSON.

No Markdown.

No explanations outside JSON.

Use exactly this structure:

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
{$news->source}

Original URL:
{$news->url}

Original publication date:
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

Produce an original Arabic editorial analysis based strictly on the supplied material.

Do not copy the source.

Do not invent facts.

Do not provide investment advice.

Do not make unsupported predictions.

Return JSON only.
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ],
        ];

        try {

            $response = Http::timeout(90)
                ->post($url, $payload);

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

            $text =
                $response->json()['candidates'][0]['content']['parts'][0]['text']
                ?? '';

            $text = trim($text);

            /*
            |--------------------------------------------------------------------------
            | Remove accidental Markdown fences
            |--------------------------------------------------------------------------
            */

            $text = preg_replace(
                '/^```json\s*/i',
                '',
                $text
            );

            $text = preg_replace(
                '/^```\s*/',
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
            | Extract JSON object if Gemini added extra characters
            |--------------------------------------------------------------------------
            */

            $start = strpos($text, '{');
            $end = strrpos($text, '}');

            if ($start !== false && $end !== false) {

                $text = substr(
                    $text,
                    $start,
                    $end - $start + 1
                );
            }

            $data = json_decode(
                $text,
                true
            );

            if (json_last_error() !== JSON_ERROR_NONE) {

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
            'why_it_matters_ar',
            'analysis_ar',
            'context_ar',
            'what_to_watch_ar',
            'limitations_ar',
        ];

        foreach ($required as $field) {

            if (
                !isset($result[$field]) ||
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

        if ($contentLength < 800) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Minimum analytical value
        |--------------------------------------------------------------------------
        */

        if (
            mb_strlen(trim($result['analysis_ar'])) < 150
        ) {
            return false;
        }

        if (
            mb_strlen(trim($result['why_it_matters_ar'])) < 80
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Sentiment validation
        |--------------------------------------------------------------------------
        */

        if (
            !isset($result['sentiment']) ||
            !in_array(
                $result['sentiment'],
                ['Bullish', 'Bearish', 'Neutral'],
                true
            )
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Category validation
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
            !isset($result['category']) ||
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
        | Impact score validation
        |--------------------------------------------------------------------------
        */

        $impactScore = filter_var(
            $result['impact_score'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $impactScore === false ||
            $impactScore < 1 ||
            $impactScore > 10
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Keywords validation
        |--------------------------------------------------------------------------
        */

        if (
            !isset($result['keywords']) ||
            !is_array($result['keywords'])
        ) {
            return false;
        }

        if (
            count($result['keywords']) < 3 ||
            count($result['keywords']) > 5
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Meta description length
        |--------------------------------------------------------------------------
        */

        if (
            mb_strlen(
                trim($result['meta_description_ar'] ?? '')
            ) > 180
        ) {
            return false;
        }

        return true;
    }
}