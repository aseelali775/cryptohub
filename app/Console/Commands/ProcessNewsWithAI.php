<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessNewsWithAI extends Command
{
    protected $signature = 'news:process-ai';
    protected $description = 'Analyze crypto news to generate Arabic rewrites and market analysis using Gemini AI.';

    public function handle()
    {
        $apiKey = env('GEMINI_API_KEY');
        
        if (empty($apiKey)) {$this->error('GEMINI_API_KEY is missing in .env file.');
            return;
        }

        $this->info('Starting CryptoHub AI Arabic Journalist...');

        $newsList = News::where('ai_processed', false)->latest()->limit(5)->get();
        if ($newsList->isEmpty()) {$this->info('No new articles for the AI to rewrite. Resting... ☕');
            return;
        }

        foreach ($newsList as $news) {$this->info("AI is processing: {$news->title_en}");
            
            $truncatedContent = mb_substr($news->content_en, 0, 8000);$result = $this->analyzeWithGemini($news->title_en, $truncatedContent,$apiKey);

            if ($result && is_array($result)) {
                // 🟢 البرمجة الدفاعية في توليد الـ Slug
                $safeSlug = Str::slug($news->title_en ?: 'news-'.$news->id);
                
                $news->update([
                    'slug'              => $safeSlug,
                    'keywords'          => $result['keywords'] ?? [],
                    'title_ar'          => $result['title_ar'] ?? null,
                    'content_ar'        => $result['content_ar'] ?? null,
                    'summary_ar'        => $result['summary_ar'] ?? null,
                    'why_it_matters_ar' => $result['why_it_matters_ar'] ?? null,
                    'sentiment'         => $result['sentiment'] ?? 'Neutral',
                    'category'          => $result['category'] ?? 'General',
                    'impact_score'      => $result['impact_score'] ?? 5,
                    'ai_processed'      => true,
                ]);
                $this->info("✅ Successfully analyzed and saved: ID {$news->id}");
            } else {
                $this->error("⚠️ Failed to parse AI response for ID: {$news->id}");
            }

            sleep(5);
        }

        $this->info('AI Journalism Cycle Completed. 🚀');
    }

    private function analyzeWithGemini($title, $content,$apiKey)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}";

        $prompt = "Analyze the provided cryptocurrency news article.
        Generate a professional Arabic journalistic rewrite and market analysis.
        Keep the original English article unchanged (do not output English text).
        
        Rules:
        - Do not translate word by word. Write a fluent, professional Arabic news article.
        - Preserve verified facts, names, numbers, dates and quotes.
        - Write a complete article. Expand the content only when sufficient information is available. Do not invent facts.
        - Generate 3-5 SEO keywords. Use English keywords only. Return them as a JSON array.
        
        Generate a valid JSON object exactly like this:
        {
          \"title_ar\": \"SEO optimized title in Arabic\",
          \"content_ar\": \"Full professional article rewritten in Arabic\",
          \"summary_ar\": \"Short engaging summary in Arabic\",
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
                $cleanJson = trim(preg_replace('/```json\s*(.*?)\s*```/s', '$1', $responseText));
                $data = json_decode($cleanJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}