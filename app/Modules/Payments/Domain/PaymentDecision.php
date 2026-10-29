<?php

namespace App\Modules\Payments\Domain;

use App\Modules\Payments\Contracts\Attempt;
use App\Modules\Payments\Contracts\PaymentOrder;

final class PaymentDecision
{
    public function outcome(bool $success, Attempt $attempt, PaymentOrder $order, \DateTimeImmutable $now): string
    {
        if (! $success) {
            return 'failed';
        }

        return $order->status === 'cancelled' || $attempt->expiresAt < $now
            || ($order->holdStatus !== null && (! in_array($order->holdStatus, ['active', 'committed'], true)
                || ($order->holdExpiresAt !== null && $order->holdExpiresAt < $now)))
            ? 'reconciliation_required' : 'paid';
    }
}
