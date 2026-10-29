<?php

namespace App\Modules\Inventory\Contracts;

interface InventoryMaintenance
{
    /** @param list<ReservationOrder> $orders */
    public function classify(bool $apply, array $orders): array;

    public function reconcile(): array;
}
