<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Modules\Ordering\Contracts\PaymentWorkflow;

/** @deprecated Composition adapter only; financial writes belong to Payments, order writes to Ordering. */
class PaymentService
{
    public function __construct(private PaymentWorkflow $workflow) {}

    public function start(Order $order): PaymentAttempt
    {
        return PaymentAttempt::findOrFail($this->workflow->start($order->id)->id);
    }

    public function url(Order $order, string $ip): string
    {
        return $this->workflow->url($order->id, $ip);
    }

    public function notify(array $query): array
    {
        return $this->workflow->notify($query);
    }
}
