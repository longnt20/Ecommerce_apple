<?php

namespace Tests\Integration\P4;

use App\Exceptions\InventoryConflict;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\User;
use App\Services\Inventory\ExpireReservations;
use App\Services\Inventory\LegacyReservationBackfill;
use App\Services\Inventory\ReconcileInventory;
use App\Services\InventoryService;
use App\Services\Ordering\PlaceOrder;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\MySqlTestCase;

class InventoryProtocolTest extends MySqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        config(['ordering.transitions_enabled' => true, 'vnpay.enabled' => true,
            'vnpay.vnp_tmn_code' => 'P4MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-p4-secret',
            'vnpay.vnp_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', 'vnpay.vnp_return_url' => 'http://localhost/api/vnpay/return']);
    }

    private function place(array $changes = []): Order
    {
        return app(PlaceOrder::class)->place(array_replace($this->checkoutPayload(), $changes), 910001);
    }

    private function reconcile(): void
    {
        $this->assertSame([], app(ReconcileInventory::class)->report()['unexplained']);
    }

    public function test_warehouse_b_is_reserved_and_consumed_once_at_shipping(): void
    {
        DB::table('warehouses')->where('id', 1)->update(['is_active' => false]);
        $order = $this->place();
        $this->assertSame(2, (int) $order->warehouse_id);
        $this->assertDatabaseHas('inventory', ['id' => 910002, 'quantity' => 5, 'reserved_quantity' => 1, 'available_quantity' => 4]);
        app(OrderService::class)->changeStatus($order, 'confirmed', 910002, 'confirm');
        $this->assertDatabaseHas('inventory', ['id' => 910002, 'quantity' => 5]);
        app(OrderService::class)->changeStatus($order, 'shipping', 910002, 'ship');
        app(OrderService::class)->changeStatus($order, 'shipping', 910002, 'ship');
        $this->assertDatabaseHas('inventory', ['id' => 910002, 'quantity' => 4, 'reserved_quantity' => 0, 'available_quantity' => 4]);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 0]);
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'status' => 'committed']);
        $this->reconcile();
    }

    public function test_no_split_allocation_and_no_partial_order_when_sum_across_warehouses_is_enough(): void
    {
        $payload = $this->checkoutPayload();
        $payload['items'][0]['quantity'] = 6;
        try {
            app(PlaceOrder::class)->place($payload, 910001);
            $this->fail('No warehouse alone can supply six.');
        } catch (InventoryConflict $exception) {
            $this->assertSame('insufficient_stock', $exception->reasonCode);
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_operations', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->reconcile();
    }

    public function test_http_checkout_requires_key_replays_original_snapshot_and_rejects_key_reuse(): void
    {
        Sanctum::actingAs(User::findOrFail(910001));
        $payload = $this->checkoutPayload();
        $missing = $payload;
        unset($missing['idempotency_key']);
        $this->postJson('/api/checkout', $missing)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $first = $this->postJson('/api/checkout', $payload)->assertOk()->json('order_id');
        DB::table('product_variants')->where('id', 910001)->update(['price' => 30000000]);
        $this->postJson('/api/checkout', $payload)->assertOk()->assertJsonPath('order_id', $first)->assertJsonPath('final_amount', 25000000);
        $payload['address'] = 'Changed destination';
        $this->postJson('/api/checkout', $payload)->assertConflict()->assertJsonPath('code', 'operation_payload_conflict');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->reconcile();
    }

    public function test_cod_and_confirmed_orders_have_no_automatic_expiry(): void
    {
        $cod = $this->place();
        $online = $this->place(['payment_method' => 'vnpay']);
        $onlineReservation = InventoryReservation::where('order_id', $online->id)->firstOrFail();
        $this->assertEqualsWithDelta(900, now()->diffInSeconds($onlineReservation->expires_at, false), 2);
        app(OrderService::class)->changeStatus($online, 'confirmed', 910002, 'online-confirm');
        $this->travel(1)->days();
        $this->assertFalse(app(ExpireReservations::class)->expire($cod->id));
        $this->assertFalse(app(ExpireReservations::class)->expire($online->id));
        $this->assertNull($onlineReservation->fresh()->expires_at);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 2]);
        $this->reconcile();
    }

    public function test_expiry_releases_once_and_late_verified_payment_requires_reconciliation(): void
    {
        $order = $this->place(['payment_method' => 'vnpay']);
        $this->travel(16)->minutes();
        $this->assertTrue(app(ExpireReservations::class)->expire($order->id));
        $this->assertFalse(app(ExpireReservations::class)->expire($order->id));
        $attempt = DB::table('payment_attempts')->where('order_id', $order->id)->first();
        $query = ['vnp_Amount' => '2500000000', 'vnp_TmnCode' => 'P4MERCHANT', 'vnp_TxnRef' => $attempt->reference,
            'vnp_TransactionNo' => '444444', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00'];
        ksort($query);
        $query['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($query, '', '&', PHP_QUERY_RFC1738), 'synthetic-p4-secret');
        $this->assertSame('00', app(PaymentService::class)->notify($query)['RspCode']);
        $this->assertDatabaseHas('payment_attempts', ['id' => $attempt->id, 'status' => 'reconciliation_required']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 0, 'available_quantity' => 5]);
        $this->assertDatabaseCount('order_transitions', 1);
        $this->reconcile();
    }

    public function test_transfer_pairs_conserve_stock_replay_once_and_create_missing_destination(): void
    {
        DB::table('inventory')->where('id', 910002)->delete();
        $service = app(InventoryService::class);
        $first = $service->transfer(1, 2, 910001, 2, null, 910002, 'transfer-test');
        $this->assertSame($first, $service->transfer(1, 2, 910001, 2, null, 910002, 'transfer-test'));
        $this->assertDatabaseHas('inventory', ['warehouse_id' => 1, 'quantity' => 3]);
        $this->assertDatabaseHas('inventory', ['warehouse_id' => 2, 'quantity' => 2]);
        $this->assertSame(0, (int) DB::table('inventory_transactions')->sum('quantity'));
        $this->assertDatabaseCount('inventory_transactions', 2);
        $this->reconcile();
    }

    public function test_stocktake_below_reserved_quarantines_without_overwriting_stock(): void
    {
        $order = $this->place();
        $result = app(InventoryService::class)->adjust(1, 910001, 0, 'Physical count', 910002, 'count-test');
        $this->assertNotNull($result['discrepancy_id']);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 1, 'available_quantity' => 4]);
        $this->assertSame($result, app(InventoryService::class)->adjust(1, 910001, 0, 'Physical count', 910002, 'count-test'));
        $this->assertDatabaseCount('inventory_discrepancies', 1);
        $other = $this->place();
        $this->assertSame(2, (int) $other->warehouse_id);
        // Cancellation may safely release a known hold even on a quarantined/inactive warehouse.
        DB::table('warehouses')->where('id', 1)->update(['is_active' => false]);
        app(OrderService::class)->changeStatus($order, 'cancelled', 910002, 'cancel-quarantine');
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 0]);
        $this->reconcile();
    }

    public function test_export_cannot_take_reserved_stock_and_import_records_exact_cost(): void
    {
        $this->place();
        $service = app(InventoryService::class);
        try {
            $service->export(1, 910001, 5, null, null, null, 910002, 'export-held');
            $this->fail('Reserved stock is unavailable for export.');
        } catch (InventoryConflict $exception) {
            $this->assertSame('insufficient_stock', $exception->reasonCode);
        }
        $service->import(1, 910001, 3, '123.45', null, 910002, 'cost-test');
        $this->assertDatabaseHas('inventory_transactions', ['type' => 'import', 'unit_cost' => '123.45', 'total_cost' => '370.35', 'created_by' => 910002]);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 8, 'reserved_quantity' => 1, 'available_quantity' => 7]);
        $this->reconcile();
    }

    public function test_ambiguous_legacy_order_is_quarantined_and_unknown_reserved_stock_preserved(): void
    {
        $order = $this->place();
        DB::table('inventory_transactions')->where('reference_id', $order->id)->delete();
        DB::table('inventory_reservation_items')->delete();
        DB::table('inventory_reservations')->delete();
        $before = DB::table('inventory')->orderBy('id')->get()->toJson();
        $report = app(LegacyReservationBackfill::class)->run(false);
        $this->assertArrayHasKey($order->id, $report['quarantined_orders']);
        $this->assertDatabaseCount('inventory_reservations', 0);
        $report = app(LegacyReservationBackfill::class)->run(true);
        $this->assertSame(1, $report['legacy_hold_quantity']);
        $this->assertSame($before, DB::table('inventory')->orderBy('id')->get()->toJson());
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'status' => 'quarantined']);
        $this->assertDatabaseHas('inventory_legacy_holds', ['inventory_id' => 910001, 'quantity' => 1]);
        $this->assertSame([], app(LegacyReservationBackfill::class)->run(true)['backfilled_orders']);
        $this->reconcile();
    }

    public function test_legacy_overallocation_quarantines_all_candidates_without_arbitrary_assignment(): void
    {
        $first = $this->place();
        $second = $this->place();
        DB::table('inventory_reservation_items')->delete();
        DB::table('inventory_reservations')->delete();
        DB::table('inventory')->where('id', 910001)->update(['reserved_quantity' => 1, 'available_quantity' => 4]);
        $before = DB::table('inventory')->orderBy('id')->get()->toJson();
        $report = app(LegacyReservationBackfill::class)->run(true);
        $this->assertSame([], $report['backfilled_orders']);
        $this->assertSame([$first->id => 'legacy_reservations_overallocated', $second->id => 'legacy_reservations_overallocated'], $report['quarantined_orders']);
        $this->assertDatabaseCount('inventory_reservation_items', 0);
        $this->assertDatabaseHas('inventory_legacy_holds', ['inventory_id' => 910001, 'quantity' => 1]);
        $this->assertSame($before, DB::table('inventory')->orderBy('id')->get()->toJson());
    }

    public function test_paid_online_order_is_never_released_by_expiry(): void
    {
        $order = $this->place(['payment_method' => 'vnpay']);
        $attempt = DB::table('payment_attempts')->where('order_id', $order->id)->first();
        $query = ['vnp_Amount' => '2500000000', 'vnp_TmnCode' => 'P4MERCHANT', 'vnp_TxnRef' => $attempt->reference,
            'vnp_TransactionNo' => '444445', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00'];
        ksort($query);
        $query['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($query, '', '&', PHP_QUERY_RFC1738), 'synthetic-p4-secret');
        $this->assertSame('00', app(PaymentService::class)->notify($query)['RspCode']);
        $this->travel(16)->minutes();
        $this->assertFalse(app(ExpireReservations::class)->expire($order->id));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid', 'status' => 'pending']);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 1]);
        $this->reconcile();
    }

    public function test_admin_batch_replay_payload_conflict_and_draft_never_writes_stock(): void
    {
        $this->actingAs(User::findOrFail(910002), 'web');
        $payload = ['warehouse_id' => 1, 'operation_key' => (string) \Illuminate\Support\Str::uuid(),
            'items' => [['product_variant_id' => 910001, 'quantity' => 2, 'unit_cost' => '100.00']]];
        $this->postJson('/admin/inventory/import', $payload + ['action' => 'draft'])->assertUnprocessable();
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->post('/admin/inventory/import', $payload)->assertRedirect(route('admin.inventory.transactions'));
        $this->post('/admin/inventory/import', $payload)->assertRedirect(route('admin.inventory.transactions'));
        $payload['items'][0]['quantity'] = 3;
        $this->postJson('/admin/inventory/import', $payload)->assertConflict()->assertJsonPath('code', 'operation_payload_conflict');
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 7]);
        $this->reconcile();
    }

    public function test_reconciliation_detects_out_of_protocol_drift_and_raw_model_writer_is_blocked(): void
    {
        $this->place();
        DB::table('inventory')->where('id', 910001)->update(['quantity' => 8, 'available_quantity' => 7]);
        $this->assertNotEmpty(app(ReconcileInventory::class)->report()['unexplained']);
        $this->expectException(\LogicException::class);
        \App\Models\Inventory::findOrFail(910001)->updateQuantity(99);
    }
}
