<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Cryptocurrency;

class FetchCryptoPrices extends Command
{
    /**
     * اسم الأمر الذي سنكتبه في الترمينال لتشغيله (تم توحيده ليكون متناسقاً مع الأخبار)
     */
    protected $signature = 'crypto:fetch-prices';

    /**
     * وصف الأمر
     */
    protected $description = 'جلب أسعار أفضل 20 عملة رقمية حية من CoinGecko وتحديث قاعدة البيانات';

    /**
     * التنفيذ الفعلي للأمر
     */
   public function handle()
    {
        $this->info('جاري الاتصال وجلب البيانات الحية...');

        try {
            // 1. جلب أسعار العملات وتخزينها في قاعدة البيانات (الكود القديم الخاص بك)
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get('https://api.coingecko.com/api/v3/coins/markets', [
                'vs_currency' => 'usd', 'order' => 'market_cap_desc', 'per_page' => 20, 'page' => 1, 'sparkline' => 'false'
            ]);

            if ($response->successful()) {
                foreach ($response->json() as $coin) {
                    \App\Models\Cryptocurrency::updateOrCreate(
                        ['coingecko_id' => $coin['id']], 
                        [
                            'name' => $coin['name'], 'symbol' => strtoupper($coin['symbol']), 'image_url' => $coin['image'] ?? null,
                            'current_price' => $coin['current_price'], 'change_24h' => $coin['price_change_percentage_24h'] ?? 0,
                            'volume_24h' => $coin['total_volume'] ?? 0, 'market_cap' => $coin['market_cap'] ?? 0,
                        ]
                    );
                }
            }

            // 🟢 2. جلب إحصائيات السوق العالمية وتخزينها في الكاش (Cache) لمدة ساعة
            $globalRes = \Illuminate\Support\Facades\Http::timeout(10)->get('https://api.coingecko.com/api/v3/global');
            if ($globalRes->successful()) {
                $globalData = $globalRes->json('data');
                \Illuminate\Support\Facades\Cache::put('market_global_stats', [
                    'market_cap' => $globalData['total_market_cap']['usd'] ?? 0,
                    'volume' => $globalData['total_volume']['usd'] ?? 0,
                    'btc_dominance' => $globalData['market_cap_percentage']['btc'] ?? 0,
                    'active_coins' => $globalData['active_cryptocurrencies'] ?? 0,
                    'market_cap_change' => $globalData['market_cap_change_percentage_24h_usd'] ?? 0,
                ], now()->addHours(1));
            }

            // 🟢 3. جلب مؤشر الخوف والطمع الحقيقي وتخزينه في الكاش
            $fngRes = \Illuminate\Support\Facades\Http::timeout(10)->get('https://api.alternative.me/fng/');
            if ($fngRes->successful()) {
                $fngData = $fngRes->json('data')[0];
                \Illuminate\Support\Facades\Cache::put('fear_greed_index', [
                    'value' => (int) $fngData['value'],
                    'classification' => $fngData['value_classification'] // مثل: Extreme Greed
                ], now()->addHours(2));
            }

            $this->info('تم تحديث كافة البيانات (العملات، المؤشر، السوق) بنجاح! 🎉');
        } catch (\Exception $e) {
            $this->error('حدث خطأ: ' . $e->getMessage());
        }
    }
}