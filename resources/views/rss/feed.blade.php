<?= '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>AQL Crypto - أحدث الأخبار</title>
        <link>{{ url('/') }}</link>
        <description>منصتك الذكية والموثوقة لمتابعة أحدث أخبار وتحليلات العملات الرقمية</description>
        <language>ar</language>
        <atom:link href="{{ url('/feed') }}" rel="self" type="application/rss+xml" />
        
        @foreach($news as $item)
            @php
                // 1. تنظيف الـ slug بنفس الطريقة الموجودة في NewsController@show
                $cleanSlug = $item->slug ?? '';
                if ($cleanSlug && preg_match('/-' . preg_quote($item->id, '/') . '$/', $cleanSlug)) {
                    $cleanSlug = preg_replace('/-' . preg_quote($item->id, '/') . '$/', '', $cleanSlug);
                }
                
                // 2. توليد الرابط الرسمي المتطابق 100% عبر Laravel Route
                $link = route('news.show', ['id' => $item->id, 'slug' => $cleanSlug]);

                // 3. جلب المحتوى العربي أو الإنجليزي كبديل
                $title = $item->title_ar ?: $item->title_en;
                $description = $item->summary_ar ?: mb_substr(strip_tags($item->content_ar ?: $item->content_en ?? ''), 0, 200) . '...';
            @endphp
            
            <item>
                <title><![CDATA[{{ $title }}]]></title>
                <link>{{ $link }}</link>
                <guid isPermaLink="true">{{ $link }}</guid>
                <description><![CDATA[{{ $description }}]]></description>
                <pubDate>{{ $item->created_at->toRfc2822String() }}</pubDate>
            </item>
        @endforeach
    </channel>
</rss>