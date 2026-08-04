<?php

namespace App\Services;

class NewsFormatterService
{
    /**
     * تغليف بيانات الخبر لتكون جاهزة لواجهات Vue باللغتين
     * @param \App\Models\News $item
     * @return array|null
     */
    public static function format($item)
    {
        if (!$item) return null;

        // ضمان أن الكلمات المفتاحية دائماً مصفوفة حتى لو كانت فارغة أو محفوظة بشكل خاطئ
        $keywords = is_string($item->keywords) ? json_decode($item->keywords, true) : $item->keywords;
        $keywords = is_array($keywords) ? $keywords : [];

        return [
            'id'           => $item->id,
            'slug'         => $item->slug ?? 'news-' . $item->id,
            'keywords'     => $keywords, // 🟢 تم تأمين الكلمات المفتاحية
            'image_url'    => $item->image_url,
            'source'       => $item->source,
            'url'          => $item->url,
            'sentiment'    => $item->sentiment ?? 'Neutral',
            'category'     => $item->category ?? 'General',
            'impact_score' => (int) ($item->impact_score ?? 5), // 🟢 تأكيد كونه رقماً
            'ai_processed' => (bool) $item->ai_processed,
            'date'         => $item->created_at ? $item->created_at->diffForHumans() : '',
            
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
}