<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class NewsController extends Controller
{
    private function mapNewsItem($item)
    {
        return [
            'id'           => $item->id,
            'slug'         => $item->slug,
            'keywords'     => $item->keywords ?? [],
            'image_url'    => $item->image_url,
            'source'       => $item->source,
            'url'          => $item->url,
            'sentiment'    => $item->sentiment ?? 'Neutral',
            'category'     => $item->category ?? 'General',
            'impact_score' => $item->impact_score ?? 5,
            'ai_processed' => (bool) $item->ai_processed,

            'date' => $item->created_at
                ? $item->created_at->diffForHumans()
                : '',

            'published_at' => $item->created_at
                ? $item->created_at->toIso8601String()
                : null,

            'updated_at' => $item->updated_at
                ? $item->updated_at->toIso8601String()
                : null,

            'author' => [
                'name' => 'Aql Crypto Editorial Team',
                'url'  => 'https://aqlcrypto.com/about',
            ],

            'publisher' => [
                'name' => 'Aql Crypto',
                'logo' => 'https://aqlcrypto.com/images/default-og.jpg',
            ],

            'translations' => [
                'ar' => [
                    'title' => $item->title_ar ?? $item->title_en,

                    'content' => $item->content_ar ?? $item->content_en,

                    'summary' => $item->summary_ar
                        ?? mb_substr($item->content_en ?? '', 0, 150) . '...',

                    'why_it_matters' => $item->why_it_matters_ar,
                    'analysis'       => $item->analysis_ar,
                    'context'        => $item->context_ar,
                    'what_to_watch'  => $item->what_to_watch_ar,
                    'limitations'    => $item->limitations_ar,
                ],

                'en' => [
                    'title'   => $item->title_en,
                    'content' => $item->content_en,
                ],
            ],
        ];
    }

    /**
     * عرض قائمة الأخبار
     *
     * - البحث
     * - التصنيف
     * - المشاعر
     * - التاريخ
     * - Pagination
     * - HTTPS
     */
    public function index()
    {
        /*
         * مهم جداً:
         * إجبار Laravel على إنشاء روابط Pagination باستخدام HTTPS.
         */
        URL::forceScheme('https');

        $query = News::query()
            ->where('ai_processed', true);

        /*
         * ============================
         * 1. البحث
         * ============================
         */
        if (request()->filled('search')) {

            $searchTerm = trim(request('search'));

            $query->where(function ($q) use ($searchTerm) {

                $q->where('title_ar', 'like', "%{$searchTerm}%")
                    ->orWhere('title_en', 'like', "%{$searchTerm}%")
                    ->orWhere('content_ar', 'like', "%{$searchTerm}%")
                    ->orWhere('content_en', 'like', "%{$searchTerm}%")
                    ->orWhere('summary_ar', 'like', "%{$searchTerm}%");
            });
        }

        /*
         * ============================
         * 2. التصنيف
         * ============================
         */
        if (request()->filled('category')) {

            $query->where(
                'category',
                request('category')
            );
        }

        /*
         * ============================
         * 3. المشاعر
         * ============================
         */
        if (request()->filled('sentiment')) {

            $query->where(
                'sentiment',
                request('sentiment')
            );
        }

        /*
         * ============================
         * 4. التاريخ
         * ============================
         *
         * التاريخ القادم من input[type=date]
         * يكون بالشكل:
         *
         * YYYY-MM-DD
         */
        if (request()->filled('date')) {

            $date = request('date');

            /*
             * نتأكد أن التاريخ صالح قبل استخدامه.
             */
            if (
                preg_match(
                    '/^\d{4}-\d{2}-\d{2}$/',
                    $date
                )
            ) {
                $query->whereDate(
                    'created_at',
                    $date
                );
            }
        }

        /*
         * ============================
         * 5. Pagination
         * ============================
         *
         * 12 خبر فقط في كل صفحة.
         *
         * withQueryString()
         * يحافظ على الفلاتر عند الانتقال:
         *
         * ?search=bitcoin&page=2
         *
         * وليس:
         *
         * ?page=2
         */
        $newsFeed = $query
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(
                fn ($item) => $this->mapNewsItem($item)
            );

        /*
         * ============================
         * 6. إرسال البيانات إلى Vue
         * ============================
         */
        return Inertia::render('News/Index', [

            'newsFeed' => $newsFeed,

            'filters' => [
                'search'    => request('search', ''),
                'category'  => request('category', ''),
                'sentiment' => request('sentiment', ''),
                'date'      => request('date', ''),
            ],
        ]);
    }


  
    /**
     * عرض خبر واحد.
     *
     * الأخبار غير المعالجة لا تكون متاحة للعامة.
     */
    public function show($id)
    {
        // استخراج ID من الرابط
        $numericId = intval($id);

        /*
         * نبحث عن الخبر بشرط أن يكون معالجاً بالذكاء الاصطناعي.
         *
         * إذا كان الخبر موجوداً ولكنه غير معالج:
         * لن يظهر للعامة وسيتم إرجاع 404.
         */
        $item = News::query()
            ->where('id', $numericId)
            ->where('ai_processed', true)
            ->firstOrFail();

        // تنظيف الـ slug في حال كان محفوظاً مع ID
        $cleanSlug = $item->slug ?? '';

        if (
            $cleanSlug &&
            preg_match('/-' . $item->id . '$/', $cleanSlug)
        ) {
            $cleanSlug = preg_replace(
                '/-' . $item->id . '$/',
                '',
                $cleanSlug
            );
        }

        // بناء الرابط الصحيح
        $expectedPath = 'news/' . $item->id;

        if ($cleanSlug) {
            $expectedPath .= '-' . $cleanSlug;
        }

        /*
         * حماية الرابط وعمل Redirect 301
         * إذا كان الرابط الحالي غير مطابق للرابط الصحيح.
         */
        if (request()->path() !== $expectedPath) {
            return redirect('/' . $expectedPath, 301);
        }

        /*
         * الأخبار ذات الصلة.
         *
         * مهم جداً:
         * لا نسمح بظهور الأخبار غير المعالجة هنا أيضاً.
         */
        $relatedNews = News::query()
            ->where('id', '!=', $item->id)
            ->where('ai_processed', true)
            ->when($item->category, function ($query) use ($item) {
                return $query->where(
                    'category',
                    $item->category
                );
            })
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($n) {
                return $this->mapNewsItem($n);
            });

        /*
         * إذا كان عدد الأخبار ذات الصلة أقل من 3،
         * نعوض النقص من أحدث الأخبار المعالجة فقط.
         */
        if ($relatedNews->count() < 3) {
            $moreNews = News::query()
                ->where('id', '!=', $item->id)
                ->where('ai_processed', true)
                ->whereNotIn(
                    'id',
                    $relatedNews->pluck('id')->toArray()
                )
                ->latest()
                ->take(3 - $relatedNews->count())
                ->get()
                ->map(function ($n) {
                    return $this->mapNewsItem($n);
                });

            $relatedNews = $relatedNews->merge($moreNews);
        }

        /*
         * عرض الخبر النهائي.
         */
        return Inertia::render('News/Show', [
            'newsItem' => $this->mapNewsItem($item),
            'relatedNews' => $relatedNews,
        ]);
    }

}