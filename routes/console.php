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

// 🟢 عامل النظافة: يعمل يومياً عند منتصف الليل لحذف الأخبار القديمة
Schedule::command('model:prune')->daily();