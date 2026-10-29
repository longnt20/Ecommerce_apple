<?php

namespace App\Services\Ordering;

use App\Models\Order;
use App\Modules\Ordering\Contracts\Checkout;
use App\SharedKernel\ActorContext;

/** @deprecated Ordering-owned compatibility adapter. Use Checkout contract; retirement tracked in P5_STATUS. */
class PlaceOrder
{
    public function __construct(private Checkout $checkout) {}

    public function place(array $input, int $actorId): Order
    {
        $result = $this->checkout->place($input, new ActorContext($actorId));

        return Order::where('user_id', $actorId)->findOrFail($result->id);
    }
}
