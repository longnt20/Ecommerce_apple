<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'thumbnail',
        'slug',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    public function promotionItems()
    {
        return $this->hasMany(PromotionItem::class);
    }
}
