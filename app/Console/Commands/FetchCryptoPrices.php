<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Cryptocurrency;

class FetchCryptoPrices extends Command
{
    /**
     * اسم الأمر
     */
    protected $signature = 'crypto:fetch-prices';

    /**
     * وصف الأمر
     */
    protected $description = 'جلب أسعار أفضل 250 عملة رقمية حية من CoinGecko وتحديث قاعدة البيانات';

    /**
     * التنفيذ
     */
    public function handle()
    {
        $this->info('جاري الاتصال وجلب أفضل 250 عملة من CoinGecko...');

        try {
            /*
            |--------------------------------------------------------------------------
            | 1. جلب أفضل 250 عملة
            |--------------------------------------------------------------------------
            */

            $response = Http::timeout(15)->get(
                'https://api.coingecko.com/api/v3/coins/markets',
                [
                    'vs_currency' => 'usd',
                    'order'       => 'market_cap_desc',
                    'per_page'    => 250,
                    'page'        => 1,
                    'sparkline'   => 'false',
                ]
            );

            if (!$response->successful()) {
                $this->error(
                    'فشل جلب أسعار العملات من CoinGecko. HTTP: '
                    . $response->status()
                );

                return self::FAILURE;
            }

            $coins = $response->json();

            if (!is_array($coins) || empty($coins)) {
                $this->error('لم يتم استلام بيانات عملات صالحة من CoinGecko.');

                return self::FAILURE;
            }

            /*
            |--------------------------------------------------------------------------
            | 2. تحديث العملات في قاعدة البيانات
            |--------------------------------------------------------------------------
            */

            $updatedCount = 0;

            foreach ($coins as $coin) {
                if (empty($coin['id'])) {
                    continue;
                }

                Cryptocurrency::updateOrCreate(
                    [
                        'coingecko_id' => $coin['id'],
                    ],
                    [
                        'name'          => $coin['name'] ?? null,
                        'symbol'        => strtoupper($coin['symbol'] ?? ''),
                        'image_url'     => $coin['image'] ?? null,
                        'current_price' => $coin['current_price'] ?? 0,
                        'change_24h'    => $coin['price_change_percentage_24h'] ?? 0,
                        'volume_24h'    => $coin['total_volume'] ?? 0,
                        'market_cap'    => $coin['market_cap'] ?? 0,
                    ]
                );

                $updatedCount++;
            }

            $this->info(
                "تم تحديث {$updatedCount} عملة في قاعدة البيانات."
            );

            /*
            |--------------------------------------------------------------------------
            | 3. إحصائيات السوق العالمية
            |--------------------------------------------------------------------------
            */

            $globalRes = Http::timeout(10)->get(
                'https://api.coingecko.com/api/v3/global'
            );

            if ($globalRes->successful()) {
                $globalData = $globalRes->json('data');

                Cache::put(
                    'market_global_stats',
                    [
                        'market_cap' => $globalData['total_market_cap']['usd'] ?? 0,
                        'volume' => $globalData['total_volume']['usd'] ?? 0,
                        'btc_dominance' => $globalData['market_cap_percentage']['btc'] ?? 0,
                        'active_coins' => $globalData['active_cryptocurrencies'] ?? 0,
                        'market_cap_change' => $globalData['market_cap_change_percentage_24h_usd'] ?? 0,
                    ],
                    now()->addHours(1)
                );

                $this->info('تم تحديث إحصائيات السوق العالمية.');
            } else {
                $this->warn(
                    'تعذر تحديث إحصائيات السوق العالمية، لكن تحديث العملات نجح.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. مؤشر الخوف والطمع
            |--------------------------------------------------------------------------
            */

            $fngRes = Http::timeout(10)->get(
                'https://api.alternative.me/fng/'
            );

            if ($fngRes->successful()) {
                $fngData = $fngRes->json('data')[0] ?? null;

                if ($fngData) {
                    Cache::put(
                        'fear_greed_index',
                        [
                            'value' => (int) ($fngData['value'] ?? 0),
                            'classification' => $fngData['value_classification'] ?? '',
                        ],
                        now()->addHours(2)
                    );

                    $this->info('تم تحديث مؤشر الخوف والطمع.');
                }
            } else {
                $this->warn(
                    'تعذر تحديث مؤشر الخوف والطمع، لكن تحديث العملات نجح.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. النتيجة النهائية
            |--------------------------------------------------------------------------
            */

            $this->newLine();

            $this->info(
                'تم تحديث كافة البيانات بنجاح:'
            );

            $this->line(
                "✓ العملات: {$updatedCount}"
            );

            $this->line(
                '✓ إحصائيات السوق العالمية'
            );

            $this->line(
                '✓ مؤشر الخوف والطمع'
            );

            $this->newLine();

            return self::SUCCESS;

        } catch (\Throwable $e) {

            $this->error(
                'حدث خطأ أثناء تحديث بيانات العملات: '
                . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}