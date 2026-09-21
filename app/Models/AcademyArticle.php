<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademyArticle extends Model
{
    protected $fillable = [
        'topic_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'image',
        'seo_title',
        'meta_description',
        'status',
        'sort_order',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(AcademyTopic::class, 'topic_id');
    }
}