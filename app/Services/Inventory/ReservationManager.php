<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryConflict;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderTransition;
use App\Models\Warehouse;

class ReservationManager
{
    public function __construct(private StockWriter $writer, private IdempotentOperation $operations) {}

    public function allocate(array $lines): int
    {
        $this->writer->assertEnabled();
        usort($lines, fn ($a, $b) => $a['variant']->id <=> $b['variant']->id);
        foreach (Warehouse::active()->orderBy('id')->pluck('id') as $warehouse) {
            $this->writer->store->lockWarehouses([(int) $warehouse]);
            $enough = true;
            foreach ($lines as $line) {
                try {
                    $stock = $this->writer->store->lock((int) $warehouse, $line['variant']->id);
                    $this->writer->available($stock, $line['quantity']);
                } catch (InventoryConflict $exception) {
                    if (! in_array($exception->reasonCode, ['missing_inventory', 'insufficient_stock', 'inventory_quarantined', 'stock_balance_conflict', 'reservation_balance_conflict'], true)) {
                        throw $exception;
                    }
                    $enough = false;
                    break;
                }
            }
            if ($enough) {
                return (int) $warehouse;
            }
        }
        throw new InventoryConflict('insufficient_stock', 'Không có một kho đang hoạt động đủ hàng cho toàn bộ đơn.');
    }

    public function reserve(Order $order, string $operationId, int $actorId): InventoryReservation
    {
        $reservation = InventoryReservation::create(['order_id' => $order->id, 'status' => 'active',
            'expires_at' => strtolower($order->payment_method) === 'vnpay' ? now()->addMinutes(config('inventory.online_ttl_minutes', 15)) : null]);
        $this->writer->store->lockWarehouses([(int) $order->warehouse_id]);
        foreach ($order->items()->orderBy('variant_id')->get() as $item) {
            $stock = $this->writer->store->lock((int) $order->warehouse_id, (int) $item->variant_id);
            $this->writer->available($stock, (int) $item->quantity);
            $reservation->items()->create(['order_item_id' => $item->id, 'warehouse_id' => $order->warehouse_id,
                'product_variant_id' => $item->variant_id, 'quantity' => $item->quantity, 'status' => 'active']);
            $this->writer->change($stock, 0, (int) $item->quantity, 'reserve', (int) $item->quantity, $operationId, $actorId,
                ['reference_type' => 'order', 'reference_id' => $order->id, 'reason' => 'Reserve stock for order',
                    'meta_data' => ['reservation_id' => $reservation->id]]);
        }

        return $reservation;
    }

    public function locked(Order $order): InventoryReservation
    {
        $reservation = InventoryReservation::where('order_id', $order->id)->lockForUpdate()->first();
        if (! $reservation || $reservation->status === 'quarantined') {
            throw new InventoryConflict('legacy_allocation_quarantined', 'Đơn chưa có allocation đáng tin; cần đối soát legacy.');
        }

        return $reservation;
    }

    public function validate(Order $order, string $target): InventoryReservation
    {
        $reservation = $this->locked($order);
        $expected = $order->items()->get()->groupBy('variant_id')->map(fn ($items) => (int) $items->sum('quantity'))->all();
        $lines = $reservation->items()->orderBy('warehouse_id')->orderBy('product_variant_id')->get();
        $allocated = $lines->groupBy('product_variant_id')->map(fn ($items) => (int) $items->sum('quantity'))->all();
        ksort($expected);
        ksort($allocated);
        if ($expected === [] || $expected !== $allocated || $lines->contains(fn ($line) => (int) $line->warehouse_id !== (int) $order->warehouse_id)) {
            throw new InventoryConflict('reservation_items_conflict', 'Allocation không khớp snapshot đơn hàng.');
        }
        $state = $target === 'completed' ? 'committed' : 'active';
        if ($reservation->status !== $state || $lines->contains(fn ($line) => $line->status !== $state)) {
            throw new InventoryConflict('reservation_state_conflict', 'Reservation không còn trạng thái phù hợp.');
        }
        if ($target !== 'cancelled' && $reservation->expires_at?->isPast()) {
            throw new InventoryConflict('reservation_expired', 'Reservation đã hết hạn; cần xử lý expiry trước.');
        }
        $this->writer->store->lockWarehouses($lines->pluck('warehouse_id')->all(), $target !== 'cancelled');
        foreach ($lines as $line) {
            $this->writer->store->lock((int) $line->warehouse_id, (int) $line->product_variant_id, false, $target === 'cancelled');
        }

        return $reservation;
    }

    public function transition(Order $order, OrderTransition $transition, string $target): void
    {
        $this->writer->assertEnabled();
        $reservation = $this->validate($order, $target);
        if ($target === 'confirmed') {
            $reservation->update(['expires_at' => null]);

            return;
        }
        if ($target === 'completed') {
            return;
        }
        $this->consume($order, $reservation, $transition->id, $target === 'shipping' ? 'committed' : 'released', $transition->actor_id);
    }

    public function consume(Order $order, InventoryReservation $reservation, int $transitionId, string $state, ?int $actorId): void
    {
        $this->operations->run('reservation-'.$state, $actorId, 'transition:'.$transitionId,
            ['order_id' => $order->id, 'reservation_id' => $reservation->id, 'state' => $state], function ($operationId) use ($order, $reservation, $transitionId, $state, $actorId) {
                if ($reservation->status !== 'active') {
                    throw new InventoryConflict('reservation_state_conflict', 'Reservation đã được xử lý.');
                }
                $lines = $reservation->items()->orderBy('warehouse_id')->orderBy('product_variant_id')->get();
                $this->writer->store->lockWarehouses($lines->pluck('warehouse_id')->all(), $state === 'committed');
                $lines = $reservation->items()->orderBy('warehouse_id')->orderBy('product_variant_id')->lockForUpdate()->get();
                $ids = [];
                foreach ($lines as $line) {
                    if ($line->status !== 'active') {
                        throw new InventoryConflict('reservation_state_conflict', 'Allocation line đã được xử lý.');
                    }
                    $stock = $this->writer->store->lock((int) $line->warehouse_id, (int) $line->product_variant_id, false, $state !== 'committed');
                    $quantity = (int) $line->quantity;
                    $line->update(['status' => $state]);
                    $ids[] = $this->writer->change($stock, $state === 'committed' ? -$quantity : 0, -$quantity,
                        $state === 'committed' ? 'commit' : 'release', $state === 'committed' ? -$quantity : $quantity,
                        $operationId, $actorId, ['reference_type' => 'order', 'reference_id' => $order->id,
                            'reason' => $state === 'expired' ? 'Reservation expired' : ($state === 'committed' ? 'Order handed to carrier' : 'Cancel order'),
                            'meta_data' => ['reservation_id' => $reservation->id, 'order_transition_id' => $transitionId]])->id;
                }
                $reservation->update(['status' => $state, 'expires_at' => null]);

                return ['movement_ids' => $ids];
            });
    }
}
