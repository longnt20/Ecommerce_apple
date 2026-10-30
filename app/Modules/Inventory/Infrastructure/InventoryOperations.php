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

        return $this->operations->run($kind, $actor->id, $key, $payload, function ($operationId) use ($kind, $actor, $payload, $work) {
            $result = $work($operationId);
            if (str_starts_with($kind, 'admin-batch-')) {
                $lines = $result['lines'];
                $open = count(array_filter($lines, fn ($line) => ($line['discrepancy_id'] ?? null) !== null)) > 0;
                $id = \Illuminate\Support\Facades\DB::table('inventory_documents')->insertGetId([
                    'operation_id' => $operationId, 'kind' => substr($kind, 12), 'actor_id' => $actor->id,
                    'status' => $open ? 'needs_reconciliation' : 'completed', 'payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'created_at' => now(),
                ]);
                foreach (array_values($lines) as $index => $line) {
                    \Illuminate\Support\Facades\DB::table('inventory_document_lines')->insert([
                        'document_id' => $id, 'line' => $index + 1, 'result' => json_encode($line, JSON_THROW_ON_ERROR),
                    ]);
                }
                $result['document_id'] = $id;
            }

            return $result;
        });
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
