<?php

namespace App\Modules\Ordering\Application;

use App\Exceptions\InventoryConflict;
use App\Modules\Identity\Contracts\AccountAccess;
use App\Modules\Inventory\Contracts\InventoryOperations;
use App\Modules\Inventory\Contracts\InventoryReservations;
use App\Modules\Inventory\Contracts\StockRequirement;
use App\Modules\Ordering\Application\Ports\OrderStore;
use App\Modules\Ordering\Contracts\Checkout;
use App\Modules\Ordering\Contracts\OrderSnapshot;
use App\Modules\Payments\Contracts\PaymentLedger;
use App\Modules\Pricing\Contracts\Pricing;
use App\SharedKernel\ActorContext;

final class PlaceOrder implements Checkout
{
    public function __construct(private InventoryOperations $operations, private InventoryReservations $reservations,
        private Pricing $pricing, private PaymentLedger $payments, private AccountAccess $accounts, private OrderStore $orders) {}

    public function place(array $input, ActorContext $actor): OrderSnapshot
    {
        $this->operations->assertEnabled();
        if (! $this->accounts->active($actor)) {
            throw new InventoryConflict('forbidden_actor', 'Tài khoản không hoạt động.');
        }
        $items = array_map(fn ($item) => ['product_id' => (int) $item['product_id'], 'variant_id' => (int) $item['variant_id'], 'quantity' => (int) $item['quantity']], $input['items']);
        usort($items, fn ($a, $b) => $a['variant_id'] <=> $b['variant_id']);
        $payload = array_merge(array_intersect_key($input, array_flip(['fullname', 'phone', 'email', 'address', 'ward', 'district', 'province', 'note', 'payment_method'])),
            ['mode' => $input['mode'] ?? 'cart', 'items' => $items]);
        ksort($payload);
        $result = $this->operations->execute('checkout', $actor, $input['idempotency_key'], $payload,
            function ($operationId) use ($payload, $actor) {
                if ($payload['payment_method'] === 'vnpay') {
                    $this->payments->assertAvailable();
                }
                $quote = $this->pricing->quote($payload['items']);
                $warehouse = $this->reservations->allocate(array_map(fn ($line) => new StockRequirement($line->sku->variantId, $line->quantity), $quote->lines));
                $id = $this->orders->create($payload, $quote, $warehouse, $actor);
                $order = new OrderSnapshot($id, $actor->id, $warehouse, 'pending', 'unpaid', $payload['payment_method'], (string) $quote->total);
                $this->reservations->reserve($this->orders->allocation($order), $operationId, $actor);
                if ($payload['payment_method'] === 'vnpay') {
                    $this->payments->start($order->payment($this->reservations->paymentHold($id)));
                }
                if ($payload['mode'] === 'cart') {
                    $this->orders->consumeCart($actor, $quote);
                }

                return ['order_id' => $id];
            });
        $order = $this->orders->snapshot($result['order_id']);
        if ($order->userId !== $actor->id) {
            throw new \LogicException('Checkout replay actor mismatch.');
        }

        return $order;
    }
}
