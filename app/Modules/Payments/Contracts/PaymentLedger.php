<?php

namespace App\Modules\Payments\Contracts;

interface PaymentLedger
{
    public function assertAvailable(): void;

    public function start(PaymentOrder $order): Attempt;

    public function url(PaymentOrder $order, string $ip): string;

    public function candidate(array $query): PaymentCandidate|PaymentResult;

    /** @param callable(): PaymentOrder $orderSnapshot Supplies the owned order/hold snapshot after attempt locking. */
    public function settle(PaymentCandidate $candidate, callable $orderSnapshot): PaymentResult;

    public function paidAttempt(int $orderId): bool;

    public function browserOrder(array $query): ?int;
}
