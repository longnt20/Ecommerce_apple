<?php

namespace App\Services\Ordering;

use App\Exceptions\OrderTransitionConflict;

// Conservative legacy evidence check; never creates or guesses an allocation.
class OrderReservationEvidence
{
    public function allocation(array $items, array $movements, int $warehouseId, string $status): array
    {
        $expected = [];
        foreach ($items as $item) {
            $variant = (int) ($item['variant_id'] ?? 0);
            $quantity = (int) $item['quantity'];
            if ($variant < 1 || $quantity < 1) {
                $this->reject('invalid_items');
            }
            $expected[$variant] = ($expected[$variant] ?? 0) + $quantity;
        }
        if ($expected === [] || $warehouseId < 1) {
            $this->reject('missing_allocation');
        }
        $totals = [];
        foreach ($movements as $movement) {
            $variant = (int) $movement['product_variant_id'];
            $type = $movement['type'];
            $quantity = (int) $movement['quantity'];
            if (! isset($expected[$variant]) || (int) $movement['warehouse_id'] !== $warehouseId
                || $movement['status'] !== 'completed' || ! in_array($type, ['reserve', 'commit', 'release'], true)) {
                $this->reject('ambiguous_allocation');
            }
            $before = (int) $movement['before_quantity'];
            $after = (int) $movement['after_quantity'];
            if (($type === 'commit' && ($quantity >= 0 || $after - $before !== $quantity))
                || ($type !== 'commit' && ($quantity <= 0 || $before !== $after))) {
                $this->reject('invalid_movement_evidence');
            }
            $totals[$variant][$type] = ($totals[$variant][$type] ?? 0) + abs($quantity);
        }
        foreach ($expected as $variant => $quantity) {
            $proof = $totals[$variant] ?? [];
            if (($proof['reserve'] ?? 0) !== $quantity || ($proof['release'] ?? 0) !== 0) {
                $this->reject('reservation_not_proven');
            }
            $committed = $proof['commit'] ?? 0;
            if ($status === 'shipping' ? $committed !== $quantity : $committed !== 0) {
                $this->reject('legacy_stock_state');
            }
        }
        ksort($expected, SORT_NUMERIC);

        return $expected;
    }

    public function assertBalance(array $inventory, int $quantity, bool $reserved): void
    {
        $onHand = (int) $inventory['quantity'];
        $held = (int) $inventory['reserved_quantity'];
        $available = (int) $inventory['available_quantity'];
        if ($onHand < 0 || $held < 0 || $held > $onHand || $available !== $onHand - $held
            || ($reserved && $held < $quantity)) {
            $this->reject('stock_balance_conflict');
        }
    }

    private function reject(string $code): never
    {
        throw new OrderTransitionConflict($code, 'Chưa đủ bằng chứng tồn kho/phân bổ đáng tin. Đơn cần đối soát trước khi chuyển trạng thái.');
    }
}
