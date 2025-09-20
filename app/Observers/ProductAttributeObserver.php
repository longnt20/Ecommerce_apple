<?php

namespace App\Observers;

use App\Models\ProductAttribute;
use App\Services\ProductAttributeService;

class ProductAttributeObserver
{
    protected $attributeService;

    public function __construct(ProductAttributeService $attributeService)
    {
        $this->attributeService = $attributeService;
    }

    public function saved(ProductAttribute $productAttribute)
    {
        $this->attributeService->clearCache();
    }

    public function deleted(ProductAttribute $productAttribute)
    {
        $this->attributeService->clearCache();
    }
}