<?php

namespace App\Services;

use App\Exceptions\InventoryConflict;
use App\Exceptions\OrderTransitionConflict;
use App\Models\Inventory;
use App\Models\OrderTransition;
use App\Modules\Inventory\Contracts\InventoryOperations;
use App\SharedKernel\ActorContext;

class InventoryService
{
    public function __construct(private InventoryOperations $operations) {}

    public function batch(string $kind, int $actorId, string $key, array $payload, callable $work): array
    {
        return $this->operations->execute('admin-batch-'.$kind, new ActorContext($actorId), $key, $payload, fn () => ['lines' => $work($key)]);
    }

    public function lockWarehouses(array $ids): void
    {
        $this->operations->lockWarehouses($ids);
    }

    public function import($warehouseId, $productVariantId, $quantity, $unitCost = null, $notes = null, ?int $actorId = null, ?string $operationKey = null)
    {
        return $this->run('import', $warehouseId, $productVariantId, $quantity, $actorId, $operationKey, ['unit_cost' => $unitCost, 'notes' => $notes]);
    }

    public function export($warehouseId, $productVariantId, $quantity, $referenceType = null, $referenceId = null, $notes = null, ?int $actorId = null, ?string $operationKey = null, ?string $referenceCode = null)
    {
        return $this->run('export', $warehouseId, $productVariantId, $quantity, $actorId, $operationKey,
            ['reference_type' => $referenceType, 'reference_id' => $referenceId, 'notes' => $notes, 'reference_code' => $referenceCode]);
    }

    public function transfer($fromWarehouseId, $toWarehouseId, $productVariantId, $quantity, $notes = null, ?int $actorId = null, ?string $operationKey = null)
    {
        return $this->run('transfer', $fromWarehouseId, $productVariantId, $quantity, $actorId, $operationKey,
            ['to_warehouse_id' => (int) $toWarehouseId, 'notes' => $notes]);
    }

    public function adjust($warehouseId, $productVariantId, $actualQuantity, $reason = null, ?int $actorId = null, ?string $operationKey = null)
    {
        return $this->run('adjust', $warehouseId, $productVariantId, $actualQuantity, $actorId, $operationKey, ['reason' => $reason]);
    }

    private function run(string $kind, $warehouse, $variant, $quantity, ?int $actorId, ?string $key, array $extra): array
    {
        if ($actorId === null || $key === null) {
            throw new InventoryConflict('operation_context_required', 'Thao tác kho cần actor và mã operation tường minh.');
        }

        return $this->operations->admin($kind, new ActorContext($actorId), $key,
            array_merge(['warehouse_id' => (int) $warehouse, 'variant_id' => (int) $variant, 'quantity' => $quantity], $extra));
    }

    public function reserve($warehouseId, $productVariantId, $quantity, $orderId)
    {
        throw new InventoryConflict('reservation_context_required', 'Giữ hàng phải đi qua checkout và reservation identity.');
    }

    public function commitOrder($warehouseId, $productVariantId, $quantity, $orderId, OrderTransition $operation)
    {
        throw new OrderTransitionConflict('stock_operation_conflict', 'Xuất hàng phải đi qua OrderService và reservation identity.');
    }

    public function releaseOrder($warehouseId, $productVariantId, $quantity, $orderId, OrderTransition $operation)
    {
        throw new OrderTransitionConflict('stock_operation_conflict', 'Trả hàng giữ phải đi qua OrderService và reservation identity.');
    }

    public function checkAvailability($productVariantId, $quantity = null)
    {
        $query = Inventory::where('product_variant_id', $productVariantId);

        return $quantity ? $query->where('available_quantity', '>=', $quantity)->exists() : $query->sum('available_quantity');
    }

    public function getStockByWarehouse($productVariantId)
    {
        return Inventory::with('warehouse')->where('product_variant_id', $productVariantId)->get()
            ->map(fn ($inventory) => ['warehouse' => $inventory->warehouse->name, 'quantity' => $inventory->quantity,
                'available' => $inventory->available_quantity, 'reserved' => $inventory->reserved_quantity, 'location' => $inventory->location]);
    }
}
