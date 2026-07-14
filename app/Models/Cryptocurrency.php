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
        'name',
        'symbol',
        'current_price',
        'change_24h',
        'volume_24h',
        'image_url',
    ];
}