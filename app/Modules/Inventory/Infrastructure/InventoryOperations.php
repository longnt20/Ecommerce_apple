<?php

namespace App\Modules\Inventory\Infrastructure;

use App\Modules\Inventory\Contracts\InventoryOperations as Contract;
use App\SharedKernel\ActorContext;

final class InventoryOperations implements Contract
{
    public function __construct(private StockWriter $writer, private IdempotentOperation $operations) {}

    public function assertEnabled(): void
    {
        $this->writer->assertEnabled();
    }

    public function execute(string $kind, ActorContext $actor, string $key, array $payload, callable $work): array
    {
        $this->assertEnabled();

        return $this->operations->run($kind, $actor->id, $key, $payload, $work);
    }

    public function admin(string $kind, ActorContext $actor, string $key, array $payload): array
    {
        if ($actor->id === null) {
            throw new \App\Exceptions\InventoryConflict('forbidden_actor', 'Tài khoản không có quyền thao tác kho.');
        }

        return $this->writer->admin($kind, $actor->id, $key, $payload);
    }

    public function lockWarehouses(array $ids): void
    {
        $this->writer->store->lockWarehouses($ids);
    }
}
