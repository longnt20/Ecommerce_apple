<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'category_id',
        'thumbnail',
        'gallery',
        'default_price',
        'visibility',
        'status'
    ];
    protected $casts = [
        'gallery' => 'array',
        'default_price' => 'decimal:2'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function specs()
    {
        return $this->hasMany(ProductSpec::class);
    }
    public function promotions()
    {
        return $this->morphMany(PromotionItem::class, 'item');
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function inventories()
    {
        return $this->hasManyThrough(
            Inventory::class,
            ProductVariant::class,
            'product_id',       // product_variants.product_id
            'product_variant_id', // inventory.product_variant_id
            'id',               // products.id
            'id'                // product_variants.id
        );
    }
}
