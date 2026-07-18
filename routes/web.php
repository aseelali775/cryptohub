<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

// 1. مسار الصفحة الرئيسية العامة للموقع
Route::get('/', [DashboardController::class, 'home'])->name('home');

// 2. مسار لوحة التحكم / الداشبورد (يعرض 3 عملات فقط)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// 3. مسار صفحة أسعار العملات الكاملة والموسعة (يعرض 20 عملة حية)
Route::get('/prices', [DashboardController::class, 'prices'])->name('crypto.prices');

// 🟢 4. مسار التحديث الفوري المفقود (تم إضافته هنا كـ POST)
Route::post('/refresh-prices', [DashboardController::class, 'refreshPrices'])->name('crypto.refresh');

// 5. مسار صفحة الأخبار المشفرة والتلخيص الفوري
Route::get('/news', [NewsController::class, 'index'])->name('news.index');

Route::get('/crypto/{symbol}', [DashboardController::class, 'show'])->name('crypto.show');

// 6. المحرك والمحور العالمي للتبديل الفوري والديناميكي بين اللغتين
Route::get('/lang/{lang}', function ($lang) {
    if (in_array($lang, ['ar', 'en'])) {
        Session::put('locale', $lang);
    }
    return redirect()->back();
})->name('lang.switch');

Route::get('/debug-db', function () {
    return [
        'env_DB_HOST' => env('DB_HOST'),
        'config_DB_HOST' => config('database.connections.mysql.host'),
        'database' => config('database.connections.mysql.database'),
    ];
});