<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function index()
    {
        // 1. جلب اللغة الحالية للمنصة (ar أو en)
        $locale = app()->getLocale();

        // 2. جلب الأخبار وتجهيزها باللغة الصحيحة ديناميكياً مع إغلاق الأقواس بدقة
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

        // 3. تمرير البيانات الجاهزة والمغلقة إلى الواجهة
        return Inertia::render('News/Index', [
            'newsFeed' => $newsFeed
        ]);
    }
}