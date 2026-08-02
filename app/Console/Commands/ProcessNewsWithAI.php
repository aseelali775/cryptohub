<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessNewsWithAI extends Command
{
    protected $signature = 'news:process-ai';
    protected $description = 'Process un-translated crypto news using Gemini API (Summarize, Sentiment, Categorize).';

    public function handle()
    {
        $apiKey = env('GEMINI_API_KEY');
        
        if (empty($apiKey)) {$this->error('GEMINI_API_KEY is missing in .env file.');
            return;
        }

        $this->info('Starting AI News Processor...');

        // 👈 3. تقليل العدد إلى 2 أخبار لضمان الاستقرار في البداية
        $newsList = News::where('ai_processed', false)->limit(2)->get();

        if ($newsList->isEmpty()) {$this->info('No new articles to process. Resting... ☕');
            return;
        }

        foreach ($newsList as $news) {$this->info("Processing AI for: {$news->title_en}");
            
            // 👈 4. اقتطاع النص إلى 8000 حرف لحماية الـ Tokens
            $truncatedContent = mb_substr($news->content_en, 0, 8000);

            $result = $this->analyzeWithGemini($news->title_en, $truncatedContent,$apiKey);

            if ($result && is_array($result)) {$news->update([
                    'title_ar'     => $result['title_ar'] ?? $news->title_ar,
                    'summary_ar'   => $result['summary_ar'] ?? null,
                    'sentiment'    => $result['sentiment'] ?? 'Neutral',
                    'category'     => $result['category'] ?? 'General',
                    'impact_score' => $result['impact_score'] ?? 5,
                    'ai_processed' => true,
                ]);
                $this->info("✅ Successfully processed and updated: {$news->id}");
            } else {
                $this->error("⚠️ Failed to parse AI response for ID: {$news->id}");
            }

            sleep(3); // استراحة لتجنب Rate Limit
        }

        $this->info('AI Processing Cycle Completed. 🚀');
    }

    private function analyzeWithGemini($title, $content,$apiKey)
    {
      $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";
        // 👈 1. تم إزالة طلب ترجمة المقال الكامل (content_ar) للحفاظ على الموارد
        $prompt = "You are an expert financial crypto analyst and translator.
        Analyze the following English crypto news article.
        
        Tasks:
        1. Translate the title accurately to Arabic.
        2. Write a highly engaging, professional Arabic summary (max 3 sentences).
        3. Determine the market sentiment (Bullish, Bearish, or Neutral).
        4. Categorize it (e.g., Bitcoin, Ethereum, Regulation, DeFi, Mining, Exchange).
        5. Assign an impact score from 1 to 10 (1=Low, 10=High impact on the market).
        
        CRITICAL INSTRUCTION: Return ONLY a valid JSON object. Do not wrap it in markdown blockquotes like ```json.
        
        JSON Format required:
        {
          \"title_ar\": \"translated title here\",
          \"summary_ar\": \"arabic summary here\",
          \"sentiment\": \"Bullish/Bearish/Neutral\",
          \"category\": \"category here\",
          \"impact_score\": integer
        }
        
        ARTICLE TITLE: {$title}
        ARTICLE CONTENT: {$content}";

        $payload = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ],
            "generationConfig" => [
                "response_mime_type" => "application/json",
            ]
        ];

        try {
            $response = Http::timeout(30)->post($url, $payload);

            if ($response->successful()) {
                $responseText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                $cleanJson = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $responseText);
                $cleanJson = trim($cleanJson);

                // 👈 2. حماية صارمة للـ JSON وإلتقاط أخطاء فك التشفير
                $data = json_decode($cleanJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Invalid JSON from Gemini: ' . json_last_error_msg());
                    Log::error('Raw Gemini Response: ' . $responseText);
                    return null;
                }

                return $data;
            }
            
            Log::error("Gemini API Error: " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("Gemini Connection Error: " . $e->getMessage());
            return null;
        }
    }
}