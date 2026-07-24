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
        $this->info('جاري الاتصال بـ CoinGecko API وجلب الأسعار الحية...');

        try {
            // جلب أفضل 20 عملة مرتبة حسب القيمة السوقية (بدون تحديد أسماء معينة لتكون ديناميكية)
            $response = Http::timeout(15)->get('https://api.coingecko.com/api/v3/coins/markets', [
                'vs_currency' => 'usd',
                'order'       => 'market_cap_desc',
                'per_page'    => 20,
                'page'        => 1,
                'sparkline'   => 'false'
            ]);

            if ($response->successful()) {
                $coins = $response->json();

                foreach ($coins as $coin) {
                    // الاعتماد على coingecko_id لتجنب أي تضارب في أسماء الرموز
                    Cryptocurrency::updateOrCreate(
                        ['coingecko_id' => $coin['id']], 
                        [
                            'name'          => $coin['name'],
                            'symbol'        => strtoupper($coin['symbol']), // أحرف كبيرة للتصميم
                            'image_url'     => $coin['image'] ?? null,
                            'current_price' => $coin['current_price'],
                            'change_24h'    => $coin['price_change_percentage_24h'] ?? 0,
                            'volume_24h'    => $coin['total_volume'] ?? 0,
                            'market_cap'    => $coin['market_cap'] ?? 0,
                        ]
                    );
                }

                $this->info('تم تحديث أسعار ' . count($coins) . ' عملة رقمية في قاعدة البيانات بنجاح! 🎉');
            } else {
                $this->error('فشل الاتصال بالـ API. رمز الخطأ: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('حدث خطأ أثناء الجلب: ' . $e->getMessage());
        }
    }
}