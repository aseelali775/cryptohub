<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Cryptocurrency;

class FetchCryptoPrices extends Command
{
    /**
     * اسم الأمر الذي سنكتبه في الترمينال لتشغيله
     */
    protected $signature = 'crypto:fetch';

    /**
     * وصف الأمر
     */
    protected $description = 'جلب أسعار العملات الرقمية الحية من CoinGecko وتحديث قاعدة البيانات';

    /**
     * التنفيذ الفعلي للأمر
     */
    public function handle()
    {
        $this->info('جاري الاتصال بـ CoinGecko API وجلب الأسعار الحية...');

        // الاتصال بالـ API وجلب بيانات البيتكوين والإيثيريوم وسولانا مقابل الدولار
        $response = Http::get('https://api.coingecko.com/api/v3/coins/markets', [
            'vs_currency' => 'usd',
            'ids' => 'bitcoin,ethereum,solana',
            'order' => 'market_cap_desc'
        ]);

        if ($response->successful()) {
            $coins = $response->json();

            foreach ($coins as $coin) {
                // تحديث البيانات إذا كانت العملة موجودة، أو إنشاؤها إن لم تكن موجودة
                Cryptocurrency::updateOrCreate(
                    ['symbol' => strtolower($coin['symbol'])], // شرط التحقق بناءً على الرمز
                    [
                        'name' => $coin['name'],
                        'current_price' => $coin['current_price'],
                        'change_24h' => $coin['price_change_percentage_24h'] ?? 0,
                        'volume_24h' => $coin['total_volume'] ?? 0,
                        'image_url' => $coin['image'] ?? null,
                    ]
                );
            }

            $this->info('تم تحديث أسعار العملات في قاعدة البيانات بنجاح! 🎉');
        } else {
            $this->error('فشل الاتصال بالـ API. تأكد من جودة الإنترنت لديك أو جرب لاحقاً.');
        }
    }
}