<?php

namespace App\Modules\Ordering\Application;

use App\Modules\Inventory\Contracts\InventoryReservations;
use App\Modules\Ordering\Application\Ports\OrderStore;
use App\Modules\Ordering\Contracts\PaymentWorkflow as Contract;
use App\Modules\Payments\Contracts\Attempt;
use App\Modules\Payments\Contracts\PaymentLedger;
use App\Modules\Payments\Contracts\PaymentResult;
use App\SharedKernel\Clock;
use App\SharedKernel\Transactions;

final class PaymentWorkflow implements Contract
{
    public function __construct(private OrderStore $orders, private PaymentLedger $ledger, private InventoryReservations $reservations,
        private Transactions $transactions, private Clock $clock, private \App\Modules\Pricing\Contracts\CouponRedemptions $coupons) {}

    public function start(int $orderId): Attempt
    {
        $order = $this->orders->snapshot($orderId);

        return $this->ledger->start($order->payment($this->reservations->paymentHold($orderId)));
    }

    public function url(int $orderId, string $ip): string
    {
        return $this->ledger->url($this->orders->snapshot($orderId)->payment(), $ip);
    }

    public function notify(array $query): array
    {
        $candidate = $this->ledger->candidate($query);
        if ($candidate instanceof PaymentResult) {
            return $candidate->response();
        }
        try {
            return $this->transactions->run(function () use ($candidate) {
                // Preserve P4 lock order: order -> payment attempt -> reservation.
                $order = $this->orders->snapshot($candidate->orderId, true);
                $hold = null;
                $result = $this->ledger->settle($candidate, function () use ($order, &$hold) {
                    $hold = $this->reservations->paymentHold($order->id);

                    return $order->payment($hold);
                });
                if ($result->outcome === 'paid') {
                    $this->coupons->finish($order->id, true);
                    $this->orders->markPaid($order->id, $result->providerTransaction, $this->clock->now());
                    if ($hold !== null) {
                        $this->reservations->retainAfterPayment($order->id);
                    }
                }

                return $result->response();
            });
        } catch (\Throwable $error) {
            if ($this->transactions->isUniqueViolation($error)) {
                return (new PaymentResult('99'))->response();
            }
            throw $error;
        }
    }
}
