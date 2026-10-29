<?php

namespace App\Modules\Ordering\Contracts;

use App\SharedKernel\ActorContext;

interface OrderTransitions
{
    public function change(int $orderId, string $target, ActorContext $actor, ?string $key = null, ?string $reason = null, ?string $requestId = null): TransitionReceipt;
}
