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
        'coingecko_id',
        'name',
        'symbol',
        'image_url',

        // بيانات السوق الحالية
        'current_price',
        'change_24h',
        'volume_24h',
        'market_cap',

        // بيانات السوق التاريخية
        'sparkline_in_7d',
        'ath',
        'atl',
        'high_24h',
        'low_24h',
        'ath_date',
        'atl_date',
    ];

    /**
     * تحويل أنواع البيانات تلقائياً.
     */
    protected $casts = [
        'sparkline_in_7d' => 'array',

        'current_price' => 'float',
        'change_24h'    => 'float',
        'volume_24h'    => 'float',
        'market_cap'    => 'float',

        'ath'      => 'float',
        'atl'      => 'float',
        'high_24h' => 'float',
        'low_24h'  => 'float',

        'ath_date' => 'datetime',
        'atl_date' => 'datetime',
    ];

    /**
     * الأسماء البديلة للعملة.
     */
    public function aliases()
    {
        return $this->hasMany(CryptoAlias::class);
    }

    /**
     * تقارير الذكاء الاصطناعي.
     */
    public function aiReports()
    {
        return $this->hasMany(CryptoAiReport::class);
    }
}