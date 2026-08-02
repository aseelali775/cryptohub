<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // 👈 استدعاء مكتبة Str
use App\Models\News;
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;

class FetchCryptoNews extends Command
{
    protected $signature = 'crypto:fetch-news';
    protected $description = 'Fetch crypto news from multiple sources, extract full articles, and display success rate.';

    protected $headers = [
        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
    ];

    public function handle()
    {
        $this->info('Starting automated news fetcher (Phase 2.2 - Enterprise Multi-Source Mode)...');

        $sources = [
            'CoinTelegraph'   => 'https://cointelegraph.com/rss',
            'CoinDesk'        => 'https://www.coindesk.com/arc/outboundfeeds/rss/',
            'Decrypt'         => 'https://decrypt.co/feed',
            'BitcoinMagazine' => 'https://bitcoinmagazine.com/feed', // 👈 1. تصحيح الرابط
        ];

        $totalSuccess = 0;
        $totalFallback = 0;

        foreach ($sources as $sourceName => $rssUrl) {
            $this->info("Fetching RSS from: {$sourceName}");

            try {
                $xmlString = Http::timeout(20)->get($rssUrl)->body();
                $xml = @simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
                
                if ($xml && isset($xml->channel->item)) {
                    $json = json_encode($xml->channel->item);
                    $newsItems = json_decode($json, true);
                    
                    $count = 0;

                    if (isset($newsItems['title'])) {
                        $newsItems = [$newsItems];
                    }

                    usort($newsItems, function($a, $b) {
                        return strtotime($b['pubDate'] ?? 'now') <=> strtotime($a['pubDate'] ?? 'now');
                    });

                    foreach ($newsItems as $item) {
                        if ($count >= 3) break; 

                        $title = is_array($item['title']) ? ($item['title'][0] ?? '') : $item['title'];
                        $link = is_array($item['link']) ? ($item['link'][0] ?? '') : $item['link'];

                        if (empty($title) || empty($link)) continue;

                        $exists = News::where('url', $link)->orWhere('title_en', $title)->exists();

                        if (!$exists) {
                            $this->info("Processing: " . $title);
                            
                            $imageUrl = $this->extractImage($item);
                            $fullContent = $this->extractFullArticle($link);
                            $isSuccess = $fullContent ? true : false;

                            if (!$isSuccess) {
                                $fullContent = strip_tags(is_array($item['description'] ?? '') ? ($item['description'][0] ?? '') : ($item['description'] ?? ''));
                            }

                            // 👈 3. الحد الأقصى للنص 15000 حرف لتجنب أخطاء قاعدة البيانات
                            $safe_content_en = Str::limit(trim(preg_replace('/\s+/', ' ', $fullContent)), 15000, '');

                            // 👈 2. فلترة المقالات الفارغة والقصيرة جداً
                            if (strlen($safe_content_en) < 100) {
                                Log::warning("Skipped (Too Short): {$sourceName} - {$link}");
                                continue; 
                            }

                            // تسجيل الإحصائية فقط إذا اجتاز الخبر فلتر الطول
                            if ($isSuccess) {
                                Log::info("Extraction Success: {$sourceName} - {$link}");
                                $totalSuccess++;
                            } else {
                                Log::warning("Extraction Fallback: {$sourceName} - {$link}");
                                $totalFallback++;
                            }

                           News::create([
                                'title_en'   => $title,
                                'content_en' => $safe_content_en,
                                // 🟢 وضع نص مؤقت بدلاً من null لحين مرور الذكاء الاصطناعي عليها
                                'title_ar'   => '[ جاري الترجمة والتلخيص بالذكاء الاصطناعي 🤖 ] ' . $title,
                                'content_ar' => '[ سيتم إضافة الملخص الذكي قريباً ]',
                                'image_url'  => $imageUrl,
                                'source'     => $sourceName,
                                'url'        => $link,
                            ]);
                            
                            $count++;
                            usleep(500000); 
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Failed to process source {$sourceName}: " . $e->getMessage());
                Log::error("Scraper Error ({$sourceName}): " . $e->getMessage());
            }
        }

        // 👈 4. حساب وعرض نسبة النجاح المئوية
        $total = $totalSuccess + $totalFallback;
        $rate = $total > 0 ? round(($totalSuccess / $total) * 100, 2) : 0;

        $this->info("====================================");
        $this->info("Extraction Success : {$totalSuccess} ✅");
        $this->warn("Extraction Fallback: {$totalFallback} ⚠️");
        $this->info("Success Rate       : {$rate}% 📊");
        $this->info("====================================");
    }

    private function extractImage($item)
    {
        if (isset($item['enclosure']['@attributes']['url'])) {
            return $item['enclosure']['@attributes']['url'];
        }
        $htmlContent = is_array($item['description'] ?? '') ? ($item['description'][0] ?? '') : ($item['description'] ?? '');
        if (!empty($htmlContent)) {
            preg_match('/<img[^>]+src="([^">]+)"/', $htmlContent, $matches);
            if (!empty($matches[1])) return $matches[1];
        }
        return 'https://cryptologos.cc/logos/bitcoin-btc-logo.png';
    }

    private function extractFullArticle($url)
    {
        try {
            $response = Http::withHeaders($this->headers)->timeout(20)->get($url);
            $html = $response->body();
            
            if (
                str_contains($html, 'Cloudflare') ||
                str_contains($html, 'Access Denied') ||
                str_contains($html, 'verify you are human') ||
                str_contains($html, 'Just a moment...')
            ) {
                return null;
            }
            
            $configuration = new Configuration();
            $configuration->setFixRelativeURLs(true);
            $configuration->setOriginalURL($url);

            $readability = new Readability($configuration);
            
            if (!$readability->parse($html)) {
                return null;
            }
            
            $content = trim(strip_tags($readability->getContent()));
            return strlen($content) > 200 ? $content : null;
            
        } catch (ParseException $e) {
            return null; 
        } catch (\Exception $e) {
            return null; 
        }
    }
}