<?php

namespace App\Modules\Inventory\Contracts;

use App\SharedKernel\ActorContext;

interface InventoryReservations
{
    /** @param list<StockRequirement> $lines */
    public function allocate(array $lines): int;

    public function reserve(ReservationOrder $order, string $operationId, ActorContext $actor): void;

    public function transition(ReservationOrder $order, int $transitionId, string $target, ActorContext $actor): void;

    public function expire(ReservationOrder $order, int $transitionId): void;

    public function paymentHold(int $orderId): ?Hold;

    public function retainAfterPayment(int $orderId): void;

    public function due(int $limit = 100): array;
}
