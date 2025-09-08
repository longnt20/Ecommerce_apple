<?php

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public static function import($variantId, $warehouseId, $quantity, $serials = [])
    {
        DB::transaction(function () use ($variantId, $warehouseId, $quantity, $serials) {
            $inventory = Inventory::firstOrCreate(
                ['product_variant_id' => $variantId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0, 'reserved' => 0]
            );

            $inventory->increment('quantity', $quantity);

            InventoryTransaction::create([
                'product_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'change' => $quantity,
                'type' => 'import',
            ]);

            foreach ($serials as $serial) {
                InventoryTransaction::create([
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $warehouseId,
                    'change' => 1,
                    'type' => 'import',
                    'serial_number' => $serial,
                ]);
            }
        });
    }
}
