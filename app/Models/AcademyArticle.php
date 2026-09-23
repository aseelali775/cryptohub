<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademyArticle extends Model
{
    protected $fillable = [
    'topic_id',

    'title',
    'title_ar',
    'title_en',

    'slug',

    'excerpt',
    'excerpt_ar',
    'excerpt_en',

    'content',
    'content_ar',
    'content_en',

    'image',

    'seo_title',
    'seo_title_ar',
    'seo_title_en',

    'meta_description',
    'meta_description_ar',
    'meta_description_en',

    'status',
    'sort_order',
    'published_at',

    'faq_ar',
    'faq_en',
];

    protected $casts = [
        'published_at' => 'datetime',
        'sort_order' => 'integer',
        'faq_ar' => 'array',
        'faq_en' => 'array',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(AcademyTopic::class, 'topic_id');
    }

public function getLocalizedTitleAttribute()
{
    if (app()->getLocale() === 'ar') {
        return $this->title_ar ?: $this->title;
    }

    return $this->title_en ?: $this->title;
}

public function getLocalizedExcerptAttribute()
{
    if (app()->getLocale() === 'ar') {
        return $this->excerpt_ar ?: $this->excerpt;
    }

    return $this->excerpt_en ?: $this->excerpt;
}

public function getLocalizedContentAttribute()
{
    if (app()->getLocale() === 'ar') {
        return $this->content_ar ?: $this->content;
    }

    return $this->content_en ?: $this->content;
}

public function getLocalizedSeoTitleAttribute()
{
    if (app()->getLocale() === 'ar') {
        return $this->seo_title_ar ?: $this->seo_title;
    }

    return $this->seo_title_en ?: $this->seo_title;
}

public function getLocalizedMetaDescriptionAttribute()
{
    if (app()->getLocale() === 'ar') {
        return $this->meta_description_ar ?: $this->meta_description;
    }

    return $this->meta_description_en ?: $this->meta_description;
}
}