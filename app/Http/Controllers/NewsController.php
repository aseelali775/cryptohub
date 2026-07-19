<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan; // 🟢 استدعاء أداة الأوامر

class NewsController extends Controller
{
    public function index()
    {
        // 🟢 نظام التحديث التلقائي الذكي (Auto-Fetch on Visit)
        $latestNews = News::latest()->first();
        // إذا لم يكن هناك أخبار، أو أن أحدث خبر مر عليه 60 دقيقة أو أكثر
        if (!$latestNews || $latestNews->created_at->diffInMinutes(now()) >= 60) {
            // تشغيل محرك سحب الأخبار بصمت في الخلفية
            Artisan::call('crypto:fetch-news');
        }

        $locale = app()->getLocale();

        // جلب الأخبار وتجهيزها
        $newsFeed = News::latest()->get()->map(function($item) use ($locale) {
            return [
                'id' => $item->id,
                'title' => $locale === 'ar' ? $item->title_ar : $item->title_en,
                'content' => $locale === 'ar' ? $item->content_ar : $item->content_en,
                'image_url' => $item->image_url,
                'source' => $item->source,
                'date' => $item->created_at ? $item->created_at->diffForHumans() : ''
            ];
        });

        return Inertia::render('News/Index', [
            'newsFeed' => $newsFeed
        ]);
    }

    // ... دالة show كما هي لا تغيرها ...
    public function show($id)
    {
        $item = News::findOrFail($id);
        $locale = app()->getLocale();

        $newsItem = [
            'id'        => $item->id,
            'title'     => $locale === 'ar' ? $item->title_ar : $item->title_en,
            'content'   => $locale === 'ar' ? $item->content_ar : $item->content_en,
            'image_url' => $item->image_url,
            'source'    => $item->source,
            'date'      => $item->created_at ? $item->created_at->diffForHumans() : ''
        ];

        return Inertia::render('News/Show', [
            'newsItem' => $newsItem
        ]);
    }
}