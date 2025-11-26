<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'product_variant_id',
        'rating',
        'content',
        'order_id',
        'tags',
        'status',
    ];
    protected $casts = [
        'tags' => 'array',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function media()
    {
        return $this->hasMany(ReviewMedia::class);
    }

    public function experiences()
    {
        return $this->hasMany(ReviewExperience::class);
    }
}
