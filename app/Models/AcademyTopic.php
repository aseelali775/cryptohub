<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyTopic extends Model
{
    protected $fillable = [
    'name',
    'name_ar',
    'name_en',

    'slug',

    'description',
    'description_ar',
    'description_en',

    'image',
    'sort_order',
    'is_active',
];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(AcademyArticle::class, 'topic_id');
    }

   public function getLocalizedNameAttribute()
{
    if (app()->getLocale() === 'ar') {
        return $this->name_ar ?: $this->name;
    }

    return $this->name_en ?: $this->name;
}

public function getLocalizedDescriptionAttribute()
{
    if (app()->getLocale() === 'ar') {
        return $this->description_ar ?: $this->description;
    }

    return $this->description_en ?: $this->description;
}
}