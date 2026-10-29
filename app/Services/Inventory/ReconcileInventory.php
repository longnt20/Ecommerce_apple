<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\InventoryDiscrepancy;
use App\Models\InventoryLegacyHold;
use App\Models\InventoryReservationItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class ReconcileInventory
{
    // Snapshot reads only. A known quarantine is reported separately, never repaired here.
    public function report(): array
    {
        return DB::transaction(function () {
            $result = ['inventory_rows' => 0, 'protocol_movements' => 0, 'unexplained' => [], 'quarantined' => [], 'legacy_hold_quantity' => 0];
            $active = InventoryReservationItem::where('status', 'active')->get()->groupBy(fn ($line) => $line->warehouse_id.':'.$line->product_variant_id);
            $holds = InventoryLegacyHold::all()->keyBy('inventory_id');
            $quarantines = InventoryDiscrepancy::where('status', 'open')->pluck('inventory_id')->all();
            foreach (Inventory::orderBy('id')->get() as $stock) {
                $result['inventory_rows']++;
                $held = (int) ($holds->get($stock->id)?->quantity ?? 0);
                $result['legacy_hold_quantity'] += $held;
                $known = (int) ($active->get($stock->warehouse_id.':'.$stock->product_variant_id)?->sum('quantity') ?? 0);
                $issues = [];
                if ($stock->quantity < 0 || $stock->reserved_quantity < 0 || $stock->reserved_quantity > $stock->quantity
                    || (int) $stock->available_quantity !== (int) $stock->quantity - (int) $stock->reserved_quantity) {
                    $issues[] = 'stock_invariant';
                }
                if ((int) $stock->reserved_quantity !== $known + $held) {
                    $issues[] = 'reservation_accounting';
                }
                if ($issues !== []) {
                    $category = in_array($stock->id, $quarantines, true) ? 'quarantined' : 'unexplained';
                    $result[$category][] = ['inventory_id' => $stock->id, 'issues' => $issues];
                }
                $previous = null;
                foreach (InventoryTransaction::where('warehouse_id', $stock->warehouse_id)->where('product_variant_id', $stock->product_variant_id)
                    ->whereNotNull('operation_id')->orderBy('id')->get() as $movement) {
                    $result['protocol_movements']++;
                    $meta = $movement->meta_data ?? [];
                    $beforeReserved = $meta['reserved_before'] ?? null;
                    $afterReserved = $meta['reserved_after'] ?? null;
                    $delta = in_array($movement->type, ['reserve', 'release'], true) ? 0 : (int) $movement->quantity;
                    $reservedDelta = match ($movement->type) {
                        'reserve' => (int) $movement->quantity, 'release' => -(int) $movement->quantity,
                        'commit' => (int) $movement->quantity, default => 0,
                    };
                    if ($beforeReserved === null || $afterReserved === null || (int) $movement->after_quantity !== (int) $movement->before_quantity + $delta
                        || $afterReserved !== $beforeReserved + $reservedDelta
                        || ($previous && ((int) $previous->after_quantity !== (int) $movement->before_quantity || $previous->meta_data['reserved_after'] !== $beforeReserved))) {
                        $result['unexplained'][] = ['movement_id' => $movement->id, 'issues' => ['movement_chain']];
                    }
                    $previous = $movement;
                }
                if ($previous && ((int) $previous->after_quantity !== (int) $stock->quantity || ($previous->meta_data['reserved_after'] ?? null) !== (int) $stock->reserved_quantity)) {
                    $result['unexplained'][] = ['inventory_id' => $stock->id, 'issues' => ['movement_balance']];
                }
            }
            foreach (InventoryTransaction::where('type', 'transfer')->whereNotNull('operation_id')->get()->groupBy('operation_id') as $id => $pair) {
                if ($pair->count() !== 2 || (int) $pair->sum('quantity') !== 0 || $pair->pluck('product_variant_id')->unique()->count() !== 1
                    || $pair->pluck('warehouse_id')->unique()->count() !== 2) {
                    $result['unexplained'][] = ['operation_id' => $id, 'issues' => ['transfer_pair']];
                }
            }
            $result['open_discrepancies'] = count($quarantines);

            return $result;
        });
    }
}
