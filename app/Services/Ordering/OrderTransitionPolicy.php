<?php

namespace App\Services\Ordering;

use App\Exceptions\OrderTransitionConflict;

class OrderTransitionPolicy
{
    public function next(string $status): array
    {
        return match ($status) {
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['shipping'],
            'shipping' => ['completed'],
            default => [],
        };
    }

    public function assertAllowed(string $from, string $to): void
    {
        if (! in_array($to, $this->next($from), true)) {
            throw new OrderTransitionConflict('invalid_transition', 'Trạng thái đơn đã thay đổi hoặc không cho phép thao tác này.');
        }
    }
}
