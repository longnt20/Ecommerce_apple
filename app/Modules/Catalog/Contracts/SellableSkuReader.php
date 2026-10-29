<?php

namespace App\Modules\Catalog\Contracts;

interface SellableSkuReader
{
    public function find(int $productId, ?int $variantId): SellableSku;
}
