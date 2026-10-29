<?php

namespace App\Services\Ordering;

use App\Exceptions\InventoryConflict;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\Inventory\IdempotentOperation;
use App\Services\Inventory\ReservationManager;
use App\Services\Inventory\StockWriter;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentService;
use App\Services\Pricing\QuoteService;
use Illuminate\Support\Str;

class PlaceOrder
{
    public function __construct(private IdempotentOperation $operations, private ReservationManager $reservations,
        private QuoteService $pricing, private PaymentService $payments, private PaymentGateway $gateway, private StockWriter $writer) {}

    public function place(array $input, int $actorId): Order
    {
        $this->writer->assertEnabled();
        if (User::find($actorId)?->status !== 'active') {
            throw new InventoryConflict('forbidden_actor', 'Tài khoản không hoạt động.');
        }
        $items = array_map(fn ($item) => ['product_id' => (int) $item['product_id'], 'variant_id' => (int) $item['variant_id'], 'quantity' => (int) $item['quantity']], $input['items']);
        usort($items, fn ($a, $b) => $a['variant_id'] <=> $b['variant_id']);
        $payload = array_merge(array_intersect_key($input, array_flip(['fullname', 'phone', 'email', 'address', 'ward', 'district', 'province', 'note', 'payment_method'])),
            ['mode' => $input['mode'] ?? 'cart', 'items' => $items]);
        ksort($payload);
        $result = $this->operations->run('checkout', $actorId, $input['idempotency_key'], $payload,
            function ($operationId) use ($payload, $actorId) {
                if ($payload['payment_method'] === 'vnpay') {
                    $this->gateway->assertConfigured();
                }
                $quote = $this->pricing->quote($payload['items']);
                $warehouse = $this->reservations->allocate($quote['lines']);
                $order = Order::create(array_merge(array_diff_key($payload, array_flip(['mode', 'items'])), [
                    'code' => 'DH'.date('Ymd').'-'.Str::ulid(), 'user_id' => $actorId, 'warehouse_id' => $warehouse,
                    'status' => 'pending', 'payment_status' => 'unpaid', 'total_price' => $quote['total'], 'final_amount' => $quote['total'],
                ]));
                foreach ($quote['lines'] as $line) {
                    $variant = $line['variant'];
                    $order->items()->create(['product_id' => $line['product']->id, 'variant_id' => $variant->id,
                        'product_name' => $line['product']->name, 'variant_name' => $variant->storage.'-'.$variant->color_label,
                        'quantity' => $line['quantity'], 'price' => $line['price'], 'total' => $line['total']]);
                }
                $this->reservations->reserve($order, $operationId, $actorId);
                if ($payload['payment_method'] === 'vnpay') {
                    $this->payments->start($order);
                }
                if ($payload['mode'] === 'cart') {
                    $cart = Cart::where('user_id', $actorId)->first();
                    foreach ($quote['lines'] as $line) {
                        $item = $cart?->items()->where('product_id', $line['product']->id)->where('product_variant_id', $line['variant']->id)->lockForUpdate()->first();
                        if ($item) {
                            $item->quantity <= $line['quantity'] ? $item->delete() : $item->update(['quantity' => $item->quantity - $line['quantity']]);
                        }
                    }
                }

                return ['order_id' => $order->id];
            });

        return Order::where('user_id', $actorId)->findOrFail($result['order_id']);
    }
}
