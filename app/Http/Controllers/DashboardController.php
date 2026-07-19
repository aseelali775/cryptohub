<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Cryptocurrency;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use App\Models\News; // إضافة موديل الأخبار
class DashboardController extends Controller
{
    // 1. الدالة التي كانت مفقودة وتسببت في الخطأ (تخدم الواجهة الخارجية الجديدة)
  public function home()
    {
        $tickerCryptos = Cryptocurrency::take(8)->get();
        $topGainers = Cryptocurrency::orderBy('change_24h', 'desc')->take(3)->get();
        
        // جلب آخر 4 أخبار (الأول هو الرئيسي، والـ 3 التالية هي الفرعية)
        $latestNews = News::latest()->take(4)->get();
        
        return Inertia::render('Home', [
            'tickerCryptos' => $tickerCryptos,
            'topGainers' => $topGainers,
            'news' => $latestNews // إرسال الأخبار الحقيقية للواجهة
        ]);
    }

    // 2. دالة لوحة التحكم الرئيسية (تعرض 3 عملات فقط في الـ Dashboard)
    public function index()
    {
        // جلب أول 3 عملات فقط من قاعدة البيانات
        $cryptos = Cryptocurrency::take(3)->get();

        return Inertia::render('Welcome', [
            'cryptocurrencies' => $cryptos
        ]);
    }

    // 3. دالة صفحة أسعار العملات الكاملة (تعرض 20 عملة حية ومحدثة)
    public function prices()
    {
        // جلب أفضل 20 عملة مخزنة في قاعدة البيانات المحلية لضمان ثبات القائمة
        $cryptos = Cryptocurrency::take(20)->get();

        // إذا كانت قاعدة البيانات تحتوي على أقل من 15 عملة، نقوم بملئها فوراً من الـ API
        if ($cryptos->count() < 15) {
            try {
                $response = Http::get('https://api.coingecko.com/api/v3/coins/markets', [
                    'vs_currency' => 'usd',
                    'order' => 'market_cap_desc',
                    'per_page' => 20,
                    'page' => 1,
                    'sparkline' => false
                ]);

                if ($response->successful()) {
                    foreach ($response->json() as $coin) {
                        Cryptocurrency::updateOrCreate(
                            ['symbol' => $coin['symbol']],
                            [
                                'name' => $coin['name'],
                                'current_price' => $coin['current_price'],
                                'change_24h' => round($coin['price_change_percentage_24h'], 2),
                                'image_url' => $coin['image'],
                            ]
                        );
                    }
                    // إعادة جلب الـ 20 عملة بعد حفظهم وتحديثهم
                    $cryptos = Cryptocurrency::take(20)->get();
                }
            } catch (\Exception $e) {
                // تجاهل الخطأ والقراءة من الموجود
            }
        }

        return Inertia::render('Crypto/Prices', [
            'cryptos' => $cryptos
        ]);
    }

    // 4. دالة التحديث والاستدعاء الفوري يدويًا
    public function refresh()
    {
        // تشغيل الأمر الذي برمجناه سابقاً لجلب البيانات من CoinGecko
        Artisan::call('crypto:fetch');

        // العودة تلقائياً إلى الصفحة السابقة مع البيانات المحدثة
        return redirect()->back();
    }

    // 5. دالة صفحة تفاصيل وتحليل كل عملة على حدة (الـ Chart)
    public function show($symbol)
    {
        // البحث عن العملة عبر رمزها مع تحويله لحروف صغيرة لضمان التوافق
        $crypto = Cryptocurrency::where('symbol', strtolower($symbol))->firstOrFail();

        return Inertia::render('Crypto/Show', [
            'crypto' => $crypto
        ]);
    }
    
}