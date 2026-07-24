<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::command('crypto:fetch')->everyMinute();

// 🟢 أمر سحب الأخبار يعمل كل 30 دقيقة
Schedule::command('crypto:fetch-news')->everyThirtyMinutes();
