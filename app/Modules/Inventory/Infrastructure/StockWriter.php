<?php

namespace App\Modules\Inventory\Infrastructure;

use App\Exceptions\InventoryConflict;
use App\Models\Inventory;
use App\Models\InventoryDiscrepancy;
use App\Models\InventoryOperation;
use App\Models\InventoryTransaction;
use App\Modules\Identity\Contracts\AccountAccess;
use App\SharedKernel\ActorContext;
use App\SharedKernel\Clock;
use Illuminate\Support\Str;

class StockWriter
{
    public function __construct(public StockStore $store, private IdempotentOperation $operations, private AccountAccess $accounts, private Clock $clock) {}

    public function assertEnabled(): void
    {
        if (! config('inventory.enabled')) {
            throw new InventoryConflict('inventory_unavailable', 'Thao tác kho đang tạm dừng để chuyển sang protocol an toàn.');
        }
    }

    public function admin(string $kind, int $actorId, string $key, array $payload): array
    {
        $this->assertEnabled();
        if (! $this->accounts->administrator(new ActorContext($actorId))) {
            throw new InventoryConflict('forbidden_actor', 'Tài khoản không có quyền thao tác kho.');
        }
        $warehouse = (int) $payload['warehouse_id'];
        $variant = (int) $payload['variant_id'];
        $quantity = $this->store->positive($payload['quantity'], $kind === 'adjust');
        $unitCost = $payload['unit_cost'] ?? null;
        if ($unitCost !== null && ! preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $unitCost)) {
            throw new InventoryConflict('invalid_cost', 'Giá vốn cần là số không âm, tối đa hai chữ số thập phân.');
        }

        return $this->operations->run($kind, $actorId, $key, $payload, function ($operationId) use ($kind, $actorId, $payload, $warehouse, $variant, $quantity, $unitCost) {
            $ids = [$warehouse];
            if ($kind === 'transfer') {
                $destination = (int) $payload['to_warehouse_id'];
                if ($destination === $warehouse) {
                    throw new InventoryConflict('invalid_transfer', 'Kho nguồn và đích phải khác nhau.');
                }
                $ids[] = $destination;
            }
            $this->store->lockWarehouses($ids);
            $stock = $this->store->lock($warehouse, $variant, $kind === 'import');
            $meta = ['notes' => $payload['notes'] ?? null, 'reason' => $payload['reason'] ?? null,
                'reference_type' => $payload['reference_type'] ?? null, 'reference_id' => $payload['reference_id'] ?? null,
                'reference_code' => $payload['reference_code'] ?? null];
            if ($kind === 'adjust' && $quantity < $stock->reserved_quantity) {
                $discrepancy = InventoryDiscrepancy::create([
                    'inventory_id' => $stock->id, 'operation_id' => $operationId,
                    'recorded_quantity' => $stock->quantity, 'reserved_quantity' => $stock->reserved_quantity,
                    'counted_quantity' => $quantity, 'reason' => 'stocktake_below_reserved',
                    'details' => ['actor_id' => $actorId, 'reason' => $payload['reason'] ?? null],
                ]);

                return ['movement_ids' => [], 'discrepancy_id' => $discrepancy->id];
            }
            $movements = [];
            if ($kind === 'import') {
                $stock->last_received_at = $this->clock->now();
                $movements[] = $this->change($stock, $quantity, 0, 'import', $quantity, $operationId, $actorId,
                    array_merge($meta, $this->cost($unitCost, $quantity)))->id;
            } elseif ($kind === 'export') {
                $this->available($stock, $quantity);
                $stock->last_shipped_at = $this->clock->now();
                $movements[] = $this->change($stock, -$quantity, 0, 'export', -$quantity, $operationId, $actorId, $meta)->id;
            } elseif ($kind === 'transfer') {
                $this->available($stock, $quantity);
                $target = $this->store->lock((int) $payload['to_warehouse_id'], $variant, true);
                $transfer = array_merge($meta, ['from_warehouse_id' => $warehouse, 'to_warehouse_id' => $target->warehouse_id]);
                $movements[] = $this->change($stock, -$quantity, 0, 'transfer', -$quantity, $operationId, $actorId, $transfer)->id;
                $movements[] = $this->change($target, $quantity, 0, 'transfer', $quantity, $operationId, $actorId, $transfer)->id;
            } elseif ($kind === 'adjust') {
                $difference = $quantity - $stock->quantity;
                $stock->last_counted_at = $this->clock->now();
                $movements[] = $this->change($stock, $difference, 0, 'adjust', $difference, $operationId, $actorId, $meta)->id;
            } else {
                throw new InventoryConflict('invalid_operation', 'Loại thao tác kho không hợp lệ.');
            }

            return ['movement_ids' => $movements, 'discrepancy_id' => null];
        });
    }

    public function available(Inventory $stock, int $quantity): void
    {
        if ($stock->available_quantity < $quantity) {
            throw new InventoryConflict('insufficient_stock', 'Không đủ hàng khả dụng trong kho.');
        }
    }

    // Called only after the shared warehouse/inventory lock protocol in the enclosing operation.
    public function change(Inventory $stock, int $onHandDelta, int $reservedDelta, string $type, int $movementQuantity,
        string $operationId, ?int $actorId, array $attributes = []): InventoryTransaction
    {
        $operation = InventoryOperation::whereKey($operationId)->lockForUpdate()->first();
        if (! $operation || $operation->result !== null || ($operation->actor_id === null ? null : (int) $operation->actor_id) !== $actorId) {
            throw new InventoryConflict('stock_operation_conflict', 'Operation tồn kho không hợp lệ hoặc đã hoàn tất.');
        }
        $this->store->assertBalance($stock);
        $before = (int) $stock->quantity;
        $heldBefore = (int) $stock->reserved_quantity;
        $stock->quantity += $onHandDelta;
        $stock->reserved_quantity += $reservedDelta;
        $stock->available_quantity = $stock->quantity - $stock->reserved_quantity;
        $this->store->assertBalance($stock);
        $stock->save();
        $line = InventoryTransaction::where('operation_id', $operationId)->count() + 1;

        return InventoryTransaction::create(array_merge($attributes, [
            'transaction_code' => 'INV-OP-'.Str::ulid(), 'operation_id' => $operationId, 'operation_line' => $line,
            'warehouse_id' => $stock->warehouse_id, 'product_variant_id' => $stock->product_variant_id,
            'type' => $type, 'quantity' => $movementQuantity, 'before_quantity' => $before, 'after_quantity' => $stock->quantity,
            'created_by' => $actorId, 'status' => 'completed',
            'meta_data' => array_merge($attributes['meta_data'] ?? [], ['reserved_before' => $heldBefore, 'reserved_after' => $stock->reserved_quantity]),
        ]));
    }

    private function cost(mixed $cost, int $quantity): array
    {
        if ($cost === null) {
            return [];
        }
        [$whole, $fraction] = array_pad(explode('.', (string) $cost, 2), 2, '');
        $cents = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        if ($cents > intdiv(999999999999999, $quantity)) {
            throw new InventoryConflict('cost_overflow', 'Tổng giá vốn vượt giới hạn lưu trữ.');
        }
        $format = fn ($value) => intdiv($value, 100).'.'.str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);

        return ['unit_cost' => $format($cents), 'total_cost' => $format($cents * $quantity)];
    }
}
