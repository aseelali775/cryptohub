<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_en', 'title_ar', 'content_en', 'content_ar', 'image_url', 
        'source', 'url', 'summary_ar', 'why_it_matters_ar', 'sentiment', 'category', 'impact_score', 'ai_processed',
        'slug', 'keywords'
    ];

    // تحويل الكلمات المفتاحية لمصفوفة آلياً
    protected $casts = [
        'keywords' => 'array',
    ];
}