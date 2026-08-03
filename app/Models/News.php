<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class News extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'title_en', 'title_ar', 'content_en', 'content_ar', 'image_url', 
        'source', 'url', 'summary_ar', 'why_it_matters_ar', 'sentiment', 'category', 'impact_score', 'ai_processed'
    ];

    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays(30));
    }
}