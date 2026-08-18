<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;

class NewsController extends Controller
{
    /**
     * تجهيز بيانات الخبر لإرسالها إلى Vue.
     */
    private function mapNewsItem($item): array
    {
        return [
            'id'            => $item->id,
            'slug'          => $item->slug,
            'keywords'      => $item->keywords ?? [],
            'image_url'     => $item->image_url,
            'source'        => $item->source,
            'url'           => $item->url,
            'sentiment'     => $item->sentiment ?? 'Neutral',
            'category'      => $item->category ?? 'General',
            'impact_score'  => $item->impact_score ?? 5,
            'ai_processed'  => (bool) $item->ai_processed,

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
                    'title' => $item->title_ar
                        ?: $item->title_en,

                    'content' => $item->content_ar
                        ?: $item->content_en,

                    'summary' => $item->summary_ar
                        ?: mb_substr(
                            $item->content_en ?? '',
                            0,
                            150
                        ) . '...',

                    'why_it_matters' => $item->why_it_matters_ar,

                    'analysis' => $item->analysis_ar,

                    'context' => $item->context_ar,

                    'what_to_watch' => $item->what_to_watch_ar,

                    'limitations' => $item->limitations_ar,
                ],

                'en' => [
                    'title'   => $item->title_en,
                    'content' => $item->content_en,
                ],
            ],
        ];
    }

    /**
     * قائمة الأخبار.
     *
     * يدعم:
     * - البحث
     * - التصنيف
     * - المشاعر
     * - التاريخ
     * - Pagination
     *
     * الأخبار غير المعالجة بالـ AI لا تظهر للعامة.
     */
    public function index()
    {
        /*
         * نبدأ بالأخبار المعالجة فقط.
         */
        $query = News::query()
            ->where('ai_processed', true);

        /*
         * -----------------------------------------
         * 1. البحث
         * -----------------------------------------
         */
        $search = request('search');

        if ($search !== null && trim($search) !== '') {
            $search = trim($search);

            $query->where(function ($q) use ($search) {
                $q->where('title_ar', 'like', "%{$search}%")
                    ->orWhere('title_en', 'like', "%{$search}%")
                    ->orWhere('content_ar', 'like', "%{$search}%")
                    ->orWhere('content_en', 'like', "%{$search}%")
                    ->orWhere('summary_ar', 'like', "%{$search}%")
                    ->orWhere('analysis_ar', 'like', "%{$search}%");
            });
        }

        /*
         * -----------------------------------------
         * 2. فلتر التصنيف
         * -----------------------------------------
         */
        $category = request('category');

        if ($category !== null && trim($category) !== '') {
            $query->where('category', trim($category));
        }

        /*
         * -----------------------------------------
         * 3. فلتر المشاعر
         * -----------------------------------------
         */
        $sentiment = request('sentiment');

        if ($sentiment !== null && trim($sentiment) !== '') {
            $query->where('sentiment', trim($sentiment));
        }

        /*
         * -----------------------------------------
         * 4. فلتر التاريخ
         * -----------------------------------------
         */
        $date = request('date');

        if ($date !== null && trim($date) !== '') {
            $query->whereDate('created_at', trim($date));
        }

        /*
         * -----------------------------------------
         * 5. الترتيب + Pagination
         * -----------------------------------------
         *
         * paginate(12):
         * يجلب 12 خبرًا فقط في كل صفحة.
         *
         * withQueryString():
         * يحافظ على الفلاتر عند الانتقال:
         *
         * /news?page=2&search=bitcoin
         *
         * بدل أن تصبح:
         *
         * /news?page=2
         */
        $newsFeed = $query
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(function ($item) {
                return $this->mapNewsItem($item);
            });

        /*
         * -----------------------------------------
         * إرسال البيانات إلى Inertia
         * -----------------------------------------
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