<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Cryptocurrency;
use App\Services\NewsFormatterService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AiMarketController extends Controller
{
    public function index()
    {
        // 1. تجميع الإحصائيات الذكية
        $aiStats = Cache::remember('ai_market_dashboard_stats', 3600, function () {
            
            $processedNews = News::where('ai_processed', true)
                ->select(['id', 'sentiment', 'keywords', 'updated_at'])
                ->get();

            $total = $processedNews->count() ?: 1; 
            
            $bullishCount = $processedNews->where('sentiment', 'Bullish')->count();
            $bearishCount = $processedNews->where('sentiment', 'Bearish')->count();
            $neutralCount = $processedNews->where('sentiment', 'Neutral')->count();

            $sentiment = [
                'bullish' => round(($bullishCount / $total) * 100),
                'bearish' => round(($bearishCount / $total) * 100),
                'neutral' => round(($neutralCount / $total) * 100),
            ];

            // 🟢 1️⃣ خلاصة حالة السوق العامة (Market Mood)
            $marketMood = 'Neutral';
            if ($sentiment['bullish'] >= 55) {
                $marketMood = 'Bullish';
            } elseif ($sentiment['bearish'] >= 55) {
                $marketMood = 'Bearish';
            }

            // 🟢 2️⃣ آخر تحديث للبيانات
            $lastUpdatedRaw = News::where('ai_processed', true)->latest('updated_at')->value('updated_at');
            $lastUpdated = $lastUpdatedRaw ? $lastUpdatedRaw->diffForHumans() : '';

            $ignoredKeywords = ['CRYPTO', 'MARKET', 'BLOCKCHAIN', 'TOKEN', 'COIN', 'ETF', 'WEB3', 'DEFI', 'NEWS', 'SEC', 'FED'];
            
            $coins = Cryptocurrency::with('aliases')->select('id', 'name', 'symbol')->get();
            $validSymbols = $coins->pluck('symbol')->toArray();
            
            $keywordToSymbol = [];
            foreach ($coins as $coin) {
                $keywordToSymbol[strtoupper($coin->name)] = $coin->symbol;
                $keywordToSymbol[strtoupper($coin->symbol)] = $coin->symbol;
                foreach ($coin->aliases as $alias) {
                    $keywordToSymbol[strtoupper($alias->alias)] = $coin->symbol;
                }
            }

            $trendingCounts = [];
            foreach ($processedNews as $item) {
                if (is_array($item->keywords)) {
                    foreach ($item->keywords as $kw) {
                        $kwUpper = strtoupper(trim($kw));
                        if (in_array($kwUpper, $ignoredKeywords) || strlen($kwUpper) <= 2) continue;

                        $resolvedKeyword = $keywordToSymbol[$kwUpper] ?? $kwUpper;
                        $trendingCounts[$resolvedKeyword] = ($trendingCounts[$resolvedKeyword] ?? 0) + 1;
                    }
                }
            }
            
            arsort($trendingCounts);
            $topTrending = array_slice($trendingCounts, 0, 8, true);

            $formattedTrending = [];
            foreach ($topTrending as $kw => $count) {
                $formattedTrending[] = [
                    'keyword' => $kw,
                    'count'   => $count,
                    'is_coin' => in_array($kw, $validSymbols)
                ];
            }

            // 🟢 3️⃣ تحديد العملة الأكثر ذكراً (Most Mentioned Coin)
            $mostMentionedCoin = null;
            foreach ($formattedTrending as $item) {
                if ($item['is_coin']) {
                    $coinModel = $coins->firstWhere('symbol', $item['keyword']);
                    $mostMentionedCoin = [
                        'symbol' => $item['keyword'],
                        'name'   => $coinModel ? $coinModel->name : $item['keyword'],
                        'count'  => $item['count'],
                    ];
                    break;
                }
            }

            return [
                'sentiment'         => $sentiment,
                'marketMood'        => $marketMood,
                'lastUpdated'       => $lastUpdated,
                'trending'          => $formattedTrending,
                'mostMentionedCoin' => $mostMentionedCoin,
                'total_analyzed'    => $processedNews->count()
            ];
        });

        // 🟢 4️⃣ & 5️⃣ الأخبار الأكثر تأثيراً مرتبة بحسب التأثير ثم الأحدث + ربط رمز العملة
        $impactfulNews = Cache::remember('ai_market_impact_news', 3600, function () {
            $symbols = Cryptocurrency::pluck('symbol')->toArray();

            return News::where('ai_processed', true)
                ->whereNotNull('impact_score')
                ->where('impact_score', '>=', 7)
                ->orderByDesc('impact_score')
                ->latest()
                ->take(6)
                ->get()
                ->map(function ($item) use ($symbols) {
                    $formatted = NewsFormatterService::format($item);
                    
                    // استخراج رمز العملة المعنية من الكلمات المفتاحية
                    $relatedSymbol = null;
                    if (is_array($item->keywords)) {
                        foreach ($item->keywords as $kw) {
                            $kwUpper = strtoupper(trim($kw));
                            if (in_array($kwUpper, $symbols)) {
                                $relatedSymbol = $kwUpper;
                                break;
                            }
                        }
                    }
                    $formatted['related_symbol'] = $relatedSymbol ?: ($item->category ?? 'Crypto');
                    return $formatted;
                });
        });

        return Inertia::render('AiMarket/Index', [
            'aiStats'       => $aiStats,
            'impactfulNews' => $impactfulNews
        ]);
    }
}