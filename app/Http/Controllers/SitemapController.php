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
        // استخدام الكاش لمدة ساعة (3600 ثانية) لتخفيف الضغط على السيرفر (Railway)
        $xmlContent = Cache::remember('sitemap_xml', 3600, function () {
            
            // قراءة الرابط الأساسي للموقع
            $baseUrl = url('/');

            // 1. الصفحات الرئيسية والأساسية
            $staticUrls = [
                [
                    'url' => $baseUrl,
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '1.0',
                ],
                [
                    'url' => $baseUrl . '/prices',
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'hourly',
                    'priority' => '0.9',
                ],
                [
                    'url' => $baseUrl . '/news',
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'hourly',
                    'priority' => '0.9',
                ],
                [
                    'url' => $baseUrl . '/ai-market',
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ],
            ];

            // 2. الصفحات القانونية والمعلوماتية (تتحدث شهرياً فقط)
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
                    'lastmod' => now()->startOfMonth()->toAtomString(), // نستخدم بداية الشهر لأنها ثابتة
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ];
            }

            // 3. جلب جميع الأخبار (باستخدام Select لتوفير استهلاك الذاكرة RAM)
          // 3. جلب جميع الأخبار (باستخدام Select لتوفير استهلاك الذاكرة RAM)
            $newsUrls = [];
            $articles = News::select('id', 'slug', 'updated_at')->latest()->get();
            
            foreach ($articles as $article) {
                // تنظيف الـ Slug بإزالة الـ ID المكرر من نهايته (إذا كان موجوداً)
                $cleanSlug = $article->slug ? preg_replace('/-' . $article->id . '$/', '', $article->slug) : '';
                
                // بناء الرابط النظيف ليطابق مسارك: /news/{id}-{slug}
                $newsUrls[] = [
                    'url' => $baseUrl . '/news/' . $article->id . ($cleanSlug ? '-' . $cleanSlug : ''),
                    'lastmod' => $article->updated_at ? $article->updated_at->toAtomString() : now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
            // 4. جلب تفاصيل العملات (باستخدام Select)
            $coinUrls = [];
            $coins = Cryptocurrency::select('symbol', 'updated_at')->get();
            foreach ($coins as $coin) {
                // بناء الرابط ليطابق مسارك: /crypto/{symbol}
                $coinUrls[] = [
                    'url' => $baseUrl . '/crypto/' . strtolower($coin->symbol),
                    'lastmod' => $coin->updated_at ? $coin->updated_at->toAtomString() : now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ];
            }

            // دمج كافة الروابط في مصفوفة واحدة
            $urls = array_merge($staticUrls, $newsUrls, $coinUrls);

            // بناء محتوى ملف XML
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            foreach ($urls as $item) {
                $xml .= '<url>';
                // نستخدم htmlspecialchars لحماية الروابط التي قد تحتوي على رموز خاصة
                $xml .= '<loc>' . htmlspecialchars($item['url']) . '</loc>';
                $xml .= '<lastmod>' . $item['lastmod'] . '</lastmod>';
                $xml .= '<changefreq>' . $item['changefreq'] . '</changefreq>';
                $xml .= '<priority>' . $item['priority'] . '</priority>';
                $xml .= '</url>';
            }

            $xml .= '</urlset>';

            return $xml;
        });

        // إرجاع الاستجابة بنوع XML صريح ليفهمه Google
        return response($xmlContent, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}