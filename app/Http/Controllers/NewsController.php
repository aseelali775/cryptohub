<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function index()
    {
        $newsFeed = News::latest()->get()->map(function($item) {
            $locale = app()->getLocale();
            $isArabic = ($locale === 'ar');

            // إذا تمت معالجة الخبر بالذكاء الاصطناعي واللغة عربية، نستخدم العنوان المترجم
            $title = ($isArabic && $item->ai_processed && $item->title_ar) 
                        ? $item->title_ar 
                        : $item->title_en;

            // في اللغة العربية نعتمد على الملخص الذكي summary_ar بدلاً من النص الخام
            $summary = $isArabic 
                        ? ($item->summary_ar ?? $item->content_ar) 
                        : $item->content_en;

            return [
                'id'           => $item->id,
                'title'        => $title,
                'summary'      => $summary,
                'content'      => $summary,
                'image_url'    => $item->image_url,
                'source'       => $item->source,
                'url'          => $item->url,
                'sentiment'    => $item->sentiment ?? 'Neutral',
                'category'     => $item->category ?? 'General',
                'impact_score' => $item->impact_score ?? 5,
                'ai_processed' => (bool) $item->ai_processed,
                'date'         => $item->created_at ? $item->created_at->diffForHumans() : ''
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
        $isArabic = ($locale === 'ar');

        $title = ($isArabic && $item->ai_processed && $item->title_ar) 
                    ? $item->title_ar 
                    : $item->title_en;

        $summary = $isArabic 
                    ? ($item->summary_ar ?? $item->content_ar) 
                    : $item->content_en;

        $newsItem = [
            'id'           => $item->id,
            'title'        => $title,
            'summary'      => $summary,
            'content_en'   => $item->content_en,
            'image_url'    => $item->image_url,
            'source'       => $item->source,
            'url'          => $item->url,
            'sentiment'    => $item->sentiment ?? 'Neutral',
            'category'     => $item->category ?? 'General',
            'impact_score' => $item->impact_score ?? 5,
            'ai_processed' => (bool) $item->ai_processed,
            'date'         => $item->created_at ? $item->created_at->diffForHumans() : ''
        ];

        return Inertia::render('News/Show', [
            'newsItem' => $newsItem
        ]);
    }
}