<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 🟢 تم التصحيح: اسم الأمر الآن يتطابق تماماً مع الملف
// 🟢 تم التعديل: كل 10 دقائق لتجنب الحظر من مزودي واجهات الـ API
Schedule::command('crypto:fetch-prices')->everyTenMinutes();

// 🟢 أمر سحب الأخبار يعمل كل 30 دقيقة
Schedule::command('crypto:fetch-news')->everyThirtyMinutes();

/// معالجة الأخبار بالذكاء الاصطناعي كل 10 دقائق 
// (لكي يعالج أي خبر جديد يتم سحبه بسرعة وبدون أن يتراكم)
Schedule::command('news:process-ai')->everyTenMinutes();

// عامل النظافة اليومي
Schedule::command('model:prune')->daily();