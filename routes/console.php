<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// سحب الأسعار كل 10 دقائق (محمي من التداخل)
Schedule::command('crypto:fetch-prices')->everyFiveHours()->withoutOverlapping();

// سحب الأخبار من المصادر كل 30 دقيقة (محمي من التداخل)
Schedule::command('crypto:fetch-news')->everySixHours()->withoutOverlapping();

// معالجة الأخبار بالذكاء الاصطناعي كل 10 دقائق (ضروري جداً حمايته من التداخل)
Schedule::command('news:process-ai')->everySixHours()->withoutOverlapping();

// عامل النظافة اليومي
Schedule::command('model:prune')->daily();