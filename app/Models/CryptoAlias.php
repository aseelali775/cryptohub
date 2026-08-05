<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoAlias extends Model
{
    protected $fillable = ['cryptocurrency_id', 'alias'];

    public function cryptocurrency()
    {
        return $this->belongsTo(Cryptocurrency::class);
    }

    
}



