<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function index()
    {
        $newsFeed = News::latest()->get()->map(function($item) {
            return [
                'id'           => $item->id,
                // إذا تمت المعالجة نرسل العنوان الجديد، وإلا القديم
                'title'        => $item->ai_processed ? $item->ai_title : $item->title_en,
                'summary'      => $item->ai_processed ? $item->ai_summary : mb_substr($item->content_en, 0, 150) . '...',
                'content'      => $item->ai_processed ? $item->ai_content : $item->content_en,
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

        $newsItem = [
            'id'           => $item->id,
            'title'        => $item->ai_processed ? $item->ai_title : $item->title_en,
            'summary'      => $item->ai_processed ? $item->ai_summary : null,
            'content'      => $item->ai_processed ? $item->ai_content : $item->content_en,
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