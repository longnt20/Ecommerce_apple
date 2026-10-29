<?php

namespace App\Modules\Payments\Contracts;

interface PaymentGateway
{
    public function assertConfigured(): void;

    public function verified(array $query): bool;

    public function paymentUrl(Attempt $attempt, string $ip): string;
}
