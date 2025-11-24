<?php

namespace App\Models;

use App\Services\ProductAttributeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;
    protected $fillable = ['product_id', 'sku', 'price', 'cost_price', 'thumbnail', 'barcode', 'color', 'storage'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function promotions()
    {
        return $this->morphMany(PromotionItem::class, 'item');
    }
    public function getColorLabelAttribute()
    {
        $attributeService = app(ProductAttributeService::class);
        return $attributeService->getColors()[$this->color] ?? null;
    }
}
