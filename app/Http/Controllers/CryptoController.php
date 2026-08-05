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
        // 1. جلب العملة مع الأسماء البديلة (الـ Aliases)
        $crypto = Cryptocurrency::with('aliases')->where('symbol', strtoupper($symbol))->firstOrFail();

        // 2. تجميع كل مصطلحات البحث الممكنة لهذه العملة
        $searchTerms = array_unique(array_merge(
            [$crypto->name, $crypto->symbol],
            $crypto->aliases->pluck('alias')->toArray()
        ));

        // 3. جلب الأخبار الذكية الخاصة بالعملة (مطابقة ذكية)
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

        // 🟢 4. جلب تقرير الذكاء الاصطناعي (AI Report) وتخزينه في الكاش لمدة ساعة
        $aiReport = Cache::remember('coin_ai_report_' . $crypto->symbol, 3600, function () use ($crypto) {
            return $crypto->aiReports()->latest('generated_at')->first();
        });

        // 🟢 5. بيانات الشارت (مهمة جداً لأن واجهة Show.vue تطلبها كـ Required Prop)
        // إذا كان لديك دالة خاصة تجلب الشارت، يمكنك تعديل هذا الجزء. وضعنا قيماً افتراضية لمنع أخطاء الـ Vue.
        $chartData = [
            'sparkline' => $crypto->sparkline_in_7d ?? [], // مصفوفة أسعار لـ 7 أيام
            'ath'       => $crypto->ath ?? 0,
            'atl'       => $crypto->atl ?? 0,
        ];

        // 6. إرسال كل البيانات للواجهة
        return Inertia::render('Crypto/Show', [
            'crypto'    => $crypto,
            'chartData' => $chartData, // تمرير الشارت
            'coinNews'  => $coinNews,
            'aiReport'  => $aiReport,  // 🟢 تمرير التقرير الذكي
        ]);
    }
}