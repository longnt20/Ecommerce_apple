<?php

namespace App\Modules\Ordering\Infrastructure;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTransition;
use App\Modules\Inventory\Contracts\ReservationLine;
use App\Modules\Inventory\Contracts\ReservationOrder;
use App\Modules\Ordering\Application\Ports\OrderStore;
use App\Modules\Ordering\Contracts\LegacyOrders;
use App\Modules\Ordering\Contracts\OrderSnapshot;
use App\Modules\Ordering\Contracts\TransitionReceipt;
use App\Modules\Pricing\Contracts\Quote;
use App\SharedKernel\ActorContext;
use App\SharedKernel\Clock;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class EloquentOrderStore implements LegacyOrders, OrderStore
{
    public function __construct(private Clock $clock) {}

    public function enabled(): bool
    {
        return config('ordering.transitions_enabled') && Schema::hasTable('order_transitions');
    }

    public function snapshot(int $id, bool $lock = false): OrderSnapshot
    {
        $order = Order::whereKey($id)->when($lock, fn ($query) => $query->lockForUpdate())->firstOrFail();

        return new OrderSnapshot($order->id, $order->user_id, $order->warehouse_id, $order->status, $order->payment_status, $order->payment_method, (string) $order->final_amount);
    }

    public function create(array $payload, Quote $quote, int $warehouseId, ActorContext $actor): int
    {
        $order = Order::create(array_merge(array_diff_key($payload, array_flip(['mode', 'items'])), [
            'code' => 'DH'.$this->clock->now()->format('Ymd').'-'.Str::ulid(), 'user_id' => $actor->id, 'warehouse_id' => $warehouseId,
            'status' => 'pending', 'payment_status' => 'unpaid', 'total_price' => $quote->total, 'final_amount' => $quote->total]));
        foreach ($quote->lines as $line) {
            $order->items()->create(['product_id' => $line->sku->productId, 'variant_id' => $line->sku->variantId,
                'product_name' => $line->sku->productName, 'variant_name' => $line->sku->variantName,
                'quantity' => $line->quantity, 'price' => $line->price, 'total' => $line->total]);
        }

        return $order->id;
    }

    public function allocation(OrderSnapshot $order): ReservationOrder
    {
        $lines = OrderItem::where('order_id', $order->id)->orderBy('variant_id')->get()
            ->map(fn ($item) => new ReservationLine($item->id, (int) $item->variant_id, (int) $item->quantity))->all();

        return new ReservationOrder($order->id, $order->warehouseId, $order->status, $order->paymentMethod, $lines);
    }

    public function consumeCart(ActorContext $actor, Quote $quote): void
    {
        $cart = Cart::where('user_id', $actor->id)->first();
        foreach ($quote->lines as $line) {
            $item = $cart?->items()->where('product_id', $line->sku->productId)->where('product_variant_id', $line->sku->variantId)->lockForUpdate()->first();
            if ($item) {
                $item->quantity <= $line->quantity ? $item->delete() : $item->update(['quantity' => $item->quantity - $line->quantity]);
            }
        }
    }

    public function previous(int $orderId, string $hash): ?TransitionReceipt
    {
        $row = OrderTransition::where('order_id', $orderId)->where('operation_hash', $hash)->first();

        return $row ? new TransitionReceipt($row->id, $row->payload_hash) : null;
    }

    public function record(int $orderId, ActorContext $actor, string $from, string $to, string $operationHash, string $payloadHash,
        ?string $reason, ?string $requestId, \DateTimeImmutable $at): TransitionReceipt
    {
        $row = OrderTransition::create(['order_id' => $orderId, 'actor_id' => $actor->id, 'from_status' => $from, 'to_status' => $to,
            'operation_hash' => $operationHash, 'payload_hash' => $payloadHash, 'reason' => $reason, 'request_id' => $requestId, 'created_at' => $at]);

        return new TransitionReceipt($row->id, $row->payload_hash);
    }

    public function setStatus(int $orderId, string $status, \DateTimeImmutable $at): void
    {
        $changes = ['status' => $status];
        if ($status === 'cancelled') {
            $changes['cancelled_at'] = $at;
        } elseif ($status === 'completed') {
            $changes['delivered_at'] = $at;
        }
        Order::whereKey($orderId)->update($changes);
    }

    public function markPaid(int $orderId, string $transaction, \DateTimeImmutable $at): void
    {
        Order::whereKey($orderId)->update(['payment_status' => 'paid', 'transaction_id' => $transaction, 'paid_at' => $at]);
    }

    public function allocations(): array
    {
        return Order::orderBy('id')->get()->map(fn ($order) => $this->allocation(new OrderSnapshot($order->id, $order->user_id, $order->warehouse_id,
            $order->status, $order->payment_status, $order->payment_method, (string) $order->final_amount)))->all();
    }
}
