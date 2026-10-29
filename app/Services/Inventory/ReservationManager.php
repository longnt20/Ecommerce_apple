<?php

namespace App\Services\Inventory;

use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderTransition;
use App\Modules\Inventory\Contracts\StockRequirement;
use App\Modules\Ordering\Application\Ports\OrderStore;
use App\SharedKernel\ActorContext;

/** @deprecated Eloquent compatibility only. Runtime use cases use InventoryReservations contract. */
class ReservationManager
{
    public function __construct(private \App\Modules\Inventory\Infrastructure\ReservationManager $implementation, private OrderStore $orders) {}

    private function allocation(Order $order): \App\Modules\Inventory\Contracts\ReservationOrder
    {
        return $this->orders->allocation($this->orders->snapshot($order->id));
    }

    public function allocate(array $lines): int
    {
        return $this->implementation->allocate(array_map(fn ($line) => $line instanceof StockRequirement ? $line : new StockRequirement($line['variant']->id, $line['quantity']), $lines));
    }

    public function reserve(Order $order, string $operationId, int $actorId): InventoryReservation
    {
        $this->implementation->reserve($this->allocation($order), $operationId, new ActorContext($actorId));

        return InventoryReservation::where('order_id', $order->id)->firstOrFail();
    }

    public function locked(Order $order): InventoryReservation
    {
        return $this->implementation->locked($this->allocation($order));
    }

    public function validate(Order $order, string $target): InventoryReservation
    {
        return $this->implementation->validate($this->allocation($order), $target);
    }

    public function transition(Order $order, OrderTransition $transition, string $target): void
    {
        $this->implementation->transition($this->allocation($order), $transition->id, $target, new ActorContext($transition->actor_id));
    }

    public function consume(Order $order, InventoryReservation $reservation, int $transitionId, string $state, ?int $actorId): void
    {
        $this->implementation->consume($this->allocation($order), $reservation, $transitionId, $state, $actorId);
    }
}
