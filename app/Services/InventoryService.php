<?php
namespace App\Services;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InventoryService
{
     public function import($warehouseId, $productVariantId, $quantity, $unitCost = null, $notes = null)
    {
        return DB::transaction(function () use ($warehouseId, $productVariantId, $quantity, $unitCost, $notes) {
            // Get or create inventory record
            $inventory = Inventory::firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $productVariantId
                ],
                [
                    'quantity' => 0,
                    'available_quantity' => 0
                ]
            );

            $beforeQuantity = $inventory->quantity;
            
            // Update inventory
            $inventory->quantity += $quantity;
            $inventory->available_quantity += $quantity;
            $inventory->last_received_at = now();
            $inventory->save();

            // Create transaction
            $transaction = InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_variant_id' => $productVariantId,
                'type' => 'import',
                'quantity' => $quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $inventory->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost ? $unitCost * $quantity : null,
                'notes' => $notes,
                'status' => 'completed'
            ]);

            return $transaction;
        });
    }

    /**
     * Export stock from warehouse
     */
    public function export($warehouseId, $productVariantId, $quantity, $referenceType = null, $referenceId = null, $notes = null)
    {
        return DB::transaction(function () use ($warehouseId, $productVariantId, $quantity, $referenceType, $referenceId, $notes) {
            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if (!$inventory || $inventory->available_quantity < $quantity) {
                throw new \Exception('Không đủ hàng trong kho');
            }

            $beforeQuantity = $inventory->quantity;
            
            // Update inventory
            $inventory->quantity -= $quantity;
            $inventory->available_quantity -= $quantity;
            $inventory->last_shipped_at = now();
            $inventory->save();

            // Create transaction
            $transaction = InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_variant_id' => $productVariantId,
                'type' => 'export',
                'quantity' => -$quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $inventory->quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'status' => 'completed'
            ]);

            return $transaction;
        });
    }

    /**
     * Transfer stock between warehouses
     */
    public function transfer($fromWarehouseId, $toWarehouseId, $productVariantId, $quantity, $notes = null)
    {
        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $productVariantId, $quantity, $notes) {
            // Export from source warehouse
            $fromInventory = Inventory::where('warehouse_id', $fromWarehouseId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if (!$fromInventory || $fromInventory->available_quantity < $quantity) {
                throw new \Exception('Không đủ hàng để chuyển');
            }

            $fromBefore = $fromInventory->quantity;
            $fromInventory->quantity -= $quantity;
            $fromInventory->available_quantity -= $quantity;
            $fromInventory->save();

            // Import to destination warehouse
            $toInventory = Inventory::firstOrCreate(
                [
                    'warehouse_id' => $toWarehouseId,
                    'product_variant_id' => $productVariantId
                ],
                [
                    'quantity' => 0,
                    'available_quantity' => 0
                ]
            );

            $toBefore = $toInventory->quantity;
            $toInventory->quantity += $quantity;
            $toInventory->available_quantity += $quantity;
            $toInventory->save();

            // Create transaction
            $transaction = InventoryTransaction::create([
                'warehouse_id' => $fromWarehouseId,
                'product_variant_id' => $productVariantId,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'type' => 'transfer',
                'quantity' => $quantity,
                'before_quantity' => $fromBefore,
                'after_quantity' => $fromInventory->quantity,
                'notes' => $notes,
                'status' => 'completed'
            ]);

            return $transaction;
        });
    }

    /**
     * Adjust inventory (for inventory counting)
     */
    public function adjust($warehouseId, $productVariantId, $actualQuantity, $reason = null)
    {
        return DB::transaction(function () use ($warehouseId, $productVariantId, $actualQuantity, $reason) {
            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if (!$inventory) {
                throw new \Exception('Sản phẩm không tồn tại trong kho');
            }

            $beforeQuantity = $inventory->quantity;
            $difference = $actualQuantity - $beforeQuantity;

            // Update inventory
            $inventory->quantity = $actualQuantity;
            $inventory->available_quantity = $actualQuantity - $inventory->reserved_quantity;
            $inventory->last_counted_at = now();
            $inventory->save();

            // Create transaction
            $transaction = InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_variant_id' => $productVariantId,
                'type' => 'adjust',
                'quantity' => $difference,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $actualQuantity,
                'reason' => $reason,
                'status' => 'completed'
            ]);

            return $transaction;
        });
    }

    /**
     * Reserve stock for order
     */
    public function reserve($warehouseId, $productVariantId, $quantity, $orderId)
    {
        return DB::transaction(function () use ($warehouseId, $productVariantId, $quantity, $orderId) {
            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if (!$inventory || $inventory->available_quantity < $quantity) {
                throw new \Exception('Không đủ hàng để giữ');
            }

            // Reserve stock
            $inventory->reserved_quantity += $quantity;
            $inventory->available_quantity -= $quantity;
            $inventory->save();

            // Create transaction
            $transaction = InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_variant_id' => $productVariantId,
                'type' => 'reserve',
                'quantity' => $quantity,
                'before_quantity' => $inventory->quantity,
                'after_quantity' => $inventory->quantity,
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'status' => 'completed'
            ]);

            return $transaction;
        });
    }

    /**
     * Check stock availability across all warehouses
     */
    public function checkAvailability($productVariantId, $quantity = null)
    {
        $query = Inventory::where('product_variant_id', $productVariantId);
        
        if ($quantity) {
            return $query->where('available_quantity', '>=', $quantity)->exists();
        }

        return $query->sum('available_quantity');
    }

    /**
     * Get stock by warehouse
     */
    public function getStockByWarehouse($productVariantId)
    {
        return Inventory::with('warehouse')
            ->where('product_variant_id', $productVariantId)
            ->get()
            ->map(function ($inventory) {
                return [
                    'warehouse' => $inventory->warehouse->name,
                    'quantity' => $inventory->quantity,
                    'available' => $inventory->available_quantity,
                    'reserved' => $inventory->reserved_quantity,
                    'location' => $inventory->location
                ];
            });
    }
}
