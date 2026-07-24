<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use Inertia\Inertia;

class CryptoController extends Controller
{
    /**
     * عرض صفحة الأسواق وقائمة العملات الكاملة
     */
    public function index()
    {
        // جلب أفضل 20 عملة مرتبة حسب القيمة السوقية
        $cryptos = Cryptocurrency::orderBy('market_cap', 'desc')->take(20)->get();

        return Inertia::render('Crypto/Prices', [
            'cryptos' => $cryptos
        ]);
    }

    /**
     * عرض صفحة تحليل وتفاصيل عملة واحدة عبر رمزها (مثل: BTC)
     */
   public function show($symbol)
    {
        // 1. البحث عن العملة في قاعدة البيانات المحلية
        $crypto = Cryptocurrency::where('symbol', strtoupper($symbol))
            ->orWhere('symbol', strtolower($symbol))
            ->firstOrFail();

        // 2. جلب بيانات الرسم البياني (آخر 7 أيام) والبيانات العميقة من CoinGecko
        // نستخدم الكاش لمدة 15 دقيقة لتخفيف الضغط على الـ API وسرعة فتح الصفحة
        $chartData = \Illuminate\Support\Facades\Cache::remember('chart_data_' . $crypto->symbol, 60 * 15, function () use ($crypto) {
            try {
                // استدعاء بيانات السعر والماركت كاب التفصيلية للعملة
                $response = \Illuminate\Support\Facades\Http::timeout(10)->get("https://api.coingecko.com/api/v3/coins/markets", [
                    'vs_currency' => 'usd',
                    'ids' => $crypto->coingecko_id,
                    'sparkline' => 'true', // 🟢 هذا الخيار يجلب بيانات السعر لآخر 7 أيام لعمل Chart
                ]);

                if ($response->successful() && count($response->json()) > 0) {
                    $coinData = $response->json()[0];
                    return [
                        'sparkline' => $coinData['sparkline_in_7d']['price'] ?? [],
                        'ath' => $coinData['ath'] ?? 0,
                        'atl' => $coinData['atl'] ?? 0,
                        'market_cap_rank' => $coinData['market_cap_rank'] ?? 'N/A',
                        'high_24h' => $coinData['high_24h'] ?? 0,
                        'low_24h' => $coinData['low_24h'] ?? 0,
                    ];
                }
            } catch (\Exception $e) {
                // تجاهل الخطأ في حالة فشل الاتصال وسنرسل مصفوفة فارغة
            }
            return ['sparkline' => [], 'ath' => 0, 'atl' => 0, 'market_cap_rank' => 'N/A', 'high_24h' => 0, 'low_24h' => 0];
        });

        // 3. إرسال العملة + بيانات الرسم البياني للواجهة
        return Inertia::render('Crypto/Show', [
            'crypto' => $crypto,
            'chartData' => $chartData
        ]);
    }
}
