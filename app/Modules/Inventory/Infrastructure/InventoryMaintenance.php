<?php

namespace App\Modules\Inventory\Infrastructure;

use App\Modules\Inventory\Contracts\InventoryMaintenance as Contract;

final class InventoryMaintenance implements Contract
{
    public function __construct(private LegacyReservationBackfill $backfill, private ReconcileInventory $reconciliation) {}

    public function classify(bool $apply, array $orders): array
    {
        return $this->backfill->run($apply, $orders);
    }

    public function reconcile(): array
    {
        return $this->reconciliation->report();
    }
}
