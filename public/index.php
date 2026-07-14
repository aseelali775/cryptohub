<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. التحقق من وضع الصيانة (الخروج خطوة للخلف)
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// 2. استدعاء الـ Autoloader القياسي (الخروج خطوة للخلف)
require __DIR__.'/../vendor/autoload.php';

// 3. استدعاء ملف الإقلاع الـ Bootstrap (الخروج خطوة للخلف)
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// 4. معالجة الطلب وتوليد الاستجابة
$app->handleRequest(Request::capture());