<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessNewsWithAI extends Command
{
    protected $signature = 'news:process-ai';
    protected $description = 'Auto-Blogging: Rewrite crypto news using Gemini AI to generate 100% unique English SEO content.';

    public function handle()
    {
        $apiKey = env('GEMINI_API_KEY');
        
        if (empty($apiKey)) {$this->error('GEMINI_API_KEY is missing in .env file.');
            return;
        }

        $this->info('Starting CryptoHub AI Journalist...');

        // سحب الأخبار التي لم تعالج بعد
        $newsList = News::where('ai_processed', false)->limit(2)->get();

        if ($newsList->isEmpty()) {$this->info('No new articles for the AI to rewrite. Resting... ☕');
            return;
        }

        foreach ($newsList as $news) {$this->info("AI is rewriting: {$news->title_en}");
            
            // اقتطاع النص الأصلي لحماية التوكنز
            $truncatedContent = mb_substr($news->content_en, 0, 8000);

            $result = $this->analyzeWithGemini($news->title_en, $truncatedContent,$apiKey);

            if ($result && is_array($result)) {
                // تحديث الحقول الجديدة مع الاحتفاظ بالأصلية
                $news->update([
                    'ai_title'     => $result['ai_title'] ?? null,
                    'ai_content'   => $result['ai_content'] ?? null,
                    'ai_summary'   => $result['ai_summary'] ?? null,
                    'sentiment'    => $result['sentiment'] ?? 'Neutral',
                    'category'     => $result['category'] ?? 'General',
                    'impact_score' => $result['impact_score'] ?? 5,
                    'ai_processed' => true,
                ]);
                $this->info("✅ Successfully rewritten and saved: ID {$news->id}");
            } else {
                $this->error("⚠️ Failed to parse AI response for ID: {$news->id}");
            }

            sleep(5); // حماية الحصة المجانية
        }

        $this->info('AI Journalism Cycle Completed. 🚀');
    }

    private function analyzeWithGemini($title, $content,$apiKey)
    {
        // استخدام الإصدار الثابت بناءً على توصيتك
       $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}";

        // الـ Prompt العبقري الخاص بك
        $prompt = "You are the lead crypto journalist for CryptoHub.
        Rewrite the provided cryptocurrency news article into a professional English news article.
        
        Rules:
        - English language only.
        - Do not translate word by word.
        - Rewrite the entire article with a new structure and professional journalistic style.
        - Preserve verified facts, names, numbers, dates and quotes.
        - Do not invent information.
        - Do not mention the original source in the rewritten content.
        - Optimize the title for SEO.
        - Make the article suitable for publication on a global crypto news website.
        
        CRITICAL INSTRUCTION: Return ONLY a valid JSON object. Do not wrap it in markdown blockquotes like ```json.
        
        Generate:
        {
          \"ai_title\": \"SEO optimized title here\",
          \"ai_content\": \"Full rewritten article (600-900 words when enough information exists) here\",
          \"ai_summary\": \"Short, engaging summary here\",
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
            $response = Http::timeout(60)->post($url, $payload);

            if ($response->successful()) {
                $responseText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                $cleanJson = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $responseText);
                $cleanJson = trim($cleanJson);

                $data = json_decode($cleanJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Invalid JSON from Gemini: ' . json_last_error_msg());
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