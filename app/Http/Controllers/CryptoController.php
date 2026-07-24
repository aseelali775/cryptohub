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
        // البحث عن العملة بغض النظر عن حالة الأحرف (كبيرة أم صغيرة)
        $crypto = Cryptocurrency::where('symbol', strtoupper($symbol))
            ->orWhere('symbol', strtolower($symbol))
            ->firstOrFail();

        return Inertia::render('Crypto/Show', [
            'crypto' => $crypto
        ]);
    }
}
