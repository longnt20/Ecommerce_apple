<?php

namespace App\Modules\Inventory\Contracts;

final readonly class ReservationLine
{
    public function __construct(public int $orderItemId, public int $variantId, public int $quantity) {}
}
