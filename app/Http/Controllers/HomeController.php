<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\News;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * تجهيز خبر واحد بالهيكلة المطلوبة لواجهة Vue.
     */
    private function mapNewsItem($item): array
    {
        return [
            'id'           => $item->id,
            'image_url'    => $item->image_url,
            'source'       => $item->source,
            'url'          => $item->url,
            'sentiment'    => $item->sentiment ?? 'Neutral',
            'category'     => $item->category ?? 'General',
            'impact_score' => $item->impact_score ?? 5,
            'ai_processed' => (bool) $item->ai_processed,

            'date' => $item->created_at
                ? $item->created_at->diffForHumans()
                : '',

            'translations' => [
                'ar' => [
                    'title' => $item->title_ar
                        ?? $item->title_en,

                    'content' => $item->content_ar
                        ?? $item->content_en,

                    'summary' => $item->summary_ar
                        ?? (
                            $item->content_en
                                ? mb_substr(strip_tags($item->content_en), 0, 150) . '...'
                                : ''
                        ),

                    'why_it_matters' => $item->why_it_matters_ar,
                ],

                'en' => [
                    'title'   => $item->title_en,
                    'content' => $item->content_en,
                ],
            ],
        ];
    }

    /**
     * الصفحة الرئيسية للمنصة.
     */
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | شريط العملات
        |--------------------------------------------------------------------------
        |
        | نعرض مجموعة من العملات الموجودة فعلياً في بيانات السوق.
        | لا نربط هذا العدد بعدد صفحات Coin Hub المميزة.
        |
        */
        $tickerCryptos = Cryptocurrency::query()
            ->whereNotNull('current_price')
            ->orderByDesc('market_cap')
            ->take(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | العملات الأعلى ارتفاعاً
        |--------------------------------------------------------------------------
        */
        $topGainers = Cryptocurrency::query()
            ->whereNotNull('current_price')
            ->whereNotNull('change_24h')
            ->orderByDesc('change_24h')
            ->take(3)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | آخر الأخبار
        |--------------------------------------------------------------------------
        |
        | نعرض فقط الأخبار المنشورة والمعالجة.
        | هذا يمنع ظهور مسودات أو أخبار لم تكتمل معالجتها.
        |
        */
        $latestNews = News::query()
            ->where('status', 'published')
            ->where('ai_processed', true)
            ->whereNotNull('title_ar')
            ->where('title_ar', '!=', '')
            ->latest('created_at')
            ->take(4)
            ->get()
            ->map(function ($item) {
                return $this->mapNewsItem($item);
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | إحصائيات السوق العامة
        |--------------------------------------------------------------------------
        */
        $globalStats = Cache::get('market_global_stats', [
            'market_cap'        => 0,
            'volume'            => 0,
            'btc_dominance'     => 0,
            'active_coins'      => 0,
            'market_cap_change' => 0,
        ]);

        /*
        |--------------------------------------------------------------------------
        | مؤشر الخوف والطمع
        |--------------------------------------------------------------------------
        */
        $fearGreed = Cache::get('fear_greed_index', [
            'value'          => 50,
            'classification' => 'Neutral',
        ]);

        /*
        |--------------------------------------------------------------------------
        | معلومات المنصة
        |--------------------------------------------------------------------------
        |
        | هذه المعلومات تستخدمها Homepage لإظهار هوية AQL Crypto
        | ومصادر البيانات بصورة واضحة.
        |
        */
        $platformInfo = [
            'featured_coin_hubs' => 20,
            'market_data_source' => 'CoinGecko',
            'fear_greed_source'  => 'Alternative.me',
        ];

        return Inertia::render('Home', [
            'tickerCryptos' => $tickerCryptos,
            'topGainers'    => $topGainers,
            'news'          => $latestNews,
            'globalStats'   => $globalStats,
            'fearGreed'     => $fearGreed,
            'platformInfo'  => $platformInfo,
        ]);
    }
}