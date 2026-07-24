<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\News;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * عرض الصفحة الرئيسية للمنصة (Home)
     */
   public function index()
    {
        $tickerCryptos = Cryptocurrency::take(8)->get();
        $topGainers = Cryptocurrency::orderBy('change_24h', 'desc')->take(3)->get();
        $latestNews = News::latest()->take(4)->get();

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
            'news'          => $latestNews,
            'globalStats'   => $globalStats, // 🟢 تم التمرير
            'fearGreed'     => $fearGreed    // 🟢 تم التمرير
        ]);
    }
}