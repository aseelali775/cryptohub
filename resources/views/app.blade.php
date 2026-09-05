<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aql Crypto</title>

    <!-- Google tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-WKHYN6DQJT"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      // تسجيل الزيارة الأولى عند فتح الموقع
      gtag('config', 'G-WKHYN6DQJT');
    </script>

    <!-- حقن ملفات Vite بشكل منفصل وصريح لضمان تحميل التنسيقات والـ JS بشكل صحيح -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="bg-slate-900 text-slate-100 antialiased">
    @inertia
</body>
</html>