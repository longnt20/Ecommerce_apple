<?php

namespace App\Modules\Pricing\Contracts;

interface Pricing
{
    public function sku(int $productId, ?int $variantId): QuoteLine;

    public function quote(array $items): Quote;
}
