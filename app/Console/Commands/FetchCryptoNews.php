<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\News;
use Stichoza\GoogleTranslate\GoogleTranslate;

class FetchCryptoNews extends Command
{
    protected $signature = 'crypto:fetch-news';
    protected $description = 'Fetch crypto news via RSS, try translating, and fallback gracefully.';

    public function handle()
    {
        $this->info('Starting automated news fetcher (Production-Ready Mode)...');

        try {
            $response = Http::timeout(15)->get('https://api.rss2json.com/v1/api.json', [
                'rss_url' => 'https://cointelegraph.com/rss'
            ]);

            if ($response->successful() && $response->json()['status'] === 'ok') {
                $newsItems = $response->json()['items'];
                
                // 🟢 3. سجل يوضح عدد الأخبار القادمة من المصدر
                $this->info("RSS returned: " . count($newsItems) . " articles");
                
                $count = 0;

                foreach ($newsItems as $item) {
                    if ($count >= 5) break;

                    $exists = News::where('title_en', $item['title'])->exists();

                    if (!$exists) {
                        $this->info("Processing: " . $item['title']);
                        
                        try {
                            // 🟢 4. تقليل حجم النص إلى 1500 حرف لتجنب الحظر
                            $content_en = strip_tags($item['description'] ?? $item['content'] ?? '');
                            $safe_content_en = mb_substr($content_en, 0, 1500); 
                            
                            // 🟢 2. إنشاء كائن الترجمة داخل الحلقة لتجنب تتبع الجلسة
                            $tr = new GoogleTranslate('ar'); 

                            try {
                                $title_ar = $tr->translate($item['title']);
                                $content_ar = $tr->translate($safe_content_en);
                            } catch (\Exception $e) {
                                $this->warn("⚠️ Translation failed. Saving English version as fallback.");
                                $title_ar = '[EN] ' . $item['title']; 
                                $content_ar = $safe_content_en; 
                            }

                            News::create([
                                'title_en'   => $item['title'],
                                'title_ar'   => $title_ar,
                                'content_en' => $safe_content_en,
                                'content_ar' => $content_ar,
                                'image_url'  => !empty($item['thumbnail']) ? $item['thumbnail'] : 'https://cryptologos.cc/logos/bitcoin-btc-logo.png',
                                'source'     => 'CoinTelegraph',
                            ]);
                            
                            $count++;
                            
                        } catch (\Exception $e) {
                            $this->error("Failed to save article: " . $e->getMessage());
                        } finally {
                            // 🟢 1. التأكد من تنفيذ الاستراحة دائماً، حتى لو فشل الحفظ في قاعدة البيانات
                            sleep(5);
                        }
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