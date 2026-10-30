<?php

namespace App\Modules\Pricing\Contracts;

interface CouponRedemptions
{
    public function reserve(int $orderId, int $userId, string $code, int $subtotal): int;

    public function finish(int $orderId, bool $redeem): void;
}
