<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\News;
use App\Services\NewsFormatterService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Models\AcademyTopic;

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
    $requestedSymbol = trim((string) $symbol);
    $canonicalSymbol = strtolower($requestedSymbol);

    $crypto = Cryptocurrency::with('aliases')
        ->whereRaw('LOWER(symbol) = ?', [$canonicalSymbol])
        ->firstOrFail();

    /*
    |--------------------------------------------------------------------------
    | Canonical crypto URL
    |--------------------------------------------------------------------------
    |
    | The official URL must always use lowercase symbols.
    |
    | /crypto/BTC
    | /crypto/Btc
    | /crypto/bTc
    |
    |        ↓ 301
    |
    | /crypto/btc
    |--------------------------------------------------------------------------
    */

    if ($requestedSymbol !== $canonicalSymbol) {
        return redirect()->route(
            'crypto.show',
            ['symbol' => $canonicalSymbol],
            301
        );
    }

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

       $coinNews = Cache::remember(
    'coin_news_' . strtolower($crypto->symbol),
    1800,
    function () use ($crypto) {

        $name = trim((string) $crypto->name);
        $symbol = strtoupper(trim((string) $crypto->symbol));

        /*
        |--------------------------------------------------------------------------
        | Build safe search terms
        |--------------------------------------------------------------------------
        | Name:
        |   Used for exact keyword matching and title matching.
        |
        | Symbol:
        |   Used ONLY as an exact keyword.
        |   Never use a short symbol in LIKE queries.
        |
        | Aliases:
        |   Used as exact keywords.
        |   Title LIKE is allowed only for sufficiently long aliases.
        |--------------------------------------------------------------------------
        */

        $aliases = $crypto->aliases
            ->pluck('alias')
            ->map(fn ($alias) => trim((string) $alias))
            ->filter(fn ($alias) => $alias !== '')
            ->unique()
            ->values();

        return News::query()
            ->where('ai_processed', true)
            ->where(function ($query) use ($name, $symbol, $aliases) {

                /*
                |--------------------------------------------------------------------------
                | Exact keyword matching
                |--------------------------------------------------------------------------
                */

                $query->whereJsonContains('keywords', $name)
                    ->orWhereJsonContains('keywords', $symbol);

                foreach ($aliases as $alias) {
                    $query->orWhereJsonContains('keywords', $alias);
                }

                /*
                |--------------------------------------------------------------------------
                | Title matching
                |--------------------------------------------------------------------------
                |
                | The symbol is intentionally NOT used here.
                |
                | Example:
                |   S  -> dangerous
                |   A  -> dangerous
                |   OP -> too short
                |
                | Therefore title matching uses the full coin name
                | and only sufficiently long aliases.
                |--------------------------------------------------------------------------
                */

                if (mb_strlen($name) >= 4) {
                    $query->orWhere(
                        'title_en',
                        'LIKE',
                        '%' . addcslashes($name, '%_') . '%'
                    );
                }

                foreach ($aliases as $alias) {
                    if (mb_strlen($alias) >= 4) {
                        $query->orWhere(
                            'title_en',
                            'LIKE',
                            '%' . addcslashes($alias, '%_') . '%'
                        );
                    }
                }
            })
           ->latest('created_at')
            ->limit(6)
            ->get()
            ->unique('id')
            ->values()
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
        /*
|--------------------------------------------------------------------------
| أكاديمية AQL Crypto
|--------------------------------------------------------------------------
|
| نعرض الموضوعات التعليمية النشطة حتى يعرف زائر صفحة
| العملة بوجود الأكاديمية، بدون ربط كل عملة بدرس خاص بها.
|
*/

$academyTopics = Cache::remember(
    'academy_topics_crypto_widget',
    3600,
    function () {
        return AcademyTopic::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get([
                'id',
                'name_ar',
                'name_en',
                'slug',
                'description_ar',
                'description_en',
            ]);
    }
);

        return Inertia::render('Crypto/Show', [
    'crypto' => $crypto,
    'chartData' => $chartData,
    'coinNews' => $coinNews,
    'aiReport' => $aiReport,
    'academyTopics' => $academyTopics,
]);
    }
}