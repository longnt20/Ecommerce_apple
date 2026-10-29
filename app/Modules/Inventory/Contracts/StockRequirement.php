<?php

namespace App\Modules\Inventory\Contracts;

final readonly class StockRequirement
{
    public function __construct(public int $variantId, public int $quantity) {}
}
