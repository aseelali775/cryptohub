<?php

namespace App\Http\Controllers;

use App\Models\News;
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
            
            // 🔴 التواريخ: صيغة مقروءة للزائر + صيغة ISO دقيقة للـ Schema
            'date'         => $item->created_at ? $item->created_at->diffForHumans() : '',
            'published_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
            'updated_at'   => $item->updated_at ? $item->updated_at->toIso8601String() : null,
            
            // 🔴 الموثوقية: المؤلف والناشر (سيتم استخدامها في NewsArticle Schema)
            'author'       => [
                'name' => 'Aql Crypto Editorial Team',
                'url'  => 'https://aqlcrypto.com/about'
            ],
            'publisher'    => [
                'name' => 'Aql Crypto',
                'logo' => 'https://aqlcrypto.com/images/default-og.jpg'
            ],
            
            // الهيكلة الذكية للترجمة
            'translations' => [
                'ar' => [
                    'title'          => $item->title_ar ?? $item->title_en,
                    'content'        => $item->content_ar ?? $item->content_en,
                    'summary'        => $item->summary_ar ?? mb_substr($item->content_en ?? '', 0, 150) . '...',
                    'why_it_matters' => $item->why_it_matters_ar,
                    
                    // 🔴 الحقول التحليلية الجديدة (مربوطة بقاعدة البيانات)
                    'analysis'       => $item->analysis_ar,
                    'context'        => $item->context_ar,
                    'what_to_watch'  => $item->what_to_watch_ar,
                    'limitations'    => $item->limitations_ar,
                ],
                'en' => [
                    'title'   => $item->title_en,
                    'content' => $item->content_en,
                ]
            ]
        ];
    }

    public function index()
    {
        $newsFeed = News::latest()->get()->map(function($item) {
            return $this->mapNewsItem($item);
        });

        return Inertia::render('News/Index', [
            'newsFeed' => $newsFeed
        ]);
    }

    public function show($id)
    {
        // 1. استخراج الـ ID كرقم فقط (حتى لو كان الرابط يحتوي على 193-slug)
        $numericId = intval($id);
        $item = News::findOrFail($numericId);

        // 2. 🔴 حماية الـ URL وعمل 301 Redirect
        // تنظيف الـ Slug في حال كان محفوظاً مع ID في النهاية
        $cleanSlug = $item->slug ?? '';
        if ($cleanSlug && preg_match('/-' . $item->id . '$/', $cleanSlug)) {
            $cleanSlug = preg_replace('/-' . $item->id . '$/', '', $cleanSlug);
        }
        
        // بناء الرابط المتوقع (الوحيد الصحيح)
        $expectedPath = 'news/' . $item->id . ($cleanSlug ? '-' . $cleanSlug : '');

        // إذا كان الرابط الحالي لا يطابق الرابط الصحيح، قم بالتحويل الدائم 301
        if (request()->path() !== $expectedPath) {
            return redirect('/' . $expectedPath, 301);
        }

        // 3. 🟠 الأخبار ذات الصلة (Internal Linking)
        // نجلب 3 أخبار من نفس التصنيف، وإذا لم يكتمل العدد نكملها من أحدث الأخبار
        $relatedNews = News::where('id', '!=', $item->id)
            ->when($item->category, function($query) use ($item) {
                return $query->where('category', $item->category);
            })
            ->latest()
            ->take(3)
            ->get()
            ->map(function($n) {
                return $this->mapNewsItem($n);
            });

        // إذا كان عدد الأخبار ذات الصلة أقل من 3، نعوض النقص بأحدث الأخبار
        if ($relatedNews->count() < 3) {
            $moreNews = News::where('id', '!=', $item->id)
                ->whereNotIn('id', $relatedNews->pluck('id')->toArray())
                ->latest()
                ->take(3 - $relatedNews->count())
                ->get()
                ->map(function($n) {
                    return $this->mapNewsItem($n);
                });
            $relatedNews = $relatedNews->merge($moreNews);
        }

        // 4. عرض الصفحة النهائية
        return Inertia::render('News/Show', [
            'newsItem'    => $this->mapNewsItem($item),
            'relatedNews' => $relatedNews
        ]);
    }
}