<?php

namespace App\Services;

use App\Models\ProductAttribute;

class ProductAttributeService
{
    public function getAttributesByType($type)
    {
        return app(\App\Services\CatalogReadCache::class)->remember("product_attributes_{$type}", 3600, function () use ($type) {
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
        app(CatalogReadCache::class)->invalidate();
    }
}
