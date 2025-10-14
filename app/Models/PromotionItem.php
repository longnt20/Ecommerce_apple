<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionItem extends Model
{
    protected $fillable = [
        'promotion_id',
        'item_id',
        'item_type',
    ];

    public function item()
    {
        return $this->morphTo();
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}

