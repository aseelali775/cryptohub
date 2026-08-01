<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\News;
use Stichoza\GoogleTranslate\GoogleTranslate;

class FetchCryptoNews extends Command
{
    protected $signature = 'crypto:fetch-news';
    protected $description = 'Fetch crypto news via RSS, try translating, and fallback to English gracefully if translation fails.';

    public function handle()
    {
        $this->info('Starting automated news fetcher (Fault-Tolerant Mode)...');

        try {
            $response = Http::timeout(15)->get('https://api.rss2json.com/v1/api.json', [
                'rss_url' => 'https://cointelegraph.com/rss'
            ]);

            if ($response->successful() && $response->json()['status'] === 'ok') {
                $newsItems = $response->json()['items'];
                $count = 0;
                
                $tr = new GoogleTranslate('ar'); 

                foreach ($newsItems as $item) {
                    // 🟢 1. الحد الأقصى للدفعة
                    if ($count >= 5) break;

                    $exists = News::where('title_en', $item['title'])->exists();

                    if (!$exists) {
                        $this->info("Processing: " . $item['title']);
                        
                        $content_en = strip_tags($item['description'] ?? $item['content'] ?? '');
                        $safe_content_en = mb_substr($content_en, 0, 4000); 
                        
                        // 🟢 2. محاولة الترجمة مع معالجة الأخطاء (Graceful Degradation)
                        try {
                            $title_ar = $tr->translate($item['title']);
                            $content_ar = $tr->translate($safe_content_en);
                        } catch (\Exception $e) {
                            $this->warn("⚠️ Translation failed (429 or other). Saving English version as fallback.");
                            
                            // نستخدم النص الإنجليزي كبديل مؤقت لحين معالجة الترجمة لاحقاً
                            // هذا يمنع انهيار قاعدة البيانات إذا كانت الأعمدة لا تقبل null
                            $title_ar = '[EN] ' . $item['title']; 
                            $content_ar = $safe_content_en; 
                        }

                        // 🟢 3. الحفظ في قاعدة البيانات (سيستمر دائماً سواء نجحت الترجمة أم لا)
                        News::create([
                            'title_en'   => $item['title'],
                            'title_ar'   => $title_ar,
                            'content_en' => $safe_content_en,
                            'content_ar' => $content_ar,
                            'image_url'  => !empty($item['thumbnail']) ? $item['thumbnail'] : 'https://cryptologos.cc/logos/bitcoin-btc-logo.png',
                            'source'     => 'CoinTelegraph',
                        ]);
                        
                        $count++;
                        
                        // 🟢 4. استراحة لتخفيف الضغط
                        sleep(5); 
                    }
                }

                $this->info("Done! {$count} new articles processed successfully. ✅");
            } else {
                $this->error('Failed to fetch RSS Feed. API Status was not OK.');
            }
        } catch (\Exception $e) {
            $this->error('Critical Error during fetching: ' . $e->getMessage());
        }
    }
}