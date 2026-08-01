<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\News;
use Stichoza\GoogleTranslate\GoogleTranslate;

class FetchCryptoNews extends Command
{
    protected $signature = 'crypto:fetch-news';
    protected $description = 'Fetch crypto news via RSS, extract images dynamically, translate, and fallback gracefully.';

    public function handle()
    {
        $this->info('Starting automated news fetcher (Multi-Source Image Extractor Mode)...');

        try {
            $response = Http::timeout(15)->get('https://api.rss2json.com/v1/api.json', [
                'rss_url' => 'https://cointelegraph.com/rss'
            ]);

            if ($response->successful() && $response->json()['status'] === 'ok') {
                $newsItems = $response->json()['items'];
                
                // 🟢 ضمان ترتيب الأخبار من الأحدث للأقدم
                usort($newsItems, function($a, $b) {
                    return strtotime($b['pubDate'] ?? 'now') <=> strtotime($a['pubDate'] ?? 'now');
                });

                $this->info("RSS returned: " . count($newsItems) . " articles (Sorted by newest)");
                
                $count = 0;

                foreach ($newsItems as $item) {
                    if ($count >= 5) break;

                    $exists = News::where('title_en', $item['title'])->exists();

                    if (!$exists) {
                        $this->info("Processing: " . $item['title']);
                        
                        try {
                            $content_en = strip_tags($item['description'] ?? $item['content'] ?? '');
                            $safe_content_en = mb_substr($content_en, 0, 1500); 
                            
                            // 🟢 استخراج الصورة باستخدام الدالة الذكية متعددة المصادر
                            $imageUrl = $this->extractImage($item);

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
                                'image_url'  => $imageUrl, // 👈 حفظ الرابط المستخرج بدقة
                                'source'     => 'CoinTelegraph',
                            ]);
                            
                            $count++;
                            
                        } catch (\Exception $e) {
                            $this->error("Failed to save article: " . $e->getMessage());
                        } finally {
                            sleep(5);
                        }
                    }
                }

                $this->info("Done! {$count} new articles processed with images successfully. ✅");
            } else {
                $this->error('Failed to fetch RSS Feed. API Status was not OK.');
            }
        } catch (\Exception $e) {
            $this->error('Critical Error during fetching: ' . $e->getMessage());
        }
    }

    /**
     * 🟢 دالة هندسية متقدمة لاستخراج صورة الخبر من عدة مصادر محتملة في الـ RSS
     */
    private function extractImage($item)
    {
        // 1. البحث في الحقل المباشر thumbnail
        if (!empty($item['thumbnail'])) {
            return $item['thumbnail'];
        }

        // 2. البحث في حقل الـ enclosure
        if (!empty($item['enclosure']['link'])) {
            return $item['enclosure']['link'];
        }

        // 3. استخراج أول رابط صورة من كود الـ HTML داخل الوصف أو المحتوى
        $htmlContent = $item['description'] ?? $item['content'] ?? '';
        if (!empty($htmlContent)) {
            preg_match('/<img[^>]+src="([^">]+)"/', $htmlContent, $matches);
            if (!empty($matches[1])) {
                return $matches[1];
            }
        }

        // 4. صورة افتراضية في حال لم يتم العثور على أي صورة نهائياً
        return 'https://cryptologos.cc/logos/bitcoin-btc-logo.png';
    }
}