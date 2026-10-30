<?php

namespace App\Modules\Ordering\Application;

use App\Modules\Inventory\Contracts\InventoryOperations;
use App\Modules\Inventory\Contracts\InventoryReservations;
use App\Modules\Ordering\Application\Ports\OrderStore;
use App\Modules\Ordering\Contracts\ReservationExpiry;
use App\Modules\Payments\Contracts\PaymentLedger;
use App\SharedKernel\ActorContext;
use App\SharedKernel\Clock;
use App\SharedKernel\Transactions;

final class ExpireReservations implements ReservationExpiry
{
    public function __construct(private OrderStore $orders, private InventoryOperations $operations, private InventoryReservations $reservations,
        private PaymentLedger $payments, private Transactions $transactions, private Clock $clock, private \App\Modules\Pricing\Contracts\CouponRedemptions $coupons) {}

    public function due(int $limit = 100): array
    {
        return $this->reservations->due($limit);
    }

    public function expire(int $orderId): bool
    {
        $this->operations->assertEnabled();

        return $this->transactions->run(function () use ($orderId) {
            $order = $this->orders->snapshot($orderId, true);
            $attemptPaid = $this->payments->paidAttempt($orderId);
            $hold = $this->reservations->paymentHold($orderId);
            if (! $hold || $hold->status !== 'active' || $order->status !== 'pending' || $hold->expiresAt === null || $hold->expiresAt > $this->clock->now()) {
                return false;
            }
            if ($order->paymentStatus === 'paid' || $attemptPaid) {
                $this->reservations->retainAfterPayment($orderId);

                return false;
            }
            $receipt = $this->orders->record($orderId, ActorContext::system(), 'pending', 'cancelled', hash('sha256', 'system-expiry:'.$hold->id),
                hash('sha256', json_encode([$orderId, $hold->id, 'expired'])), 'Reservation expired', null, $this->clock->now());
            $this->reservations->expire($this->orders->allocation($order), $receipt->id);
            $this->coupons->finish($orderId, false);
            $this->orders->setStatus($orderId, 'cancelled', $this->clock->now());

            return true;
        });
    }
}
