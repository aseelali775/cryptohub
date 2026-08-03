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
            'image_url'    => $item->image_url,
            'source'       => $item->source,
            'url'          => $item->url,
            'sentiment'    => $item->sentiment ?? 'Neutral',
            'category'     => $item->category ?? 'General',
            'impact_score' => $item->impact_score ?? 5,
            'ai_processed' => (bool) $item->ai_processed,
            'date'         => $item->created_at ? $item->created_at->diffForHumans() : '',
            
            // الهيكلة الذكية الجديدة
            'translations' => [
                'ar' => [
                    'title'          => $item->title_ar ?? $item->title_en,
                    'content'        => $item->content_ar ?? $item->content_en,
                    'summary'        => $item->summary_ar ?? mb_substr($item->content_en, 0, 150) . '...',
                    'why_it_matters' => $item->why_it_matters_ar,
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
        $item = News::findOrFail($id);

        return Inertia::render('News/Show', [
            'newsItem' => $this->mapNewsItem($item)
        ]);
    }
}