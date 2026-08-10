<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Cryptocurrency;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        // استخدام الكاش لمدة ساعة (3600 ثانية) لتخفيف الضغط على السيرفر
        $xmlContent = Cache::remember('sitemap_xml', 3600, function () {
            
            // قراءة النطاق الأساسي من ملف الإعدادات (config/app.php -> APP_URL)
            $baseUrl = rtrim(config('app.url', url('/')), '/');

            // --- جلب تواريخ آخر تحديثات حقيقية من قاعدة البيانات ---
            $latestNewsUpdated = News::latest('updated_at')->value('updated_at');
            $latestNewsDate = $latestNewsUpdated 
                ? $latestNewsUpdated->toAtomString() 
                : now()->toAtomString();

            $latestCryptoUpdated = Cryptocurrency::latest('updated_at')->value('updated_at');
            $latestCryptoDate = $latestCryptoUpdated 
                ? $latestCryptoUpdated->toAtomString() 
                : now()->toAtomString();
            // ----------------------------------------------------

            // 1. الصفحات الرئيسية والأساسية (ربط lastmod بالتاريخ الفعلي للبيانات)
            $staticUrls = [
                [
                    'url' => $baseUrl,
                    'lastmod' => $latestNewsDate, // تاريخ آخر خبر
                    'changefreq' => 'daily',
                    'priority' => '1.0',
                ],
                [
                    'url' => $baseUrl . '/prices',
                    'lastmod' => $latestCryptoDate, // تاريخ آخر تحديث للأسعار
                    'changefreq' => 'hourly',
                    'priority' => '0.9',
                ],
                [
                    'url' => $baseUrl . '/news',
                    'lastmod' => $latestNewsDate, // تاريخ آخر خبر
                    'changefreq' => 'hourly',
                    'priority' => '0.9',
                ],
                [
                    'url' => $baseUrl . '/ai-market',
                    'lastmod' => $latestNewsDate,
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ],
            ];

            // 2. الصفحات القانونية والمعلوماتية (تتحدث شهرياً/ثابتة)
            $legalPages = [
                '/about',
                '/contact',
                '/privacy-policy',
                '/terms-of-use',
                '/disclaimer',
                '/editorial-policy',
            ];

            foreach ($legalPages as $page) {
                $staticUrls[] = [
                    'url' => $baseUrl . $page,
                    'lastmod' => now()->startOfMonth()->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ];
            }

            // 3. جلب جميع الأخبار (روابط نظيفة مع تاريخ updated_at الفعلي للخبر)
            $newsUrls = [];
            $articles = News::select('id', 'slug', 'updated_at')->latest()->get();
            
            foreach ($articles as $article) {
                // تنظيف الـ Slug وإزالة الـ ID المكرر من نهايته إن وجد
                $cleanSlug = $article->slug ? preg_replace('/-' . $article->id . '$/', '', $article->slug) : '';
                
                $newsUrls[] = [
                    'url' => $baseUrl . '/news/' . $article->id . ($cleanSlug ? '-' . $cleanSlug : ''),
                    'lastmod' => $article->updated_at ? $article->updated_at->toAtomString() : $latestNewsDate,
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }

            // 4. جلب تفاصيل العملات
            $coinUrls = [];
            $coins = Cryptocurrency::select('symbol', 'updated_at')->get();
            foreach ($coins as $coin) {
                $coinUrls[] = [
                    'url' => $baseUrl . '/crypto/' . strtolower($coin->symbol),
                    'lastmod' => $coin->updated_at ? $coin->updated_at->toAtomString() : $latestCryptoDate,
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ];
            }

            // دمج كافة الروابط
            $urls = array_merge($staticUrls, $newsUrls, $coinUrls);

            // بناء محتوى ملف XML
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            foreach ($urls as $item) {
                $xml .= '<url>';
                $xml .= '<loc>' . htmlspecialchars($item['url']) . '</loc>';
                $xml .= '<lastmod>' . $item['lastmod'] . '</lastmod>';
                $xml .= '<changefreq>' . $item['changefreq'] . '</changefreq>';
                $xml .= '<priority>' . $item['priority'] . '</priority>';
                $xml .= '</url>';
            }

            $xml .= '</urlset>';

            return $xml;
        });

        return response($xmlContent, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}