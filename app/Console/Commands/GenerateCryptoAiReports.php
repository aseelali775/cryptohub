<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cryptocurrency;
use App\Models\CryptoAiReport;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GenerateCryptoAiReports extends Command
{
    protected $signature = 'crypto:generate-ai-reports';
    protected $description = 'Generate AI intelligence reports for top cryptocurrencies based on news context.';

    public function handle()
    {
        $apiKey = config('services.gemini.key');
        if (empty($apiKey)) {
            $this->error('GEMINI_API_KEY is missing.'); return;
        }

        // 🟢 جلب أول 50 عملة فقط لتجنب استنزاف الـ Quota
        $cryptos = Cryptocurrency::with('aliases')
            ->where('market_cap_rank', '<=', 50)
            ->orderBy('market_cap_rank')
            ->get();

        foreach ($cryptos as $crypto) {
            $this->info("🤖 Analyzing: {$crypto->name}...");

            // 🟢 استخدام نظام الـ Aliases لجلب أدق سياق إخباري
            $searchTerms = array_unique(array_merge(
                [$crypto->name, $crypto->symbol],
                $crypto->aliases->pluck('alias')->toArray()
            ));

            $recentNews = News::where('ai_processed', true)
                ->where(function($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query->orWhereJsonContains('keywords', $term)
                              ->orWhere('title_en', 'LIKE', "%{$term}%"); 
                    }
                })->latest()->take(6)->get();

            // إذا لم يكن هناك أخبار، نتجاهل العملة
            if ($recentNews->isEmpty()) {
                $this->warn("⚠️ No recent news for {$crypto->name}. Skipping.");
                continue;
            }

            $newsContext = $recentNews->pluck('summary_ar')->implode("\n- ");
            $result = $this->generateWithGemini($crypto, $newsContext, $apiKey);

            if ($result && is_array($result)) {
                CryptoAiReport::create([
                    'cryptocurrency_id' => $crypto->id,
                    'trend'             => $result['trend'] ?? 'Neutral',
                    'confidence'        => (int) ($result['confidence'] ?? 50),
                    'strength_score'    => (int) ($result['strength_score'] ?? 5),
                    'summary'           => $result['summary'] ?? '',
                    'bullish_factors'   => is_array($result['bullish_factors'] ?? null) ? $result['bullish_factors'] : [],
                    'risk_factors'      => is_array($result['risk_factors'] ?? null) ? $result['risk_factors'] : [],
                    'generated_at'      => now(),
                ]);
                $this->info("✅ Report saved for {$crypto->name}");
                
                // مسح كاش التقرير ليظهر فوراً في صفحة العملة
                Cache::forget("coin_ai_report_{$crypto->symbol}");
            } else {
                $this->error("❌ Failed to parse response for {$crypto->name}");
            }

            sleep(2); // 🟢 2 ثانية كما اقترحت (سريع وآمن)
        }
        $this->info('🚀 All AI Reports Generated Successfully!');
    }

    private function generateWithGemini($crypto, $newsContext, $apiKey)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}";

        $prompt = "You are an expert Cryptocurrency AI Market Analyst.
        Based on the following recent news context for {$crypto->name} ({$crypto->symbol}), generate a professional market intelligence report in Arabic.
        
        Recent News Context:
        {$newsContext}
        
        CRITICAL: RETURN ONLY A VALID JSON OBJECT EXACTLY LIKE THIS FORMAT:
        {
          \"trend\": \"Bullish\", \"Bearish\", or \"Neutral\",
          \"confidence\": Integer between 0 and 100,
          \"strength_score\": Integer between 1 and 10,
          \"summary\": \"A strictly professional Arabic summary of the coin's current market situation.\",
          \"bullish_factors\": [\"Positive driver 1 in Arabic\", \"Positive driver 2 in Arabic\"],
          \"risk_factors\": [\"Risk 1 in Arabic\", \"Risk 2 in Arabic\"]
        }";

        $payload = [
            "contents" => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => ["response_mime_type" => "application/json"]
        ];

        try {
            $response = Http::timeout(60)->post($url, $payload);
            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                // التنظيف الجراحي الآمن
                $start = strpos($text, '{');
                $end = strrpos($text, '}');
                if ($start !== false && $end !== false) {
                    $cleanJson = substr($text, $start, $end - $start + 1);
                    return json_decode($cleanJson, true);
                }
            }
        } catch (\Exception $e) {
            Log::error("AI Report Error: " . $e->getMessage());
        }
        return null;
    }
}