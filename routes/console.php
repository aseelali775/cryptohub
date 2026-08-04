<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// سحب الأسعار كل ساعة (يقلل استهلاك الـ CPU بنسبة 85%)
Schedule::command('crypto:fetch-prices')->hourly()->withoutOverlapping();

// سحب الأخبار كل 3 ساعات (يقلل الضغط على قاعدة البيانات والـ API)
Schedule::command('crypto:fetch-news')->everyThreeHours()->withoutOverlapping();

// معالجة الأخبار بالذكاء الاصطناعي كل ساعتين 
Schedule::command('news:process-ai')->everyTwoHours()->withoutOverlapping();

// عامل النظافة اليومي
Schedule::command('model:prune')->daily();