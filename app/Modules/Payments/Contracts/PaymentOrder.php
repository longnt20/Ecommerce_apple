<?php

namespace App\Modules\Payments\Contracts;

final readonly class PaymentOrder
{
    public function __construct(public int $id, public string $amount, public string $method, public string $status,
        public string $paymentStatus, public ?string $holdStatus = null, public ?\DateTimeImmutable $holdExpiresAt = null) {}
}
