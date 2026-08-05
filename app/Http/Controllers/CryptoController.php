<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\News;
use App\Services\NewsFormatterService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CryptoController extends Controller
{
    public function show($symbol)
    {
        // 1. جلب العملة مع الأسماء البديلة
        $crypto = Cryptocurrency::with('aliases')->where('symbol', strtoupper($symbol))->firstOrFail();

        // 2. تجميع كل الكلمات التي تدل على هذه العملة
        $searchTerms = array_unique(array_merge(
            [$crypto->name, $crypto->symbol],
            $crypto->aliases->pluck('alias')->toArray()
        ));

        // 3. جلب الأخبار المرتبطة بأي كلمة من هذه الكلمات
        $coinNews = Cache::remember(
            'coin_news_' . $crypto->symbol,
            1800, // نصف ساعة
            function () use ($searchTerms) {
                return News::where('ai_processed', true)
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {

    $query->orWhereJsonContains('keywords', $term)

          ->orWhere('title_en', 'LIKE', "%{$term}%")

          ->orWhere('content_en', 'LIKE', "%{$term}%");

}
                    })
                    ->latest()
                    ->take(6)
                    ->get()
                    ->map(function($item) {
                        return NewsFormatterService::format($item);
                    });
            }
        );

        return Inertia::render('Crypto/Show', [
            'crypto'   => $crypto,
            'coinNews' => $coinNews,
        ]);
    }
}