<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CryptoController;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

// 1. مسار الصفحة الرئيسية العامة للموقع (مربوط بـ HomeController)
Route::get('/', [HomeController::class, 'index'])->name('home');

// 2. مسار صفحة أسعار العملات الكاملة (مربوط بـ CryptoController)
Route::get('/prices', [CryptoController::class, 'index'])->name('crypto.prices');

// 3. مسار صفحة تحليل وتفاصيل عملة منفردة (مثل /crypto/btc)
Route::get('/crypto/{symbol}', [CryptoController::class, 'show'])->name('crypto.show');

// 4. مسارات قسم الأخبار وتفاصيل الخبر المنفرد (مربوطة بـ NewsController)
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{id}', [NewsController::class, 'show'])->name('news.show');

// 5. المحرك العالمي للتبديل الفوري بين اللغتين (العربية والإنجليزية)
Route::get('/lang/{lang}', function ($lang) {
    if (in_array($lang, ['ar', 'en'])) {
        Session::put('locale', $lang);
    }
    return redirect()->back();
})->name('lang.switch');