<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CryptoController;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\AiMarketController;
use App\Http\Controllers\LegalPagesController;


// 1. مسار الصفحة الرئيسية العامة للموقع (مربوط بـ HomeController)
Route::get('/', [HomeController::class, 'index'])->name('home');

// 2. مسار صفحة أسعار العملات الكاملة (مربوط بـ CryptoController)
Route::get('/prices', [CryptoController::class, 'index'])->name('crypto.prices');

// 3. مسار صفحة تحليل وتفاصيل عملة منفردة (مثل /crypto/btc)
Route::get('/crypto/{symbol}', [CryptoController::class, 'show'])->name('crypto.show');

// 4. مسارات قسم الأخبار وتفاصيل الخبر المنفرد (مربوطة بـ NewsController)
Route::get('/news', [NewsController::class, 'index'])->name('news.index');

Route::get('/news/{id}-{slug?}', [NewsController::class, 'show'])->name('news.show');
// ... (باقي الروابط)
Route::get('/ai-market', [AiMarketController::class, 'index'])->name('ai-market');


// ==========================================
// AQL Crypto Legal & Company Pages
// ==========================================
Route::get('/about', [LegalPagesController::class, 'about'])->name('about');

Route::get('/contact', [LegalPagesController::class, 'contact'])->name('contact');
Route::post('/contact', [LegalPagesController::class, 'submitContact'])->name('contact.submit');

Route::get('/privacy-policy', [LegalPagesController::class, 'privacyPolicy'])->name('privacy.policy');
Route::get('/terms-of-use', [LegalPagesController::class, 'termsOfUse'])->name('terms.use');
Route::get('/disclaimer', [LegalPagesController::class, 'disclaimer'])->name('disclaimer');
Route::get('/editorial-policy', [LegalPagesController::class, 'editorialPolicy'])->name('editorial.policy');



// 5. المحرك العالمي للتبديل الفوري بين اللغتين (العربية والإنجليزية)
Route::get('/lang/{lang}', function ($lang) {
    if (in_array($lang, ['ar', 'en'])) {
        Session::put('locale', $lang);
    }
    return redirect()->back();
})->name('lang.switch');