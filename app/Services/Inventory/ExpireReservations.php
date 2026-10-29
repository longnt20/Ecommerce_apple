<?php

namespace App\Services\Inventory;

use App\Modules\Ordering\Contracts\ReservationExpiry;

/** @deprecated Expiry orchestration belongs to Ordering; Inventory owns only reservation effects. */
class ExpireReservations
{
    public function __construct(private ReservationExpiry $expiry) {}

    public function expire(int $orderId): bool
    {
        return $this->expiry->expire($orderId);
    }

    public function due(int $limit = 100): array
    {
        return $this->expiry->due($limit);
    }
}
