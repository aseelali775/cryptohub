<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Illuminate\Support\Collection;

class NewsController extends Controller
{
    /**
     * تحويل خبر واحد إلى البيانات التي يحتاجها Frontend.
     *
     * مهم:
     * هذه الدالة تُرجع Array وليس Model.
     */
    private function mapNewsItem(News $item): array
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
     * صفحة الأخبار.
     *
     * تحتوي على:
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
        $query = News::query()
            ->where('ai_processed', true);

        /*
         * 1. البحث النصي
         */
        if (request()->filled('search')) {

            $searchTerm = trim(request('search'));

            if ($searchTerm !== '') {

                $query->where(function ($q) use ($searchTerm) {

                    $q->where('title_ar', 'like', "%{$searchTerm}%")
                        ->orWhere('title_en', 'like', "%{$searchTerm}%")
                        ->orWhere('content_ar', 'like', "%{$searchTerm}%")
                        ->orWhere('content_en', 'like', "%{$searchTerm}%");

                });
            }
        }

        /*
         * 2. فلتر التصنيف
         */
        if (request()->filled('category')) {

            $query->where(
                'category',
                request('category')
            );
        }

        /*
         * 3. فلتر المشاعر
         */
        if (request()->filled('sentiment')) {

            $query->where(
                'sentiment',
                request('sentiment')
            );
        }

        /*
         * 4. فلتر التاريخ
         *
         * التاريخ يصل من Frontend بالشكل:
         *
         * YYYY-MM-DD
         *
         * مثال:
         * 2026-08-14
         */
        if (request()->filled('date')) {

            $date = request('date');

            /*
             * التحقق من أن التاريخ صالح
             * قبل استخدامه في الاستعلام.
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
         * 5. Pagination
         *
         * يتم جلب 12 خبراً فقط في كل صفحة.
         *
         * withQueryString()
         * يحافظ على الفلاتر عند الانتقال:
         *
         * /news?page=2
         *
         * مع:
         *
         * /news?page=2&category=Bitcoin
         */
        $newsFeed = $query
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(
                fn (News $item) => $this->mapNewsItem($item)
            );

        /*
         * إرسال الصفحة إلى Inertia.
         */
        return Inertia::render('News/Index', [

            'newsFeed' => $newsFeed,

            'filters' => request()->only([
                'search',
                'category',
                'sentiment',
                'date',
            ]),
        ]);
    }

