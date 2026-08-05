<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\News;
use App\Services\NewsFormatterService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CryptoController extends Controller
{

   public function index()
    {
        // 🟢 استخدام market_cap بدلاً من market_cap_rank
        // الترتيب التنازلي للقيمة السوقية يعطينا نفس نتيجة الترتيب (البيتكوين أولاً ثم الإيثريوم...الخ)
        $cryptos = Cryptocurrency::orderBy('market_cap', 'desc')->paginate(50);

        return Inertia::render('Crypto/Prices', [
            'cryptos' => $cryptos
        ]);
    }  


    public function show($symbol)
    {
        $crypto = Cryptocurrency::with('aliases')->where('symbol', strtoupper($symbol))->firstOrFail();

        // 1. معالجة حقل الشارت وآمان الـ Array
        $sparkline = $crypto->sparkline_in_7d ?? $crypto->sparkline ?? [];
        if (is_string($sparkline)) {
            $sparkline = json_decode($sparkline, true) ?: [];
        }

        // 🟢 في حال عدم وجود بيانات شارت تاريخية، نولد مساراً شبيهاً بالسعر الحالي كي لا يختفي المخطط
        if (empty($sparkline) && $crypto->current_price > 0) {
            $price = (float) $crypto->current_price;
            $sparkline = [
                $price * 0.97, 
                $price * 0.99, 
                $price * 0.98, 
                $price * 1.01, 
                $price
            ];
        }

        // 2. تجهيز بيانات النطاق التاريخي مع حماية القيم الفارغة
        $chartData = [
            'sparkline' => $sparkline,
            'ath'       => $crypto->ath ?? $crypto->high_24h ?? ($crypto->current_price * 1.15),
            'atl'       => $crypto->atl ?? $crypto->low_24h ?? ($crypto->current_price * 0.85),
        ];

        // 3. جلب الأخبار والتقرير الذكي
        $searchTerms = array_unique(array_merge(
            [$crypto->name, $crypto->symbol],
            $crypto->aliases->pluck('alias')->toArray()
        ));

        $coinNews = Cache::remember('coin_news_' . $crypto->symbol, 1800, function () use ($searchTerms) {
            return News::where('ai_processed', true)
                ->where(function($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query->orWhereJsonContains('keywords', $term)
                              ->orWhere('title_en', 'LIKE', "%{$term}%"); 
                    }
                })->latest()->take(6)->get()->map(function($item) {
                    return NewsFormatterService::format($item);
                });
        });

        $aiReport = Cache::remember('coin_ai_report_' . $crypto->symbol, 3600, function () use ($crypto) {
            return $crypto->aiReports()->latest('generated_at')->first();
        });

        return Inertia::render('Crypto/Show', [
            'crypto'    => $crypto,
            'chartData' => $chartData,
            'coinNews'  => $coinNews,
            'aiReport'  => $aiReport,
        ]);
    }
}