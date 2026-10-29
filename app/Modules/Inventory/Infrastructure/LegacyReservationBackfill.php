<?php

namespace App\Modules\Inventory\Infrastructure;

use App\Exceptions\InventoryConflict;
use App\Exceptions\OrderTransitionConflict;
use App\Models\Inventory;
use App\Models\InventoryDiscrepancy;
use App\Models\InventoryLegacyHold;
use App\Models\InventoryReservation;
use App\Models\InventoryTransaction;
use App\Modules\Inventory\Domain\LegacyAllocationEvidence;
use Illuminate\Support\Facades\DB;

class LegacyReservationBackfill
{
    public function __construct(private LegacyAllocationEvidence $evidence, private StockStore $store) {}

    // Run only with writers stopped (deployment maintenance or isolated test schema).
    // No inventory quantity/reserved/available or historical order fields are changed.
    public function run(bool $apply, array $orders): array
    {
        if ($apply && ! app()->environment('testing') && ! app()->isDownForMaintenance()) {
            throw new InventoryConflict('maintenance_required', 'Backfill metadata cần dừng writers trước khi áp dụng.');
        }

        return DB::transaction(function () use ($apply, $orders) {
            $stocks = Inventory::orderBy('warehouse_id')->orderBy('product_variant_id')->get();
            $lookup = $stocks->keyBy(fn ($stock) => $stock->warehouse_id.':'.$stock->product_variant_id);
            $candidates = [];
            $rejected = [];
            $expected = [];
            foreach ($orders as $order) {
                if (InventoryReservation::where('order_id', $order->id)->exists() || in_array($order->status, ['completed', 'cancelled'], true)) {
                    continue;
                }
                try {
                    $items = $order->lines;
                    $movements = InventoryTransaction::where('reference_type', 'order')->where('reference_id', $order->id)->get();
                    $allocation = $this->evidence->allocation(array_map(fn ($item) => ['variant_id' => $item->variantId, 'quantity' => $item->quantity], $items), $movements->toArray(), (int) $order->warehouseId, $order->status);
                    foreach ($allocation as $variant => $quantity) {
                        $stock = $lookup->get($order->warehouseId.':'.$variant);
                        if (! $stock) {
                            throw new InventoryConflict('missing_inventory', 'Missing inventory');
                        }
                        $this->store->assertBalance($stock);
                    }
                    $candidates[$order->id] = ['order' => $order, 'items' => $items, 'allocation' => $allocation];
                } catch (OrderTransitionConflict|InventoryConflict $exception) {
                    $rejected[$order->id] = $exception->reasonCode;
                }
            }
            foreach ($candidates as $candidate) {
                if ($candidate['order']->status !== 'shipping') {
                    foreach ($candidate['allocation'] as $variant => $quantity) {
                        $stock = $lookup->get($candidate['order']->warehouseId.':'.$variant);
                        $expected[$stock->id] = ($expected[$stock->id] ?? 0) + $quantity;
                    }
                }
            }
            $existing = DB::table('inventory_reservation_items as ri')->join('inventory_reservations as r', 'r.id', '=', 'ri.reservation_id')
                ->where('ri.status', 'active')->where('r.status', 'active')->selectRaw('ri.warehouse_id, ri.product_variant_id, SUM(ri.quantity) AS quantity')
                ->groupBy('ri.warehouse_id', 'ri.product_variant_id')->get()->keyBy(fn ($row) => $row->warehouse_id.':'.$row->product_variant_id);
            foreach ($candidates as $id => $candidate) {
                if ($candidate['order']->status === 'shipping') {
                    continue;
                }
                foreach ($candidate['allocation'] as $variant => $quantity) {
                    $stock = $lookup->get($candidate['order']->warehouseId.':'.$variant);
                    $known = (int) ($existing->get($stock->warehouse_id.':'.$variant)?->quantity ?? 0);
                    if (($expected[$stock->id] ?? 0) + $known > $stock->reserved_quantity) {
                        $rejected[$id] = 'legacy_reservations_overallocated';
                    }
                }
            }
            $result = ['mode' => $apply ? 'apply_metadata_only' : 'dry_run', 'backfilled_orders' => [], 'quarantined_orders' => $rejected, 'legacy_hold_quantity' => 0, 'discrepancies' => []];
            foreach ($candidates as $id => $candidate) {
                if (isset($rejected[$id])) {
                    continue;
                }
                $order = $candidate['order'];
                $state = $order->status === 'shipping' ? 'committed' : 'active';
                $result['backfilled_orders'][] = $id;
                if ($apply) {
                    $reservation = InventoryReservation::create(['order_id' => $id, 'status' => $state, 'source' => 'legacy', 'expires_at' => null]);
                    foreach ($candidate['items'] as $item) {
                        $reservation->items()->create(['order_item_id' => $item->orderItemId, 'warehouse_id' => $order->warehouseId,
                            'product_variant_id' => $item->variantId, 'quantity' => $item->quantity, 'status' => $state]);
                    }
                }
            }
            if ($apply) {
                foreach ($rejected as $id => $reason) {
                    InventoryReservation::create(['order_id' => $id, 'status' => 'quarantined', 'source' => 'legacy', 'reason' => $reason]);
                }
            }
            foreach ($stocks as $stock) {
                $known = (int) DB::table('inventory_reservation_items as ri')->join('inventory_reservations as r', 'r.id', '=', 'ri.reservation_id')
                    ->where('ri.warehouse_id', $stock->warehouse_id)->where('ri.product_variant_id', $stock->product_variant_id)
                    ->where('ri.status', 'active')->where('r.status', 'active')->sum('ri.quantity');
                if (! $apply) {
                    foreach ($candidates as $id => $candidate) {
                        if (! isset($rejected[$id]) && $candidate['order']->status !== 'shipping' && (int) $candidate['order']->warehouseId === (int) $stock->warehouse_id) {
                            $known += $candidate['allocation'][$stock->product_variant_id] ?? 0;
                        }
                    }
                }
                try {
                    $this->store->assertBalance($stock);
                    if ($known > $stock->reserved_quantity) {
                        throw new InventoryConflict('legacy_reservations_overallocated', 'Overallocated stock');
                    }
                    $held = (int) $stock->reserved_quantity - $known;
                    $result['legacy_hold_quantity'] += $held;
                    if ($apply) {
                        InventoryLegacyHold::updateOrCreate(['inventory_id' => $stock->id], ['quantity' => $held]);
                    }
                } catch (InventoryConflict $exception) {
                    $result['discrepancies'][$stock->id] = $exception->reasonCode;
                    if ($apply && ! InventoryDiscrepancy::where('inventory_id', $stock->id)->where('status', 'open')->exists()) {
                        InventoryDiscrepancy::create(['inventory_id' => $stock->id, 'recorded_quantity' => $stock->quantity,
                            'reserved_quantity' => $stock->reserved_quantity, 'reason' => $exception->reasonCode]);
                    }
                }
            }

            return $result;
        });
    }
}
