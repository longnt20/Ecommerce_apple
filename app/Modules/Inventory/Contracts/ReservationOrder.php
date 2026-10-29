<?php

namespace App\Modules\Inventory\Contracts;

final readonly class ReservationOrder
{
    /** @param list<ReservationLine> $lines */
    public function __construct(public int $id, public int $warehouseId, public string $status, public string $paymentMethod, public array $lines) {}
}
