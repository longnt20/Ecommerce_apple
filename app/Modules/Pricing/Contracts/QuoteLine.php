<?php

namespace App\Modules\Pricing\Contracts;

use App\Modules\Catalog\Contracts\SellableSku;

final readonly class QuoteLine
{
    public function __construct(public SellableSku $sku, public int $quantity, public int $price, public int $total) {}
}
