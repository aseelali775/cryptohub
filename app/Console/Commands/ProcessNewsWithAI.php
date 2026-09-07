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
    private const RATE_LIMIT_SECONDS = 20;
    private const MAX_API_RETRIES = 2;
    private const RETRY_BASE_SECONDS = 5;

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
        // 1. Query Update: Fetch ONLY pending & unprocessed
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
            $this->info("Processing ID {$news->id}: {$news->title_en}");

            try {
                $title = trim((string) $news->title_en);
                $content = trim(mb_substr((string) $news->content_en, 0, self::MAX_SOURCE_LENGTH));
                $contentLength = mb_strlen($content);

                if (mb_strlen($title) < 5) {
                    $this->warn("⚠️ Article {$news->id} has an invalid title.");
                    $skipped++;
                    continue;
                }

                if ($contentLength < self::MIN_SOURCE_LENGTH) {
                    $this->warn("⚠️ Article {$news->id} source content is too short for AI.");
                    $skipped++;
                    continue;
                }

                $result = $this->analyzeWithGemini($news, $title, $content, $apiKey);

                if (is_array($result) && ($result['__quota_exceeded'] ?? false)) {
                    $this->error('🛑 Gemini API quota/rate limit reached.');
                    $apiFailed++;
                    break;
                }

                if (is_array($result) && ($result['__temporary_failure'] ?? false)) {
                    $this->error("❌ Temporary Gemini API failure for ID {$news->id}");
                    $apiFailed++;
                    continue;
                }

                // =========================================================
                // 🛡️ RELEVANCE GATE: Safety Check
                // =========================================================
                if (!is_array($result) || !array_key_exists('is_relevant', $result)) {
                    $this->error("❌ AI relevance decision missing for #{$news->id}");
                    
                    $news->update([
                        'status' => 'failed',
                        'rejection_reason' => 'missing_relevance_decision',
                        'ai_processed' => false,
                    ]);
                    
                    $failed++;
                    continue;
                }

                // =========================================================
                // 🛡️ RELEVANCE GATE: Rejected Content
                // =========================================================
                if ($result['is_relevant'] === false) {
                    $reason = trim((string) ($result['rejection_reason'] ?? 'off_topic'));
                    
                    if ($reason === '') {
                        $reason = 'off_topic';
                    }

                    $news->update([
                        'status' => 'rejected',
                        'rejection_reason' => mb_substr($reason, 0, 255),
                        'ai_processed' => false,
                    ]);

                    $this->warn("🚫 Rejected #{$news->id}: {$news->title_en} — {$reason}");
                    $skipped++;
                    continue;
                }

                // =========================================================
                // If is_relevant === true, proceed with validation
                // =========================================================
                if (!$this->isValidResult($result)) {
                    $this->error("❌ AI result validation failed for ID {$news->id}");
                    $validationFailed++;
                    continue;
                }

                $slug = $this->buildSlug($title, $news->id);
                $keywords = $this->normalizeKeywords($result['keywords']);

                if (count($keywords) < 3 || count($keywords) > 5) {
                    $this->error("❌ Invalid keywords for ID {$news->id}");
                    $validationFailed++;
                    continue;
                }

                $sentiment = $this->normalizeSentiment($result['sentiment'] ?? null);
                $category = $this->normalizeCategory($result['category'] ?? null);
                $impactScore = $this->normalizeImpactScore($result['impact_score'] ?? null);

                $titleAr = trim((string) $result['title_ar']);
                $contentAr = trim((string) $result['content_ar']);
                $summaryAr = trim((string) $result['summary_ar']);
                $metaDescriptionAr = trim((string) $result['meta_description_ar']);
                $whyItMattersAr = trim((string) $result['why_it_matters_ar']);
                $analysisAr = trim((string) $result['analysis_ar']);
                $contextAr = trim((string) $result['context_ar']);
                $whatToWatchAr = trim((string) $result['what_to_watch_ar']);
                $limitationsAr = trim((string) $result['limitations_ar']);

                if (!$this->validateFinalContent($titleAr, $contentAr, $summaryAr, $whyItMattersAr, $analysisAr, $contextAr, $whatToWatchAr, $limitationsAr, $metaDescriptionAr)) {
                    $this->error("❌ Final content validation failed for ID {$news->id}");
                    $validationFailed++;
                    continue;
                }

                // =========================================================
                // 2. Publication state update
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
                $this->info("✅ AI editorial analysis saved and published for ID {$news->id}");

            } catch (\Throwable $e) {
                $failed++;
                $this->error("❌ Processing failed for ID {$news->id}: {$e->getMessage()}");
                
                $news->update([
                    'status' => 'failed',
                    'rejection_reason' => 'ai_processing_failed',
                    'ai_processed' => false,
                ]);
            }

            if ($news->id !== $newsList->last()->id) {
                sleep(self::RATE_LIMIT_SECONDS);
            }
        }

        Cache::forget('ai_market_dashboard_stats');
        Cache::forget('ai_market_impact_news');

        $this->newLine();
        $this->info("📊 Processed: {$processed} | ❌ Failed: {$failed} | ⏭️ Skipped/Rejected: {$skipped} | ⚠️ Validation Failed: {$validationFailed}");
        $this->newLine();
        $this->info('🚀 Aql Crypto AI Editorial Cycle Completed.');

        return ($failed > 0 && $processed === 0) ? self::FAILURE : self::SUCCESS;
    }

    private function analyzeWithGemini(News $news, string $title, string $content, string $apiKey): ?array
    {
        $url = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', self::GEMINI_MODEL, $apiKey);

        // =========================================================
        // 3. Updated Prompt with robust Relevance Gate rules
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
            ['{{SOURCE}}', '{{URL}}', '{{DATE}}', '{{TITLE}}', '{{CONTENT}}'],
            [(string) ($news->source ?? 'Unknown'), (string) ($news->url ?? ''), (string) ($news->created_at ?? ''), $title, $content],
            $prompt
        );

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'is_relevant' => ['type' => 'BOOLEAN'],
                'rejection_reason' => ['type' => 'STRING'],
                'title_ar' => ['type' => 'STRING'],
                'content_ar' => ['type' => 'STRING'],
                'summary_ar' => ['type' => 'STRING'],
                'meta_description_ar' => ['type' => 'STRING'],
                'why_it_matters_ar' => ['type' => 'STRING'],
                'analysis_ar' => ['type' => 'STRING'],
                'context_ar' => ['type' => 'STRING'],
                'what_to_watch_ar' => ['type' => 'STRING'],
                'limitations_ar' => ['type' => 'STRING'],
                'sentiment' => ['type' => 'STRING'],
                'category' => ['type' => 'STRING'],
                'impact_score' => ['type' => 'INTEGER'],
                'keywords' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => [
                'is_relevant',
            ],
        ];

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.35,
                'response_mime_type' => 'application/json',
                'response_schema' => $schema,
            ],
        ];

        for ($attempt = 1; $attempt <= self::MAX_API_RETRIES + 1; $attempt++) {
            try {
                $response = Http::timeout(90)->acceptJson()->asJson()->post($url, $payload);

                if ($response->successful()) {
                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    
                    if (!is_string($text) || trim($text) === '') {
                        return null;
                    }

                    $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));$text = preg_replace('/\s*```$/', '', $text);
                    $data = json_decode(trim($text), true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return null;
                    }

                    return is_array($data) ? $data : null;
                }

                if ($response->status() === 429) {
                    return [
                        '__quota_exceeded' => true,
                        '__message' => 'Gemini API quota exceeded.',
                        '__retry_seconds' => null,
                    ];
                }

                if (in_array($response->status(), [500, 502, 503, 504], true)) {
                    if ($attempt <= self::MAX_API_RETRIES) {
                        sleep(self::RETRY_BASE_SECONDS * $attempt);
                        continue;
                    }
                    return ['__temporary_failure' => true];
                }

                return null;

            } catch (\Throwable $e) {
                if ($attempt <= self::MAX_API_RETRIES) {
                    sleep(self::RETRY_BASE_SECONDS * $attempt);
                    continue;
                }
                return ['__temporary_failure' => true];
            }
        }
        return null;
    }

    private function isValidResult(?array $result): bool
    {
        // Notice: This function is only called if is_relevant === true
        $required = [
            'title_ar', 'content_ar', 'summary_ar', 'meta_description_ar', 'why_it_matters_ar',
            'analysis_ar', 'context_ar', 'what_to_watch_ar', 'limitations_ar', 'sentiment', 'category', 'impact_score', 'keywords'
        ];

        foreach ($required as $field) {
            if (!array_key_exists($field, $result)) return false;
        }

        return true;
    }

    private function validateFinalContent(string $titleAr, string $contentAr, string $summaryAr, string $whyItMattersAr, string $analysisAr, string $contextAr, string $whatToWatchAr, string $limitationsAr, string $metaDescriptionAr): bool
    {
        if (mb_strlen($titleAr) < 15 || mb_strlen($titleAr) > 180) return false;
        if (mb_strlen($contentAr) < self::MIN_ARTICLE_LENGTH || mb_strlen($contentAr) > self::MAX_ARTICLE_LENGTH) return false;
        if (mb_strlen($summaryAr) < 50) return false;
        if (mb_strlen($whyItMattersAr) < 100) return false;
        if (mb_strlen($analysisAr) < 180) return false;
        if (mb_strlen($contextAr) < 100) return false;
        if (mb_strlen($whatToWatchAr) < 80) return false;
        if (mb_strlen($limitationsAr) < 50) return false;
        if (mb_strlen($metaDescriptionAr) < 50 || mb_strlen($metaDescriptionAr) > 180) return false;

        return true;
    }

    private function buildSlug(string $title, int|string $id): string
    {
        $slug = Str::slug($title);
        if ($slug === '') $slug = 'news';
        return $slug . '-' . $id;
    }

    private function normalizeKeywords(array $keywords): array
    {
        return collect($keywords)
            ->map(fn ($keyword) => trim((string) $keyword))
            ->filter()
            ->map(fn ($keyword) => preg_replace('/\s+/', ' ', $keyword))
            ->unique(fn ($keyword) => mb_strtolower($keyword))
            ->take(5)
            ->values()
            ->toArray();
    }

    private function normalizeSentiment(mixed $sentiment): string
    {
        return in_array($sentiment, ['Bullish', 'Bearish', 'Neutral'], true) ? $sentiment : 'Neutral';
    }

    private function normalizeCategory(mixed $category): string
    {
        $allowedCategories = ['Bitcoin', 'Ethereum', 'Regulation', 'DeFi', 'NFT', 'Mining', 'Market', 'Security', 'Blockchain'];
        return in_array($category, $allowedCategories, true) ? $category : 'Market';
    }

    private function normalizeImpactScore(mixed $score): int
    {
        if (!is_numeric($score)) return 5;
        return max(1, min(10, (int) $score));
    }
}