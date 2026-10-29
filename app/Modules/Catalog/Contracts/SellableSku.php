<?php

namespace App\Modules\Catalog\Contracts;

final readonly class SellableSku
{
    public function __construct(public int $productId, public string $productName, public ?int $variantId,
        public string $variantName, public string $salePrice) {}
}
