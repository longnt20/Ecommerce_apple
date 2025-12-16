<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function changeStatus(Order $order, string $newStatus): void
    {
        DB::transaction(function () use ($order, $newStatus) {

            // CONFIRM → trừ kho (commit)
            if ($order->status === 'pending' && $newStatus === 'confirmed') {
                $this->confirm($order);
            }

            // CANCEL → trả kho (release)
            if ($order->status === 'pending' && $newStatus === 'cancelled') {
                $this->cancel($order);
            }

            // Các trạng thái khác không đụng kho
            $order->update(['status' => $newStatus]);
        });
    }
    protected function confirm(Order $order)
    {
        foreach ($order->items as $item) {
            $this->inventoryService->commitOrder(
                $order->warehouse_id,
                $item->variant_id,
                $item->quantity,
                $order->id
            );
        }
    }

    protected function cancel(Order $order)
    {
        foreach ($order->items as $item) {
            $this->inventoryService->releaseOrder(
                $order->warehouse_id,
                $item->variant_id,
                $item->quantity,
                $order->id
            );
        }
    }
}
