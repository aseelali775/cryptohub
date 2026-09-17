<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\News;
use App\Services\NewsFormatterService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CryptoController extends Controller
{
    /**
     * صفحة جميع العملات
     */
    public function index()
    {
        $query = Cryptocurrency::query();

        /*
        |--------------------------------------------------------------------------
        | البحث
        |--------------------------------------------------------------------------
        */

        if (request()->filled('search')) {
            $search = trim(request('search'));

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%");
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | الفلاتر
        |--------------------------------------------------------------------------
        */

        $filter = request('filter', 'all');

        if ($filter === 'gainers') {

            $query->where('change_24h', '>', 0);

        } elseif ($filter === 'losers') {

            $query->where('change_24h', '<', 0);

        } elseif ($filter === 'mega') {

            /*
             * Mega Caps:
             * أعلى العملات من حيث القيمة السوقية.
             *
             * لا نحدد أسماء ثابتة هنا حتى يبقى الفلتر
             * قائمًا على بيانات السوق الفعلية.
             */

            $query->whereNotNull('market_cap')
                ->orderBy('market_cap', 'desc');

        }

        /*
        |--------------------------------------------------------------------------
        | الترتيب
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'market_cap',
            'current_price',
            'change_24h',
            'volume_24h',
        ];

        $sort = request('sort', 'market_cap');

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'market_cap';
        }

        $direction = request('direction', 'desc');

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        /*
        |--------------------------------------------------------------------------
        | ترتيب الفلاتر الخاصة
        |--------------------------------------------------------------------------
        */

        if ($filter === 'gainers') {
            $sort = 'change_24h';
            $direction = 'desc';
        }

        if ($filter === 'losers') {
            $sort = 'change_24h';
            $direction = 'asc';
        }

        if ($filter === 'mega') {
            $sort = 'market_cap';
            $direction = 'desc';
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        |
        | 25 عملة في الصفحة
        |
        */

        $cryptos = $query
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | إرسال البيانات إلى Vue
        |--------------------------------------------------------------------------
        */

        return Inertia::render('Crypto/Prices', [
            'cryptos' => $cryptos,

            'filters' => [
                'search' => request('search', ''),
                'filter' => $filter,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * صفحة العملة الفردية
     */
    public function show($symbol)
    {
        $crypto = Cryptocurrency::with('aliases')
            ->where('symbol', strtoupper($symbol))
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | معالجة بيانات الشارت
        |--------------------------------------------------------------------------
        */

        $sparkline = $crypto->sparkline_in_7d
            ?? $crypto->sparkline
            ?? [];

        if (is_string($sparkline)) {
            $sparkline = json_decode($sparkline, true) ?: [];
        }

        /*
        |--------------------------------------------------------------------------
        | بيانات احتياطية للشارت
        |--------------------------------------------------------------------------
        */

        if (empty($sparkline) && $crypto->current_price > 0) {

            $price = (float) $crypto->current_price;

            $sparkline = [
                $price * 0.97,
                $price * 0.99,
                $price * 0.98,
                $price * 1.01,
                $price,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | بيانات النطاق التاريخي
        |--------------------------------------------------------------------------
        */

        $chartData = [
            'sparkline' => $sparkline,

            'ath' => $crypto->ath
                ?? $crypto->high_24h
                ?? ($crypto->current_price * 1.15),

            'atl' => $crypto->atl
                ?? $crypto->low_24h
                ?? ($crypto->current_price * 0.85),
        ];

        /*
        |--------------------------------------------------------------------------
        | البحث عن أخبار العملة
        |--------------------------------------------------------------------------
        */

        $searchTerms = array_unique(
            array_merge(
                [
                    $crypto->name,
                    $crypto->symbol,
                ],
                $crypto->aliases
                    ->pluck('alias')
                    ->toArray()
            )
        );

        $coinNews = Cache::remember(
            'coin_news_' . $crypto->symbol,
            1800,
            function () use ($searchTerms) {

                return News::where('ai_processed', true)
                    ->where(function ($query) use ($searchTerms) {

                        foreach ($searchTerms as $term) {

                            $query
                                ->orWhereJsonContains(
                                    'keywords',
                                    $term
                                )
                                ->orWhere(
                                    'title_en',
                                    'LIKE',
                                    "%{$term}%"
                                );
                        }

                    })
                    ->latest()
                    ->take(6)
                    ->get()
                    ->map(function ($item) {

                        return NewsFormatterService::format($item);

                    });
            }
        );

        /*
        |--------------------------------------------------------------------------
        | تقرير الذكاء الاصطناعي
        |--------------------------------------------------------------------------
        */

        $aiReport = Cache::remember(
            'coin_ai_report_' . $crypto->symbol,
            3600,
            function () use ($crypto) {

                return $crypto
                    ->aiReports()
                    ->latest('generated_at')
                    ->first();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | صفحة العملة
        |--------------------------------------------------------------------------
        */

        return Inertia::render('Crypto/Show', [
            'crypto' => $crypto,
            'chartData' => $chartData,
            'coinNews' => $coinNews,
            'aiReport' => $aiReport,
        ]);
    }
}