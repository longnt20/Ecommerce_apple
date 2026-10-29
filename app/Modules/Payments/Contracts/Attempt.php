<?php

namespace App\Modules\Payments\Contracts;

final readonly class Attempt
{
    public function __construct(public int $id, public int $orderId, public string $reference, public string $merchant, public int $amount,
        public string $currency, public string $status, public \DateTimeImmutable $createdAt, public \DateTimeImmutable $expiresAt) {}
}
