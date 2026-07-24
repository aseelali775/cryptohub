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
        // 1. جلب عملات الشريط المتحرك العلوي (Ticker)
        $tickerCryptos = Cryptocurrency::take(8)->get();

        // 2. جلب أعلى 3 عملات صاعدة في 24 ساعة
        $topGainers = Cryptocurrency::orderBy('change_24h', 'desc')->take(3)->get();

        // 3. جلب أحدث 4 أخبار للرئيسية
        $latestNews = News::latest()->take(4)->get();

        return Inertia::render('Home', [
            'tickerCryptos' => $tickerCryptos,
            'topGainers'    => $topGainers,
            'news'          => $latestNews
        ]);
    }
}