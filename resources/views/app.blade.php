<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CryptoHub</title>

    <!-- حقن ملفات Vite بشكل منفصل وصريح لضمان تحميل التنسيقات والـ JS بشكل صحيح -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="bg-slate-900 text-slate-100 antialiased">
    @inertia
</body>
</html>