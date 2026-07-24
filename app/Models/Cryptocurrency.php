<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cryptocurrency extends Model
{
    use HasFactory;

    /**
     * الحقول المسموح بتعبئتها وتحديثها تلقائياً.
     */
    protected $fillable = [
        'coingecko_id', // 🟢 المعرف الفريد الذي اقترحناه
        'name',
        'symbol',
        'image_url',
        'current_price',
        'change_24h',
        'volume_24h',
        'market_cap',   // 🟢 القيمة السوقية
    ];
}