<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- =========================================================
         SEO Server-Side
         ========================================================= --}}

    <title>
        {{ $seo['title'] ?? 'Aql Crypto' }}
    </title>

    @if(isset($seo['description']) && $seo['description'])
        <meta
            name="description"
            content="{{ $seo['description'] }}"
        >
    @endif

    @if(isset($seo['canonical']) && $seo['canonical'])
        <link
            rel="canonical"
            href="{{ $seo['canonical'] }}"
        >
    @endif

    {{-- Open Graph --}}
    @if(isset($seo['title']) && $seo['title'])
        <meta
            property="og:title"
            content="{{ $seo['title'] }}"
        >
    @endif

    @if(isset($seo['description']) && $seo['description'])
        <meta
            property="og:description"
            content="{{ $seo['description'] }}"
        >
    @endif

    @if(isset($seo['canonical']) && $seo['canonical'])
        <meta
            property="og:url"
            content="{{ $seo['canonical'] }}"
        >
    @endif

    @if(isset($seo['image']) && $seo['image'])
        <meta
            property="og:image"
            content="{{ $seo['image'] }}"
        >
    @endif

    <meta
        property="og:type"
        content="article"
    >

    <meta
        property="og:site_name"
        content="Aql Crypto"
    >

    {{-- Twitter Card --}}
    <meta
        name="twitter:card"
        content="summary_large_image"
    >

    @if(isset($seo['title']) && $seo['title'])
        <meta
            name="twitter:title"
            content="{{ $seo['title'] }}"
        >
    @endif

    @if(isset($seo['description']) && $seo['description'])
        <meta
            name="twitter:description"
            content="{{ $seo['description'] }}"
        >
    @endif

    @if(isset($seo['image']) && $seo['image'])
        <meta
            name="twitter:image"
            content="{{ $seo['image'] }}"
        >
    @endif

    @if(isset($seo['canonical']) && $seo['canonical'])
        <meta
            name="twitter:url"
            content="{{ $seo['canonical'] }}"
        >
    @endif

    {{-- =========================================================
         NewsArticle Structured Data
         ========================================================= --}}

    @if(isset($seo['schema']) && $seo['schema'])
        <script type="application/ld+json">
            {!! json_encode(
                $seo['schema'],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_PRETTY_PRINT
            ) !!}
        </script>
    @endif

    {{-- =========================================================
         أيقونات الموقع لمحركات البحث والمتصفحات
         ========================================================= --}}

    <link
        rel="icon"
        type="image/webp"
        href="/favicon.webp"
    >

    <link
        rel="icon"
        type="image/x-icon"
        href="/favicon.ico"
    >

    <link
        rel="apple-touch-icon"
        href="/favicon.webp"
    >

    {{-- =========================================================
         Google Analytics
         ========================================================= --}}

    <script
        async
        src="https://www.googletagmanager.com/gtag/js?id=G-WKHYN6DQJT"
    ></script>

    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        // تسجيل الزيارة الأولى عند فتح الموقع
        gtag('config', 'G-WKHYN6DQJT');
    </script>

    {{-- =========================================================
         Vite
         ========================================================= --}}

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    {{-- =========================================================
         Inertia Head
         ========================================================= --}}

    @inertiaHead

</head>

<body class="bg-slate-900 text-slate-100 antialiased">

    @inertia

</body>
</html>