<?php

namespace App\Services\Inventory;

use App\Modules\Inventory\Contracts\InventoryMaintenance;
use App\Modules\Ordering\Contracts\LegacyOrders;
use App\SharedKernel\Transactions;

/** Maintenance-only compatibility coordinator. Owners: Ordering (evidence reads), Inventory (metadata writes). */
class LegacyReservationBackfill
{
    public function __construct(private LegacyOrders $orders, private InventoryMaintenance $inventory, private Transactions $transactions) {}

    public function run(bool $apply): array
    {
        return $this->transactions->run(fn () => $this->inventory->classify($apply, $this->orders->allocations()));
    }
}
