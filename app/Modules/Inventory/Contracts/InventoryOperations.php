<?php

namespace App\Modules\Inventory\Contracts;

use App\SharedKernel\ActorContext;

interface InventoryOperations
{
    public function assertEnabled(): void;

    public function execute(string $kind, ActorContext $actor, string $key, array $payload, callable $work): array;

    public function admin(string $kind, ActorContext $actor, string $key, array $payload): array;

    public function lockWarehouses(array $ids): void;
}
