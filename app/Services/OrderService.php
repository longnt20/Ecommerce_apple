<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTransition;
use App\Modules\Ordering\Contracts\OrderTransitions;
use App\SharedKernel\ActorContext;
use Illuminate\Support\Facades\DB;

/** @deprecated Ordering compatibility for callers that still need Eloquent results. */
class OrderService
{
    public function __construct(private OrderTransitions $transitions) {}

    public function changeStatus(Order $order, string $newStatus, int $actorId, ?string $operationKey = null, ?string $reason = null, ?string $requestId = null): OrderTransition
    {
        if ($order->getConnection()->getName() !== DB::connection()->getName()) {
            throw new \LogicException('Order and stock must use the same database connection.');
        }
        $receipt = $this->transitions->change($order->id, $newStatus, new ActorContext($actorId), $operationKey, $reason, $requestId);
        $order->refresh();

        return OrderTransition::findOrFail($receipt->id);
    }
}
