<?php

namespace Tests\Integration\P4;

use App\Models\InventoryReservation;
use App\Services\Inventory\ReconcileInventory;
use App\Services\Ordering\PlaceOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DatabaseSandbox;
use Tests\Support\P3Fixture;
use Tests\TestCase;

class InventoryConcurrencyTest extends TestCase
{
    private ?DatabaseSandbox $sandbox = null;

    private array $connection;

    protected function setUp(): void
    {
        if (getenv('P4_SCHEMA_TESTS') !== '1') {
            $this->markTestSkipped('P4_SCHEMA_TESTS=1 provisions disposable MySQL schemas for independent-process tests.');
        }
        parent::setUp();
        $this->sandbox = new DatabaseSandbox;
        $name = 'apple_project_p1_fresh_'.bin2hex(random_bytes(6));
        $target = $this->sandbox->create($name);
        DatabaseSandbox::loadSnapshot($target, file_get_contents(database_path('schema/mysql-schema.sql')));
        $this->connection = $this->sandbox->connectionConfig($name);
        config(['database.connections.p4_atomic' => $this->connection, 'ordering.transitions_enabled' => true,
            'vnpay.enabled' => true, 'vnpay.vnp_tmn_code' => 'P4MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-p4-secret',
            'vnpay.vnp_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', 'vnpay.vnp_return_url' => 'http://localhost/api/vnpay/return']);
        DB::setDefaultConnection('p4_atomic');
        foreach (['2026_10_28_000001_reconcile_application_schema.php', '2026_10_29_000001_create_verified_payments.php', '2026_10_29_000002_create_order_transitions.php', '2026_10_29_000003_create_inventory_protocol.php', '2026_10_29_000004_create_inventory_legacy_holds.php', '2026_10_30_000001_create_commerce_lifecycle.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        P3Fixture::seed();
        DB::table('inventory_transactions')->delete();
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
        DB::table('inventory')->update(['quantity' => 1, 'reserved_quantity' => 0, 'available_quantity' => 1]);
        DB::table('warehouses')->where('id', 2)->update(['is_active' => false]);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->sandbox !== null) {
                DB::purge('p4_atomic');
                $this->sandbox->dropCreated();
            }
        } finally {
            parent::tearDown();
        }
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['idempotency_key' => (string) Str::uuid(), 'payment_method' => 'cod', 'mode' => 'buy-now',
            'fullname' => 'Synthetic', 'phone' => '0000000000', 'address' => 'Synthetic', 'ward' => 'Test', 'district' => 'Test', 'province' => 'Test',
            'items' => [['product_id' => 910001, 'variant_id' => 910001, 'quantity' => 1]]], $changes);
    }

    private function workers(array $commands): array
    {
        $workers = [];
        try {
            foreach ($commands as $command) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/inventory-worker.php')],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                stream_set_timeout($pipes[1], 30);
                stream_set_timeout($pipes[2], 30);
                $workers[] = [$process, $pipes];
                fwrite($pipes[0], json_encode(array_merge(['connection' => $this->connection], $command))."\n");
                fflush($pipes[0]);
            }
            foreach ($workers as [, $pipes]) {
                $this->assertSame('READY', trim(fgets($pipes[1])));
            }
            foreach ($workers as [, $pipes]) {
                fwrite($pipes[0], "GO\n");
                fflush($pipes[0]);
            }
            $results = [];
            foreach ($workers as $index => [$process, $pipes]) {
                if ($commands[$index]['action'] === 'kill-import') {
                    $this->assertSame('MUTATED', trim(fgets($pipes[1])));
                    proc_terminate($process);
                    $results[] = ['result' => 'killed'];
                } else {
                    $results[] = json_decode(trim(fgets($pipes[1])), true, flags: JSON_THROW_ON_ERROR);
                }
                $this->assertSame('', stream_get_contents($pipes[2]));
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }
                $code = proc_close($process);
                if ($commands[$index]['action'] !== 'kill-import') {
                    $this->assertSame(0, $code);
                }
            }

            return $results;
        } finally {
            foreach ($workers as [$process, $pipes]) {
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
            }
        }
    }

    private function reconcile(): void
    {
        $this->assertSame([], app(ReconcileInventory::class)->report()['unexplained']);
    }

    public function test_two_buyers_of_last_unit_create_exactly_one_order_and_reservation(): void
    {
        $results = $this->workers([['action' => 'checkout', 'payload' => $this->payload()], ['action' => 'checkout', 'payload' => $this->payload(), 'actor' => 910002]]);
        $outcomes = array_column($results, 'result');
        sort($outcomes);
        $this->assertSame(['conflict', 'ok'], $outcomes);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('inventory_reservations', 1);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 1, 'reserved_quantity' => 1, 'available_quantity' => 0]);
        $this->reconcile();
    }

    public function test_same_checkout_key_concurrently_replays_one_order(): void
    {
        $command = ['action' => 'checkout', 'payload' => $this->payload()];
        $results = $this->workers([$command, $command]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'result'));
        $this->assertSame($results[0]['data']['order_id'], $results[1]['data']['order_id']);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->reconcile();
    }

    public function test_reversed_multiple_skus_are_canonical_and_do_not_deadlock(): void
    {
        DB::table('product_variants')->insert(['id' => 910002, 'product_id' => 910001, 'sku' => 'P4-SECOND', 'price' => 25000000]);
        DB::table('inventory')->insert(['id' => 910003, 'warehouse_id' => 1, 'product_variant_id' => 910002, 'quantity' => 1, 'reserved_quantity' => 0, 'available_quantity' => 1]);
        $payload = $this->payload();
        $payload['items'][] = ['product_id' => 910001, 'variant_id' => 910002, 'quantity' => 1];
        $reverse = $payload;
        $reverse['items'] = array_reverse($payload['items']);
        $results = $this->workers([['action' => 'checkout', 'payload' => $payload], ['action' => 'checkout', 'payload' => $reverse]]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'result'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('inventory_reservation_items', 2);
        $this->reconcile();
    }

    public function test_import_competes_with_checkout_without_lost_update(): void
    {
        $results = $this->workers([['action' => 'checkout', 'payload' => $this->payload()],
            ['action' => 'import', 'key' => 'race-import', 'payload' => ['warehouse_id' => 1, 'variant_id' => 910001, 'quantity' => 2]]]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'result'));
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 3, 'reserved_quantity' => 1, 'available_quantity' => 2]);
        $this->reconcile();
    }

    /** @dataProvider competingExports */
    public function test_export_or_transfer_cannot_spend_reserved_last_unit(string $kind): void
    {
        DB::table('warehouses')->where('id', 2)->update(['is_active' => true]);
        DB::table('inventory')->where('id', 910002)->delete();
        $results = $this->workers([['action' => 'checkout', 'payload' => $this->payload()],
            ['action' => $kind, 'key' => 'race-'.$kind, 'payload' => ['warehouse_id' => 1, 'to_warehouse_id' => 2, 'variant_id' => 910001, 'quantity' => 1]]]);
        $outcomes = array_column($results, 'result');
        sort($outcomes);
        // If transfer wins, checkout can allocate the newly supplied second warehouse.
        $this->assertContains('ok', $outcomes);
        if ($kind === 'export') {
            $this->assertSame(['conflict', 'ok'], $outcomes);
        }
        $this->assertSame($kind === 'transfer' ? 1 : ($results[1]['result'] === 'ok' ? 0 : 1), (int) DB::table('inventory')->sum('quantity'));
        $this->assertLessThanOrEqual(1, DB::table('orders')->count());
        $this->reconcile();
    }

    public static function competingExports(): array
    {
        return [['export'], ['transfer']];
    }

    public function test_two_transfers_to_missing_destination_create_one_row_and_conserve_total(): void
    {
        DB::table('warehouses')->where('id', 2)->update(['is_active' => true]);
        DB::table('inventory')->where('id', 910002)->delete();
        DB::table('inventory')->where('id', 910001)->update(['quantity' => 2, 'available_quantity' => 2]);
        $payload = ['warehouse_id' => 1, 'to_warehouse_id' => 2, 'variant_id' => 910001, 'quantity' => 1];
        $results = $this->workers([['action' => 'transfer', 'key' => 'transfer-one', 'payload' => $payload], ['action' => 'transfer', 'key' => 'transfer-two', 'payload' => $payload]]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'result'));
        $this->assertDatabaseCount('inventory', 2);
        $this->assertDatabaseHas('inventory', ['warehouse_id' => 2, 'quantity' => 2]);
        $this->assertSame(2, (int) DB::table('inventory')->sum('quantity'));
        $this->reconcile();
    }

    public function test_second_checkout_item_failure_rolls_back_whole_order_and_retry_succeeds(): void
    {
        DB::table('product_variants')->insert(['id' => 910002, 'product_id' => 910001, 'sku' => 'P4-SECOND', 'price' => 25000000]);
        DB::table('inventory')->insert(['id' => 910003, 'warehouse_id' => 1, 'product_variant_id' => 910002, 'quantity' => 1, 'reserved_quantity' => 0, 'available_quantity' => 1]);
        $payload = $this->payload();
        $payload['items'][] = ['product_id' => 910001, 'variant_id' => 910002, 'quantity' => 1];
        DB::unprepared("CREATE TRIGGER p4_fail_second BEFORE INSERT ON inventory_transactions FOR EACH ROW BEGIN IF NEW.product_variant_id = 910002 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic second SKU failure'; END IF; END");
        try {
            app(PlaceOrder::class)->place($payload, 910001);
            $this->fail('Synthetic storage failure must propagate.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('inventory_reservations', 0);
            $this->assertDatabaseCount('inventory_operations', 0);
            $this->assertDatabaseCount('inventory_transactions', 0);
            $this->assertSame(0, (int) DB::table('inventory')->sum('reserved_quantity'));
        }
        DB::unprepared('DROP TRIGGER p4_fail_second');
        app(PlaceOrder::class)->place($payload, 910001);
        $this->assertDatabaseCount('orders', 1);
        $this->reconcile();
    }

    public function test_transfer_destination_movement_failure_rolls_back_both_balances(): void
    {
        DB::table('warehouses')->where('id', 2)->update(['is_active' => true]);
        DB::unprepared("CREATE TRIGGER p4_fail_destination BEFORE INSERT ON inventory_transactions FOR EACH ROW BEGIN IF NEW.warehouse_id = 2 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic destination failure'; END IF; END");
        try {
            app(\App\Services\InventoryService::class)->transfer(1, 2, 910001, 1, null, 910002, 'transfer-rollback');
            $this->fail('Destination failure must roll back source too.');
        } catch (\Illuminate\Database\QueryException) {
            foreach ([1, 2] as $warehouse) {
                $this->assertDatabaseHas('inventory', ['warehouse_id' => $warehouse, 'quantity' => 1]);
            }
            $this->assertDatabaseCount('inventory_operations', 0);
            $this->assertDatabaseCount('inventory_transactions', 0);
        }
        $this->reconcile();
    }

    public function test_killed_process_leaves_no_partial_import_or_operation(): void
    {
        $this->workers([['action' => 'kill-import', 'key' => 'kill-test', 'payload' => ['warehouse_id' => 1, 'variant_id' => 910001, 'quantity' => 9]]]);
        // FOR UPDATE waits for server-side disconnect rollback before assertions.
        DB::transaction(fn () => DB::table('warehouses')->where('id', 1)->lockForUpdate()->first());
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 1, 'reserved_quantity' => 0]);
        $this->assertDatabaseCount('inventory_operations', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->reconcile();
    }

    /** @dataProvider expiryCompetitors */
    public function test_expiry_serializes_with_payment_or_confirmation(string $competitor): void
    {
        $order = app(PlaceOrder::class)->place($this->payload(['payment_method' => 'vnpay']), 910001);
        InventoryReservation::where('order_id', $order->id)->update(['expires_at' => now()->subSecond()]);
        $attempt = DB::table('payment_attempts')->where('order_id', $order->id)->first();
        $query = ['vnp_Amount' => '2500000000', 'vnp_TmnCode' => 'P4MERCHANT', 'vnp_TxnRef' => $attempt->reference,
            'vnp_TransactionNo' => '444444', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00'];
        ksort($query);
        $query['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($query, '', '&', PHP_QUERY_RFC1738), 'synthetic-p4-secret');
        $results = $this->workers([['action' => 'expiry', 'order_id' => $order->id], ['action' => $competitor, 'order_id' => $order->id, 'query' => $query, 'key' => 'race-confirm']]);
        $this->assertSame('ok', $results[0]['result']);
        $this->assertTrue($results[0]['data']['expired']);
        $this->assertSame($competitor === 'payment' ? 'ok' : 'conflict', $results[1]['result']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 1, 'reserved_quantity' => 0, 'available_quantity' => 1]);
        if ($competitor === 'payment') {
            $this->assertDatabaseHas('payment_attempts', ['order_id' => $order->id, 'status' => 'reconciliation_required']);
        }
        $this->reconcile();
    }

    /** @dataProvider expiryCompetitors */
    public function test_boundary_race_keeps_payment_or_confirmation_if_they_win_order_lock(string $competitor): void
    {
        $order = app(PlaceOrder::class)->place($this->payload(['payment_method' => 'vnpay']), 910001);
        $deadline = now()->addSeconds(2);
        InventoryReservation::where('order_id', $order->id)->update(['expires_at' => $deadline]);
        $attempt = DB::table('payment_attempts')->where('order_id', $order->id)->first();
        $query = ['vnp_Amount' => '2500000000', 'vnp_TmnCode' => 'P4MERCHANT', 'vnp_TxnRef' => $attempt->reference,
            'vnp_TransactionNo' => '444446', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00'];
        ksort($query);
        $query['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($query, '', '&', PHP_QUERY_RFC1738), 'synthetic-p4-secret');
        $results = $this->workers([['action' => 'expiry', 'order_id' => $order->id, 'now' => $deadline->copy()->addSecond()->toDateTimeString()],
            ['action' => $competitor, 'order_id' => $order->id, 'query' => $query, 'key' => 'boundary-confirm', 'now' => $deadline->copy()->subSecond()->toDateTimeString()]]);
        $expired = $results[0]['data']['expired'];
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 1, 'reserved_quantity' => $expired ? 0 : 1]);
        if ($expired) {
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'unpaid']);
        } else {
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => $competitor === 'confirmed' ? 'confirmed' : 'pending',
                'payment_status' => $competitor === 'payment' ? 'paid' : 'unpaid']);
            $this->assertNull(InventoryReservation::where('order_id', $order->id)->firstOrFail()->expires_at);
        }
        $this->reconcile();
    }

    public static function expiryCompetitors(): array
    {
        return [['payment'], ['confirmed']];
    }
}
