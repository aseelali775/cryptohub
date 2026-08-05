<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ProcessNewsWithAI extends Command
{
    protected $signature = 'news:process-ai';
    protected $description = 'Analyze crypto news to generate Arabic rewrites and market analysis using Gemini AI.';

    public function handle()
    {
        // 1. استخدام Config بدلاً من قراءة env مباشرة (Best Practice)
        $apiKey = config('services.gemini.key');
        
        if (empty($apiKey)) {$this->error('GEMINI_API_KEY is missing in config/services.php or .env file.');
            Log::error('AI Scheduler Error: GEMINI_API_KEY is missing.');
            return;
        }

        $this->info('Starting CryptoHub AI Arabic Journalist...');

        // 2. ترشيد الاستهلاك: معالجة 3 أخبار فقط في كل دورة
        $newsList = News::where('ai_processed', false)->latest()->limit(3)->get();
        
        if ($newsList->isEmpty()) {$this->info('No new articles for the AI to rewrite. Resting... ☕');
            return;
        }

        foreach ($newsList as $news) {$this->info("AI is processing: {$news->title_en}");
            
            $truncatedContent = mb_substr($news->content_en, 0, 8000);$result = $this->analyzeWithGemini($news->title_en, $truncatedContent,$apiKey);

            if ($result && is_array($result)) {
                // 3. تأمين الروابط (Unique Slug) لـ SEO ممتاز
                $safeSlug = Str::slug($news->title_en) . '-' .$news->id;
                
                // 4. تأمين الكلمات المفتاحية بحيث لا تتجاوز 5 كلمات
                $keywords = is_array($result['keywords'] ?? null) ? array_slice($result['keywords'], 0, 5) : [];$news->update([
                    'slug'              => $safeSlug,
                    'keywords'          => $keywords,
                    'title_ar'          => $result['title_ar'] ?? null,
                    'content_ar'        => $result['content_ar'] ?? null,
                    // استخدام الـ Meta Description إن وجد للـ SEO، وإلا نكتفي بالـ Summary
                    'summary_ar'        => $result['meta_description_ar'] ?? $result['summary_ar'] ?? null,
                    'why_it_matters_ar' => $result['why_it_matters_ar'] ?? null,
                    'sentiment'         => $result['sentiment'] ?? 'Neutral',
                    'category'          => $result['category'] ?? 'General',
                    'impact_score'      => (int) ($result['impact_score'] ?? 5),
                    'ai_processed'      => true,
                ]);
                
                $this->info("✅ Successfully analyzed and saved: ID {$news->id}");
            } else {
                $this->error("⚠️ Failed to parse AI response for ID: {$news->id}");
                Log::error("⚠️ Failed to parse AI response for ID: {$news->id}");
            }

            // استراحة قصيرة بين كل طلب لحماية الحصة (Quota)
            sleep(5);
        }

        // 5. مسح كاش لوحة AI Market مرة واحدة فقط خارج الـ Loop
        if ($newsList->isNotEmpty()) {
            Cache::forget('ai_market_dashboard_stats');
            Cache::forget('ai_market_impact_news');
            $this->info('🧹 System Cache cleared for AI Market Dashboard.');
        }

        $this->info('AI Journalism Cycle Completed. 🚀');
    }

    private function analyzeWithGemini($title, $content,$apiKey)
    {
        // استخدام الموديل المعتمد والمجرب في مشروعك
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}";

        // هندسة الأوامر (Prompt Engineering) لدعم الـ Aliases والـ SEO
        $prompt = "Analyze the provided cryptocurrency news article.
        Generate a professional Arabic journalistic rewrite and market analysis.
        Keep the original English article unchanged (do not output English text).
        
        Rules:
        - Do not translate word by word. Write a fluent, professional Arabic news article.
        - Preserve verified facts, names, numbers, dates and quotes.
        - Write a complete article. Expand the content only when sufficient information is available. Do not invent facts.
        - Generate 3-5 SEO keywords. Use English keywords only. 
        - CRITICAL: If the article is about a specific coin, you MUST include both the full name AND the ticker symbol in the keywords array (e.g., include both 'Bitcoin' and 'BTC').
        - Generate SEO meta description in Arabic under 160 characters.
        
        Generate a valid JSON object exactly like this:
        {
          \"title_ar\": \"SEO optimized title in Arabic\",
          \"content_ar\": \"Full professional article rewritten in Arabic\",
          \"summary_ar\": \"Short engaging summary in Arabic\",
          \"meta_description_ar\": \"SEO meta description under 160 chars\",
          \"why_it_matters_ar\": \"One short Arabic paragraph explaining why this news matters to crypto investors\",
          \"sentiment\": \"Bullish\", \"Bearish\", or \"Neutral\",
          \"category\": \"Main category (e.g., Bitcoin, Ethereum, Regulation, DeFi)\",
          \"impact_score\": Integer from 1 to 10,
          \"keywords\": [\"Keyword1\", \"Keyword2\", \"Keyword3\"]
        }
        
        CRITICAL: Return ONLY valid JSON. Do not use markdown wrappers like ```json.
        
        ARTICLE TITLE: {$title}
        ARTICLE CONTENT: {$content}";

        $payload = [
            "contents" => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => ["response_mime_type" => "application/json"]
        ];

        try {
            $response = Http::timeout(60)->post($url, $payload);
            
            if ($response->successful()) {
                $responseText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                // تنظيف الـ Markdown إن وجد
                $cleanJson = trim(preg_replace('/```json\s*(.*?)\s*```/s', '$1', $responseText));
                $cleanJson = trim(preg_replace('/```\s*(.*?)\s*```/s', '$1', $cleanJson));
                
                // 6. الاستخراج الآمن عبر الـ Substring لتفادي كسر الـ Regex (Defensive Programming)
                $start = strpos($cleanJson, '{');
                $end = strrpos($cleanJson, '}');

                if ($start !== false && $end !== false) {
                    $cleanJson = substr($cleanJson, $start, $end - $start + 1);
                }
                
                $data = json_decode($cleanJson, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                } else {
                    Log::error("Gemini JSON Parse Error: " . json_last_error_msg() . " | Response: " . $cleanJson);
                    return null;
                }
            } else {
                // 7. تسجيل الخطأ مع كود الحالة لتسهيل التتبع (Debugging)
                Log::error("Gemini API Error", [
                    'status' => $response->status(),
                    'body'   => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Gemini Connection Error: " . $e->getMessage());
            return null;
        }
    }
}