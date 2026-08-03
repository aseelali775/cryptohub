<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\News;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * دالة مساعدة لتغليف الأخبار بهيكلة الترجمات والذكاء الاصطناعي
     */
    private function mapNewsItem($item)
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
            'date'         => $item->created_at ? $item->created_at->diffForHumans() : '',
            
            // الهيكلة الذكية الجديدة للغات
            'translations' => [
                'ar' => [
                    'title'          => $item->title_ar ?? $item->title_en,
                    'content'        => $item->content_ar ?? $item->content_en,
                    'summary'        => $item->summary_ar ?? mb_substr($item->content_en, 0, 150) . '...',
                    'why_it_matters' => $item->why_it_matters_ar,
                ],
                'en' => [
                    'title'   => $item->title_en,
                    'content' => $item->content_en,
                ]
            ]
        ];
    }

    /**
     * عرض الصفحة الرئيسية للمنصة (Home)
     */
    public function index()
    {
        $tickerCryptos = Cryptocurrency::take(8)->get();
        $topGainers = Cryptocurrency::orderBy('change_24h', 'desc')->take(3)->get();
        
        // 🟢 جلب آخر 4 أخبار وتغليفها لتتوافق مع واجهة Vue الجديدة
        $latestNews = News::latest()->take(4)->get()->map(function($item) {
            return $this->mapNewsItem($item);
        });

        // 🟢 قراءة البيانات الحية من الكاش (مع قيم افتراضية في حال كانت فارغة)
        $globalStats = \Illuminate\Support\Facades\Cache::get('market_global_stats', [
            'market_cap' => 0, 'volume' => 0, 'btc_dominance' => 0, 'active_coins' => 0, 'market_cap_change' => 0
        ]);
        
        $fearGreed = \Illuminate\Support\Facades\Cache::get('fear_greed_index', [
            'value' => 50, 'classification' => 'Neutral'
        ]);

        return Inertia::render('Home', [
            'tickerCryptos' => $tickerCryptos,
            'topGainers'    => $topGainers,
            'news'          => $latestNews,  // 🟢 تم التمرير بالهيكلة الجديدة
            'globalStats'   => $globalStats, 
            'fearGreed'     => $fearGreed    
        ]);
    }
}