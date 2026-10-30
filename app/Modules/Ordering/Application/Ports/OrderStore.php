<?php

namespace App\Modules\Ordering\Application\Ports;

use App\Modules\Inventory\Contracts\ReservationOrder;
use App\Modules\Ordering\Contracts\OrderSnapshot;
use App\Modules\Ordering\Contracts\TransitionReceipt;
use App\Modules\Pricing\Contracts\Quote;
use App\SharedKernel\ActorContext;

interface OrderStore
{
    public function enabled(): bool;

    public function snapshot(int $id, bool $lock = false): OrderSnapshot;

    public function create(array $payload, Quote $quote, int $warehouseId, ActorContext $actor): int;

    public function allocation(OrderSnapshot $order): ReservationOrder;

    public function consumeCart(ActorContext $actor, Quote $quote): void;

    public function previous(int $orderId, string $hash): ?TransitionReceipt;

    public function record(int $orderId, ActorContext $actor, string $from, string $to, string $operationHash, string $payloadHash,
        ?string $reason, ?string $requestId, \DateTimeImmutable $at): TransitionReceipt;

    public function setStatus(int $orderId, string $status, \DateTimeImmutable $at): void;

    public function discount(int $orderId, int $amount): void;

    public function markPaid(int $orderId, string $transaction, \DateTimeImmutable $at): void;
}
