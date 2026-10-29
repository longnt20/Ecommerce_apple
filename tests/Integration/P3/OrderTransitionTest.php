<?php

namespace Tests\Integration\P3;

use App\Exceptions\OrderTransitionConflict;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderTransition;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Support\MySqlTestCase;
use Tests\Support\P3Fixture;

class OrderTransitionTest extends MySqlTestCase
{
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ordering.transitions_enabled' => true]);
        $this->order = P3Fixture::seed();
    }

    private function change(string $to, ?string $key = null, ?string $reason = null): OrderTransition
    {
        app(\App\Services\Inventory\LegacyReservationBackfill::class)->run(true);

        return app(OrderService::class)->changeStatus($this->order, $to, 910002, $key, $reason);
    }

    public function test_confirm_keeps_stock_until_shipping_and_records_each_transition_once(): void
    {
        $confirmed = $this->change('confirmed', 'confirm-one', 'Synthetic review');
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 1, 'available_quantity' => 4]);
        $this->assertSame($confirmed->id, $this->change('confirmed', 'confirm-one', 'Synthetic review')->id);
        $shipped = $this->change('shipping', 'ship-one');
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 4, 'reserved_quantity' => 0, 'available_quantity' => 4]);
        $this->assertDatabaseHas('inventory_transactions', ['reference_id' => $this->order->id, 'type' => 'commit', 'quantity' => -1, 'created_by' => 910002]);
        $movement = DB::table('inventory_transactions')->where('type', 'commit')->first();
        $this->assertSame($shipped->id, json_decode($movement->meta_data, true)['order_transition_id']);
        $this->assertStringStartsWith('INV-OP-', $movement->transaction_code);
        $this->change('completed', 'complete-one');
        $this->assertSame('completed', $this->order->status);
        $this->assertNotNull($this->order->delivered_at);
        $this->assertDatabaseCount('order_transitions', 3);
        $this->assertSame($confirmed->id, $this->change('confirmed', 'confirm-one', 'Synthetic review')->id);
        $this->assertDatabaseCount('inventory_transactions', 2);
        Mail::assertNothingOutgoing();
    }

    public function test_cancel_releases_only_once_and_stamps_actor_reason_and_time(): void
    {
        $first = $this->change('cancelled', 'cancel-one', 'Synthetic cancellation');
        $again = $this->change('cancelled', 'cancel-one', 'Synthetic cancellation');
        $this->assertSame($first->id, $again->id);
        $this->assertNotNull($this->order->cancelled_at);
        $this->assertDatabaseHas('order_transitions', ['id' => $first->id, 'actor_id' => 910002, 'from_status' => 'pending', 'to_status' => 'cancelled', 'reason' => 'Synthetic cancellation']);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 0, 'available_quantity' => 5]);
        $this->assertDatabaseCount('order_transitions', 1);
        $this->assertSame(1, DB::table('inventory_transactions')->where('type', 'release')->count());
    }

    public function test_stale_model_cannot_cancel_after_another_invocation_confirms(): void
    {
        $stale = Order::findOrFail($this->order->id);
        $this->change('confirmed');
        try {
            app(OrderService::class)->changeStatus($stale, 'cancelled', 910002);
            $this->fail('Stale pending snapshot must not authorize cancel.');
        } catch (OrderTransitionConflict $exception) {
            $this->assertSame('invalid_transition', $exception->reasonCode);
        }
        $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'status' => 'confirmed']);
        $this->assertDatabaseCount('order_transitions', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
    }

    public function test_same_operation_with_another_payload_is_a_conflict(): void
    {
        $this->change('confirmed', 'same-operation');
        $this->expectException(OrderTransitionConflict::class);
        try {
            $this->change('shipping', 'same-operation');
        } finally {
            $this->assertDatabaseCount('order_transitions', 1);
        }
    }

    public function test_direct_service_call_cannot_bypass_transition_policy_or_actor_access(): void
    {
        foreach ([['completed', 910002, 'invalid_transition'], ['confirmed', 910001, 'forbidden_actor']] as [$to, $actor, $expected]) {
            try {
                app(OrderService::class)->changeStatus($this->order, $to, $actor);
                $this->fail('Must reject direct invocation.');
            } catch (OrderTransitionConflict $exception) {
                $this->assertSame($expected, $exception->reasonCode);
            }
        }
        $this->assertDatabaseCount('order_transitions', 0);
    }

    /** @dataProvider unsafeEvidence */
    public function test_ambiguous_legacy_and_bad_balances_are_quarantined(string $case): void
    {
        match ($case) {
            'missing' => DB::table('inventory_transactions')->delete(),
            'wrong-warehouse' => DB::table('inventory_transactions')->update(['warehouse_id' => 2]),
            'duplicate-reserve' => DB::table('inventory_transactions')->update(['quantity' => 2]),
            'negative-reserve' => DB::table('inventory_transactions')->update(['quantity' => -1]),
            'pending-movement' => DB::table('inventory_transactions')->update(['status' => 'pending']),
            'unknown-operation' => DB::table('inventory_transactions')->update(['type' => 'export']),
            'empty-items' => DB::table('order_items')->delete(),
            'short-reserved' => DB::table('inventory')->where('id', 910001)->update(['reserved_quantity' => 0, 'available_quantity' => 5]),
            'bad-balance' => DB::table('inventory')->where('id', 910001)->update(['available_quantity' => 5]),
            'missing-inventory' => DB::table('inventory')->where('id', 910001)->delete(),
        };
        try {
            $this->change('confirmed');
            $this->fail('Unproven legacy order must require reconciliation.');
        } catch (OrderTransitionConflict) {
            $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'status' => 'pending']);
            $this->assertDatabaseCount('order_transitions', 0);
        }
    }

    public static function unsafeEvidence(): array
    {
        return array_map(fn ($case) => [$case], ['missing', 'wrong-warehouse', 'duplicate-reserve', 'negative-reserve', 'pending-movement', 'unknown-operation', 'empty-items', 'short-reserved', 'bad-balance', 'missing-inventory']);
    }

    public function test_old_confirmed_order_with_commit_cannot_ship_stock_twice(): void
    {
        DB::table('orders')->where('id', $this->order->id)->update(['status' => 'confirmed']);
        DB::table('inventory_transactions')->insert(['transaction_code' => 'P3-OLD-COMMIT', 'warehouse_id' => 1, 'product_variant_id' => 910001,
            'type' => 'commit', 'quantity' => -1, 'before_quantity' => 5, 'after_quantity' => 4, 'reference_type' => 'order', 'reference_id' => $this->order->id]);
        $this->expectException(OrderTransitionConflict::class);
        try {
            $this->change('shipping');
        } finally {
            $this->assertDatabaseCount('order_transitions', 0);
            $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5]);
        }
    }

    public function test_completed_stock_operation_cannot_be_reused_directly(): void
    {
        $this->change('confirmed');
        $operation = $this->change('shipping');
        $this->expectException(OrderTransitionConflict::class);
        try {
            app(InventoryService::class)->commitOrder(1, 910001, 1, $this->order->id, $operation);
        } finally {
            $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 4, 'reserved_quantity' => 0]);
        }
    }

    public function test_http_uses_same_policy_preserves_success_redirect_and_returns_json_conflict(): void
    {
        app(\App\Services\Inventory\LegacyReservationBackfill::class)->run(true);
        $this->actingAs(User::findOrFail(910002));
        $path = '/admin/orders/'.$this->order->id;
        $this->patch($path, ['status' => 'confirmed', 'operation_key' => 'http-confirm'])->assertRedirect();
        $this->patch($path, ['status' => 'confirmed', 'operation_key' => 'http-confirm'])->assertRedirect();
        $this->patchJson($path, ['status' => 'cancelled', 'operation_key' => 'http-cancel'])
            ->assertStatus(409)->assertJsonPath('code', 'invalid_transition');
        $this->get($path)->assertOk()->assertSee('Lịch sử chuyển trạng thái')->assertSee('operation_key', false);
        $this->assertDatabaseCount('order_transitions', 1);
    }

    public function test_rollout_disabled_rejects_without_stock_or_history_changes(): void
    {
        config(['ordering.transitions_enabled' => false]);
        $this->expectException(OrderTransitionConflict::class);
        try {
            $this->change('confirmed');
        } finally {
            $this->assertDatabaseCount('order_transitions', 0);
            $this->assertDatabaseCount('inventory_transactions', 1);
        }
    }

    public function test_multi_sku_shipping_and_legacy_code_sequence_are_compatible(): void
    {
        P3Fixture::addSecondSku();
        $prefix = 'INV-'.now()->format('Ymd');
        DB::table('inventory_transactions')->insert(['transaction_code' => $prefix.'-0042', 'warehouse_id' => 1, 'product_variant_id' => 910001,
            'type' => 'import', 'quantity' => 1, 'before_quantity' => 4, 'after_quantity' => 5, 'created_at' => now()->subMinute()]);
        $this->change('confirmed');
        $this->change('shipping');
        foreach ([910001, 910003] as $id) {
            $this->assertDatabaseHas('inventory', ['id' => $id, 'quantity' => 4, 'reserved_quantity' => 0, 'available_quantity' => 4]);
        }
        $this->assertSame(2, DB::table('inventory_transactions')->where('type', 'commit')->count());
        $this->assertSame($prefix.'-0043', InventoryTransaction::generateTransactionCode());
    }

    public function test_different_operation_cannot_reenter_a_terminal_state(): void
    {
        $this->change('cancelled', 'cancel-original');
        $this->expectException(OrderTransitionConflict::class);
        try {
            $this->change('cancelled', 'cancel-another');
        } finally {
            $this->assertDatabaseCount('order_transitions', 1);
            $this->assertDatabaseHas('inventory', ['id' => 910001, 'reserved_quantity' => 0]);
        }
    }
}
