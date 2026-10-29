<?php

namespace App\Modules\Inventory\Infrastructure;

use App\Exceptions\InventoryConflict;
use App\Models\Inventory;
use App\Models\InventoryDiscrepancy;
use App\Models\InventoryLegacyHold;
use App\Models\InventoryReservationItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockStore
{
    public const MAX = 2147483647;

    public function lockWarehouses(array $ids, bool $active = true): void
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Stock locks require an enclosing database transaction.');
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);
        $rows = Warehouse::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        if ($rows->count() !== count($ids) || ($active && $rows->contains(fn ($row) => ! $row->is_active))) {
            throw new InventoryConflict('warehouse_unavailable', 'Kho không tồn tại hoặc đã ngừng hoạt động.');
        }
    }

    public function lock(int $warehouseId, int $variantId, bool $create = false, bool $allowDiscrepancy = false): Inventory
    {
        $this->lockWarehouses([$warehouseId], false);
        if ($create) {
            Inventory::firstOrCreate(['warehouse_id' => $warehouseId, 'product_variant_id' => $variantId],
                ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]);
        }
        $stock = Inventory::where('warehouse_id', $warehouseId)->where('product_variant_id', $variantId)->lockForUpdate()->first();
        if (! $stock) {
            throw new InventoryConflict('missing_inventory', 'Chưa có tồn kho cho SKU tại kho này.');
        }
        $this->assertBalance($stock);
        // Current reads under REPEATABLE READ; never join/lock another order's parent.
        $allocated = (int) InventoryReservationItem::where('warehouse_id', $warehouseId)->where('product_variant_id', $variantId)
            ->where('status', 'active')->lockForUpdate()->get(['id', 'quantity'])->sum('quantity');
        $legacy = (int) (InventoryLegacyHold::where('inventory_id', $stock->id)->lockForUpdate()->first()?->quantity ?? 0);
        if ((int) $stock->reserved_quantity !== $allocated + $legacy) {
            throw new InventoryConflict('reservation_balance_conflict', 'Reserved không khớp reservation và legacy hold; cần đối soát.');
        }
        if (! $allowDiscrepancy && InventoryDiscrepancy::where('inventory_id', $stock->id)->where('status', 'open')->lockForUpdate()->first()) {
            throw new InventoryConflict('inventory_quarantined', 'Tồn kho đang được cách ly để đối soát.');
        }

        return $stock;
    }

    public function assertBalance(Inventory $stock): void
    {
        new \App\Modules\Inventory\Domain\StockBalance((int) $stock->quantity, (int) $stock->reserved_quantity, (int) $stock->available_quantity);
    }

    public function positive(mixed $quantity, bool $zero = false): int
    {
        $number = filter_var($quantity, FILTER_VALIDATE_INT);
        if ($number === false || $number < ($zero ? 0 : 1) || $number > self::MAX) {
            throw new InventoryConflict('invalid_quantity', 'Số lượng không hợp lệ.');
        }

        return $number;
    }
}
