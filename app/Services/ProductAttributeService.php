<?php

namespace App\Services;

use App\Models\ProductAttribute;
use Illuminate\Support\Facades\Cache;

class ProductAttributeService
{
    public function getAttributesByType($type)
    {
        return Cache::remember("product_attributes_{$type}", 3600, function () use ($type) {
            return ProductAttribute::active()
                ->byType($type)
                ->ordered()
                ->pluck('label', 'value')
                ->toArray();
        });
    }

    public function getColors()
    {
        return $this->getAttributesByType(ProductAttribute::TYPE_COLOR);
    }

    public function getStorages()
    {
        return $this->getAttributesByType(ProductAttribute::TYPE_STORAGE);
    }

    public function clearCache()
    {
        Cache::forget('product_attributes_color');
        Cache::forget('product_attributes_storage');
    }
}