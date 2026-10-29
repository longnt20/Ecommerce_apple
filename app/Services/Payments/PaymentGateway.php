<?php

namespace App\Services\Payments;

use App\Models\PaymentAttempt;

interface PaymentGateway
{
    public function assertConfigured(): void;

    public function paymentUrl(PaymentAttempt $attempt, string $ip): string;

    public function verified(array $query): bool;
}
