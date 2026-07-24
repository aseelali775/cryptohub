<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function index()
    {
        // الكنترولر الآن مسؤول فقط عن جلب البيانات وعرضها بأقصى سرعة
        $locale = app()->getLocale();

        $newsFeed = News::latest()->get()->map(function($item) use ($locale) {
            return [
                'id'        => $item->id,
                'title'     => $locale === 'ar' ? $item->title_ar : $item->title_en,
                'content'   => $locale === 'ar' ? $item->content_ar : $item->content_en,
                'image_url' => $item->image_url,
                'source'    => $item->source,
                'date'      => $item->created_at ? $item->created_at->diffForHumans() : ''
            ];
        });

        return Inertia::render('News/Index', [
            'newsFeed' => $newsFeed
        ]);
    }

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