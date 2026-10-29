<?php

namespace App\Modules\Ordering\Contracts;

interface ReservationExpiry
{
    public function expire(int $orderId): bool;

    public function due(int $limit = 100): array;
}
