<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable; // 👈 استدعاء ميزة التنظيف

class News extends Model
{
    use HasFactory, Prunable; // 👈 تفعيل الميزة

    protected $fillable = [
        'title_en', 'title_ar', 'content_en', 'content_ar', 'image_url', 'source',
    ];

    /**
     * تحديد شرط الحذف التلقائي
     */
    public function prunable()
    {
        // 🟢 تنظيف الأخبار التي مضى على إنشائها أكثر من 30 يوماً
        return static::where('created_at', '<', now()->subDays(30));
    }
}