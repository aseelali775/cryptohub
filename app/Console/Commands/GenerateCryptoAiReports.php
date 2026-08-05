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

        // جلب أعلى 50 عملة من حيث القيمة السوقية
        $cryptos = Cryptocurrency::with('aliases')
            ->orderBy('market_cap', 'desc')
            ->limit(50)
            ->get();

        foreach ($cryptos as $crypto) {
            $this->info("🤖 Analyzing: {$crypto->name}...");

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
                
                Cache::forget("coin_ai_report_{$crypto->symbol}");
            } else {
                // 🟢 تعديل رسالة الخطأ لتوجيهك للسجل
                $this->error("❌ Failed to parse response for {$crypto->name} (Check laravel.log)");
            }

            sleep(2);
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
        
        CRITICAL: RETURN ONLY A VALID JSON OBJECT EXACTLY LIKE THIS FORMAT. DO NOT ADD ANY MARKDOWN OR TEXT OUTSIDE THE JSON:
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
                
                // 🟢 1. تنظيف الـ Markdown المزعج
                $cleanJson = trim(preg_replace('/```json\s*(.*?)\s*```/s', '$1', $text));
                $cleanJson = trim(preg_replace('/```\s*(.*?)\s*```/s', '$1', $cleanJson));
                
                // 🟢 2. الاستخراج الجراحي للأقواس
                $start = strpos($cleanJson, '{');
                $end = strrpos($cleanJson, '}');
                
                if ($start !== false && $end !== false) {
                    $cleanJson = substr($cleanJson, $start, $end - $start + 1);
                    $data = json_decode($cleanJson, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $data;
                    } else {
                        // 🟢 تسجيل سبب الفشل بدقة في ملف الـ Log
                        Log::error("Gemini JSON Parse Error for {$crypto->name}: " . json_last_error_msg() . " | Raw: " . $cleanJson);
                    }
                } else {
                    Log::error("Gemini no JSON found for {$crypto->name} | Raw: " . $text);
                }
            } else {
                Log::error("Gemini API Error for {$crypto->name}", ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Exception $e) {
            Log::error("AI Report Error for {$crypto->name}: " . $e->getMessage());
        }
        return null;
    }
}