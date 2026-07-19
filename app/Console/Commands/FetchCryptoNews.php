<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\News;
use Stichoza\GoogleTranslate\GoogleTranslate;

class FetchCryptoNews extends Command
{
    protected $signature = 'crypto:fetch-news';
    protected $description = 'Fetch latest crypto news via RSS, translate them to Arabic, and store in database';

    public function handle()
    {
        $this->info('Starting automated news fetcher (RSS Mode)...');

        try {
            // نستخدم خدمة RSS-to-JSON المجانية تماماً لتحويل تغذية موقع CoinTelegraph العالمي
            $response = Http::timeout(15)->get('https://api.rss2json.com/v1/api.json', [
                'rss_url' => 'https://cointelegraph.com/rss'
            ]);

            if ($response->successful() && $response->json()['status'] === 'ok') {
                $newsItems = $response->json()['items'];
                $count = 0;
                
                // تهيئة المترجم الآلي للغة العربية
                $tr = new GoogleTranslate('ar'); 

                foreach ($newsItems as $item) {
                    // 🟢 تم التعديل: سحب 15 خبراً لتغذية زر "عرض المزيد" في الواجهة
                    if ($count >= 15) break;

                    // التحقق من عدم وجود الخبر مسبقاً
                    $exists = News::where('title_en', $item['title'])->exists();

                    if (!$exists) {
                        $this->info("Fetching and translating: " . $item['title']);
                        
                        // تنظيف الوصف من أي وسوم HTML واستخراج نص نقي
                        $rawDescription = strip_tags($item['description'] ?? $item['content']);
                        
                        // 🟢 تم التعديل: إلغاء Str::limit تماماً لأخذ كامل النص المتاح من المصدر
                        $content_en = trim(preg_replace('/\s+/', ' ', $rawDescription));
                        
                        // الترجمة الآلية
                        $title_ar = $tr->translate($item['title']);
                        $content_ar = $tr->translate($content_en);

                        // الحفظ في قاعدة البيانات
                        News::create([
                            'title_en'   => $item['title'],
                            'title_ar'   => $title_ar,
                            'content_en' => $content_en,
                            'content_ar' => $content_ar,
                            'image_url'  => !empty($item['thumbnail']) ? $item['thumbnail'] : 'https://cryptologos.cc/logos/bitcoin-btc-logo.png',
                            'source'     => 'CoinTelegraph',
                        ]);
                        
                        $count++;
                        // إيقاف مؤقت لمدة ثانية لتجنب حظر الـ IP الخاص بجوجل للترجمة
                        sleep(1); 
                    }
                }

                $this->info("Done! {$count} new articles automated successfully. ✅");
            } else {
                $this->error('Failed to fetch RSS Feed. API Status was not OK.');
                $this->error('Details: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('Error during fetching/translating: ' . $e->getMessage());
        }
    }
}