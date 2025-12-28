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
        'is_featured',
        'frame_id'
    ];

    public function items()
    {
        return $this->hasMany(PromotionItem::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function frame(){
        return $this->belongsTo(Frame::class);
    }
}
