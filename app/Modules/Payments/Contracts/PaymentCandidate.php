<?php

namespace App\Modules\Payments\Contracts;

final readonly class PaymentCandidate
{
    public function __construct(public int $attemptId, public int $orderId, public array $query) {}
}
