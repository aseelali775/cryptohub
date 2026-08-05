<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoAiReport extends Model
{
    protected $fillable = [
        'cryptocurrency_id', 'trend', 'confidence', 'strength_score', 
        'summary', 'bullish_factors', 'risk_factors', 'generated_at'
    ];

    protected $casts = [
        'bullish_factors' => 'array',
        'risk_factors'    => 'array',
        'generated_at'    => 'datetime',
    ];

    public function cryptocurrency()
    {
        return $this->belongsTo(Cryptocurrency::class);
    }
}