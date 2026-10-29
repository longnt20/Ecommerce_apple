<?php

namespace App\Modules\Ordering\Contracts;

use App\Modules\Payments\Contracts\Attempt;

interface PaymentWorkflow
{
    public function start(int $orderId): Attempt;

    public function url(int $orderId, string $ip): string;

    public function notify(array $query): array;
}
