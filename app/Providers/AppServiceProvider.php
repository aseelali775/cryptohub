<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1. إلزام المنصة برابط الصب دومين
        URL::forceRootUrl(config('app.url'));

        // 2. ⚡ الحل القاطع لحظر المتصفح: إجبار البروتوكول الآمن لجميع الروابط والـ Assets
        if (app()->environment('production') || str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }
    }
}