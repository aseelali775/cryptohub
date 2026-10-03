<?php

namespace App\Http\Controllers;

use App\Models\AcademyArticle;
use App\Models\AcademyTopic;
use App\Models\Cryptocurrency;
use App\Models\News;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xmlContent = Cache::remember('sitemap_xml', 3600, function () {
            $baseUrl = rtrim(config('app.url', url('/')), '/');

            /*
            |--------------------------------------------------------------------------
            | Latest dates
            |--------------------------------------------------------------------------
            */

            $latestNewsUpdated = News::query()
                ->where('ai_processed', true)
                ->whereNotNull('title_ar')
                ->where('title_ar', '!=', '')
                ->latest('updated_at')
                ->value('updated_at');

            $latestNewsDate = $latestNewsUpdated
                ? $latestNewsUpdated->toAtomString()
                : now()->toAtomString();

            $latestCryptoUpdated = Cryptocurrency::latest('updated_at')
                ->value('updated_at');

            $latestCryptoDate = $latestCryptoUpdated
                ? $latestCryptoUpdated->toAtomString()
                : now()->toAtomString();

            $latestAcademyUpdated = AcademyArticle::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->latest('updated_at')
                ->value('updated_at');

            $latestAcademyDate = $latestAcademyUpdated
                ? $latestAcademyUpdated->toAtomString()
                : now()->toAtomString();

            /*
            |--------------------------------------------------------------------------
            | Static pages
            |--------------------------------------------------------------------------
            */

            $staticUrls = [
                [
                    'url' => $baseUrl,
                    'lastmod' => $latestNewsDate,
                ],
                [
                    'url' => $baseUrl . '/prices',
                    'lastmod' => $latestCryptoDate,
                ],
                [
                    'url' => $baseUrl . '/news',
                    'lastmod' => $latestNewsDate,
                ],
                [
                    'url' => $baseUrl . '/academy',
                    'lastmod' => $latestAcademyDate,
                ],
                [
                    'url' => $baseUrl . '/ai-market',
                    'lastmod' => $latestNewsDate,
                ],
            ];

            /*
            |--------------------------------------------------------------------------
            | Legal / informational pages
            |--------------------------------------------------------------------------
            */

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
                    'lastmod' => now()->toAtomString(),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Academy topics
            |--------------------------------------------------------------------------
            */

            $academyTopics = AcademyTopic::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get([
                    'id',
                    'slug',
                    'updated_at',
                ]);

            $academyTopicUrls = [];

            foreach ($academyTopics as $topic) {
                $academyTopicUrls[] = [
                    'url' => $baseUrl . '/academy/' . $topic->slug,
                    'lastmod' => $topic->updated_at
                        ? $topic->updated_at->toAtomString()
                        : $latestAcademyDate,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Published Academy articles
            |--------------------------------------------------------------------------
            */

            $academyArticles = AcademyArticle::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereHas('topic', function ($query) {
                    $query->where('is_active', true);
                })
                ->with('topic:id,slug')
                ->orderBy('sort_order')
                ->get([
                    'id',
                    'topic_id',
                    'slug',
                    'updated_at',
                    'published_at',
                ]);

            $academyArticleUrls = [];

            foreach ($academyArticles as $article) {
                if (!$article->topic) {
                    continue;
                }

                $lastmod = $article->updated_at
                    ?? $article->published_at
                    ?? now();

                $academyArticleUrls[] = [
                    'url' => $baseUrl
                        . '/academy/'
                        . $article->topic->slug
                        . '/'
                        . $article->slug,

                    'lastmod' => $lastmod->toAtomString(),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Published news
            |--------------------------------------------------------------------------
            |
            | News table does not have published_at.
            | ai_processed + valid Arabic title are used as the
            | current published-content criteria.
            |
            */

            $articles = News::query()
                ->where('ai_processed', true)
                ->whereNotNull('title_ar')
                ->where('title_ar', '!=', '')
                ->select('id', 'slug', 'updated_at')
                ->latest('updated_at')
                ->get();

            $newsUrls = [];

            foreach ($articles as $article) {
                $cleanSlug = $article->slug
                    ? preg_replace(
                        '/-' . preg_quote($article->id, '/') . '$/',
                        '',
                        $article->slug
                    )
                    : '';

                $newsUrl = $baseUrl
                    . '/news/'
                    . $article->id
                    . ($cleanSlug ? '-' . $cleanSlug : '');

                $lastmod = $article->updated_at
                    ?? now();

                $newsUrls[] = [
                    'url' => $newsUrl,
                    'lastmod' => $lastmod->toAtomString(),
                ];
            }

                       /*
            |--------------------------------------------------------------------------
            | Featured Cryptocurrency pages
            |--------------------------------------------------------------------------
            |
            | فقط العملات الأساسية العشرون تظهر كصفحات Coin Hub
            | مستقلة في Sitemap.
            |
            | جميع العملات الأخرى تبقى موجودة في قاعدة البيانات
            | ومتاحة من صفحة الأسعار /prices.
            |
            */

            $featuredCryptoSymbols = [
                'btc',
                'eth',
                'xrp',
                'usdt',
                'bnb',
                'usdc',
                'sol',
                'trx',
                'zec',
                'hype',
                'doge',
                'xmr',
                'link',
                'ada',
                'xlm',
                'qnt',
                'sui',
                'bch',
                'ltc',
                'avax',
            ];

            $coins = Cryptocurrency::query()
                ->whereIn('symbol', $featuredCryptoSymbols)
                ->select('symbol', 'updated_at')
                ->get();

            $coinUrls = [];

            foreach ($coins as $coin) {
                $symbol = strtolower(trim((string) $coin->symbol));

                if ($symbol === '') {
                    continue;
                }

                $coinUrls[] = [
                    'url' => $baseUrl . '/crypto/' . $symbol,
                    'lastmod' => $coin->updated_at
                        ? $coin->updated_at->toAtomString()
                        : $latestCryptoDate,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Merge all URLs
            |--------------------------------------------------------------------------
            */

            $urls = array_merge(
                $staticUrls,
                $academyTopicUrls,
                $academyArticleUrls,
                $newsUrls,
                $coinUrls
            );

            /*
            |--------------------------------------------------------------------------
            | Build XML
            |--------------------------------------------------------------------------
            */

            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            foreach ($urls as $item) {
                $loc = htmlspecialchars(
                    $item['url'],
                    ENT_XML1 | ENT_QUOTES,
                    'UTF-8'
                );

                $xml .= '<url>';
                $xml .= '<loc>' . $loc . '</loc>';
                $xml .= '<lastmod>' . $item['lastmod'] . '</lastmod>';
                $xml .= '</url>';
            }

            $xml .= '</urlset>';

            return $xml;
        });

        return response($xmlContent, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function news(): Response
    {
        $xmlContent = Cache::remember('news_sitemap_xml', 900, function () {
            $baseUrl = rtrim(config('app.url', url('/')), '/');

            $articles = News::query()
                ->where('ai_processed', true)
                ->whereNotNull('title_ar')
                ->where('title_ar', '!=', '')
                ->where('created_at', '>=', now()->subHours(48))
                ->select('id', 'slug', 'title_ar', 'created_at')
                ->latest('created_at')
                ->get();

            $xml = '<?xml version="1.0" encoding="UTF-8"?>';

            $xml .= '<urlset ';
            $xml .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
            $xml .= 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';

            foreach ($articles as $article) {
                $cleanSlug = $article->slug
                    ? preg_replace(
                        '/-' . preg_quote($article->id, '/') . '$/',
                        '',
                        $article->slug
                    )
                    : '';

                $articleUrl = $baseUrl
                    . '/news/'
                    . $article->id
                    . ($cleanSlug ? '-' . $cleanSlug : '');

                $publicationDate = $article->created_at
                    ? $article->created_at->toAtomString()
                    : now()->toAtomString();

                $title = htmlspecialchars(
                    trim(strip_tags($article->title_ar)),
                    ENT_XML1 | ENT_QUOTES,
                    'UTF-8'
                );

                $loc = htmlspecialchars(
                    $articleUrl,
                    ENT_XML1 | ENT_QUOTES,
                    'UTF-8'
                );

                $xml .= '<url>';
                $xml .= '<loc>' . $loc . '</loc>';
                $xml .= '<news:news>';
                $xml .= '<news:publication>';
                $xml .= '<news:name>Aql Crypto</news:name>';
                $xml .= '<news:language>ar</news:language>';
                $xml .= '</news:publication>';
                $xml .= '<news:publication_date>' . $publicationDate . '</news:publication_date>';
                $xml .= '<news:title>' . $title . '</news:title>';
                $xml .= '</news:news>';
                $xml .= '</url>';
            }

            $xml .= '</urlset>';

            return $xml;
        });

        return response($xmlContent, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }
}