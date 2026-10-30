<?php

namespace App\Modules\Payments\Contracts;

use App\SharedKernel\ActorContext;

interface ManualPayments
{
    public function record(PaymentOrder $order, ActorContext $actor, string $key, array $input): array;

    public function history(int $orderId): array;
}
