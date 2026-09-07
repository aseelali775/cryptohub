<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        // =========================================================
        // المصدر الأصلي
        // =========================================================
        'title_en',
        'content_en',
        'image_url',
        'source',
        'url',

        // =========================================================
        // المحتوى العربي
        // =========================================================
        'title_ar',
        'content_ar',
        'summary_ar',
        'meta_description_ar',
        'why_it_matters_ar',
        'analysis_ar',
        'context_ar',
        'what_to_watch_ar',
        'limitations_ar',

        // =========================================================
        // حقول AI الإنجليزية
        // =========================================================
        'ai_title',
        'ai_content',
        'ai_summary',

        // =========================================================
        // التصنيف والتحليل
        // =========================================================
        'sentiment',
        'category',
        'impact_score',
        'keywords',

        // =========================================================
        // حالة المقال ومعالجة AI
        // =========================================================
        'ai_processed',
        'status',
        'rejection_reason',

        // =========================================================
        // SEO
        // =========================================================
        'slug',
    ];

    protected $casts = [
        'keywords' => 'array',
        'ai_processed' => 'boolean',
        'impact_score' => 'integer',
    ];
}