    /**
     * عرض خبر واحد.
     *
     * الأخبار غير المعالجة بالـ AI
     * لا تكون متاحة للعامة.
     */
    public function show($id)
    {
        /*
         * استخراج الرقم فقط من الـ ID.
         */
        $numericId = intval($id);

        /*
         * البحث عن الخبر بشرط:
         *
         * 1. ID صحيح
         * 2. ai_processed = true
         *
         * إذا كان الخبر غير موجود أو غير معالج:
         * 404
         */
        $item = News::query()
            ->where('id', $numericId)
            ->where('ai_processed', true)
            ->firstOrFail();

        /*
         * تنظيف الـ slug.
         *
         * بعض البيانات القديمة قد تحتوي على:
         *
         * article-name-123
         *
         * بينما الرابط الصحيح:
         *
         * article-name
         */
        $cleanSlug = $item->slug ?? '';

        if (
            $cleanSlug &&
            preg_match(
                '/-' . preg_quote($item->id, '/') . '$/',
                $cleanSlug
            )
        ) {

            $cleanSlug = preg_replace(
                '/-' . preg_quote($item->id, '/') . '$/',
                '',
                $cleanSlug
            );
        }

        /*
         * بناء الرابط الصحيح.
         */
        $expectedPath = 'news/' . $item->id;

        if ($cleanSlug) {
            $expectedPath .= '-' . $cleanSlug;
        }

        /*
         * Redirect 301 إذا كان الرابط الحالي
         * مختلفاً عن الرابط الرسمي.
         *
         * هذا مفيد أيضاً للـ SEO.
         */
        if (request()->path() !== $expectedPath) {

            return redirect(
                '/' . $expectedPath,
                301
            );
        }

        /*
         * =========================================================
         * الأخبار ذات الصلة
         * =========================================================
         *
         * نبدأ بأخبار نفس التصنيف.
         */

        $relatedNews = News::query()
            ->where('id', '!=', $item->id)
            ->where('ai_processed', true)

            ->when(
                $item->category,
                function ($query) use ($item) {

                    $query->where(
                        'category',
                        $item->category
                    );
                }
            )

            ->latest('created_at')
            ->take(3)
            ->get();

        /*
         * =========================================================
         * حماية مهمة
         * =========================================================
         *
         * هنا لدينا Eloquent\Collection.
         *
         * بعد mapNewsItem() تصبح العناصر Arrays.
         *
         * لذلك لا نقوم بـ:
         *
         * $relatedNews->map(...)
         *
         * ثم نستخدم merge() على Eloquent\Collection.
         *
         * بدلاً من ذلك نحولها صراحةً إلى
         * Illuminate\Support\Collection.
         */

        $relatedNews = collect(
            $relatedNews->map(
                fn (News $news) => $this->mapNewsItem($news)
            )->all()
        )->values();

        /*
         * =========================================================
         * تعويض الأخبار الناقصة
         * =========================================================
         *
         * إذا كان لدينا:
         *
         * 0 أخبار من نفس التصنيف
         * أو
         * 1 خبر
         * أو
         * 2 أخبار
         *
         * نكمل العدد حتى 3 من أحدث الأخبار.
         */
        if ($relatedNews->count() < 3) {

            /*
             * IDs الأخبار الموجودة بالفعل.
             */
            $existingIds = $relatedNews
                ->pluck('id')
                ->filter()
                ->values()
                ->all();

            /*
             * دائماً نستبعد الخبر الحالي.
             */
            $excludedIds = array_merge(
                [$item->id],
                $existingIds
            );

            /*
             * نحتاج فقط للعدد المتبقي.
             */
            $remaining = 3 - $relatedNews->count();

            /*
             * جلب الأخبار الإضافية.
             */
            $moreNews = News::query()
                ->where('ai_processed', true)
                ->whereNotIn(
                    'id',
                    $excludedIds
                )
                ->latest('created_at')
                ->take($remaining)
                ->get();

            /*
             * تحويل Eloquent Models
             * إلى Arrays أولاً.
             */
            $moreNews = collect(
                $moreNews->map(
                    fn (News $news) => $this->mapNewsItem($news)
                )->all()
            )->values();

            /*
             * الآن كلاهما Support Collection
             * تحتوي Arrays.
             *
             * وبالتالي merge() آمن.
             */
            $relatedNews = $relatedNews
                ->merge($moreNews)
                ->values();
        }

        /*
         * =========================================================
         * حماية إضافية نهائية
         * =========================================================
         *
         * نتأكد أن relatedNews:
         *
         * - Collection عادية
         * - تحتوي Arrays فقط
         * - لا تحتوي الخبر الحالي
         * - لا تحتوي IDs مكررة
         * - الحد الأقصى 3 أخبار
         */

        $relatedNews = collect($relatedNews)
            ->filter(function ($news) use ($item) {

                /*
                 * تجاهل أي عنصر غير Array.
                 */
                if (!is_array($news)) {
                    return false;
                }

                /*
                 * تجاهل الخبر الحالي.
                 */
                if (
                    isset($news['id']) &&
                    (int) $news['id'] === (int) $item->id
                ) {
                    return false;
                }

                return true;
            })
            ->unique('id')
            ->take(3)
            ->values();

        /*
         * =========================================================
         * عرض الخبر
         * =========================================================
         */
        return Inertia::render(
            'News/Show',
            [
                'newsItem' => $this->mapNewsItem($item),

                'relatedNews' => $relatedNews,
            ]
        );
    }
}