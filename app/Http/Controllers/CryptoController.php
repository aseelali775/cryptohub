<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\News;
use App\Services\NewsFormatterService; // 🟢 استدعاء السيرفيس
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CryptoController extends Controller
{
    public function show($symbol)
    {
        // 1. جلب بيانات العملة
        $crypto = Cryptocurrency::where('symbol', strtoupper($symbol))->firstOrFail();

        // 2. جلب الأخبار الذكية الخاصة بالعملة (مع كاش لمدة 30 دقيقة)
        $coinNews = Cache::remember(
            'coin_news_' . $crypto->symbol,
            1800, // 30 دقيقة
            function () use ($crypto) {
                return News::where('ai_processed', true)
                    ->where(function($query) use ($crypto) {
                        $query->whereJsonContains('keywords', $crypto->name)
                              ->orWhereJsonContains('keywords', $crypto->symbol);
                    })
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function($item) {
                        return NewsFormatterService::format($item); // 🟢 استخدام السيرفيس النظيف
                    });
            }
        );

        // 3. إرسال البيانات للواجهة
        return Inertia::render('Crypto/Show', [
            'crypto'   => $crypto,
            'coinNews' => $coinNews // 🟢 إرسال الأخبار للواجهة
        ]);
    }
}