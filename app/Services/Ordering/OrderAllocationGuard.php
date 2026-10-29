<?php

namespace App\Services\Ordering;

use App\Exceptions\OrderTransitionConflict;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;

class OrderAllocationGuard
{
    public function __construct(private OrderReservationEvidence $evidence) {}

    public function lock(Order $order): array
    {
        $items = $order->items()->get(['variant_id', 'quantity'])->toArray();
        $movements = InventoryTransaction::where('reference_type', 'order')->where('reference_id', $order->id)->get()->toArray();
        $allocation = $this->evidence->allocation($items, $movements, (int) $order->warehouse_id, $order->status);
        foreach ($allocation as $variant => $quantity) {
            $inventory = Inventory::where('warehouse_id', $order->warehouse_id)->where('product_variant_id', $variant)
                ->lockForUpdate()->first();
            if (! $inventory) {
                throw new OrderTransitionConflict('missing_inventory', 'Không tìm thấy tồn kho đã giữ. Cần đối soát đơn.');
            }
            $this->evidence->assertBalance($inventory->getAttributes(), $quantity, $order->status !== 'shipping');
        }

        return $allocation;
    }
}
