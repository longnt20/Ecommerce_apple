<?php

namespace Tests\Integration\P3;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\DB;
use Tests\Support\DatabaseSandbox;
use Tests\Support\P3Fixture;
use Tests\TestCase;

class OrderAtomicityTest extends TestCase
{
    private ?DatabaseSandbox $sandbox = null;

    private array $connection;

    private Order $order;

    protected function setUp(): void
    {
        if (getenv('P3_SCHEMA_TESTS') !== '1') {
            $this->markTestSkipped('P3_SCHEMA_TESTS=1: disposable MySQL sandbox for concurrency and storage failure tests.');
        }
        parent::setUp();
        $this->sandbox = new DatabaseSandbox;
        $name = 'apple_project_p1_fresh_'.bin2hex(random_bytes(6));
        $target = $this->sandbox->create($name);
        DatabaseSandbox::loadSnapshot($target, file_get_contents(database_path('schema/mysql-schema.sql')));
        $this->connection = $this->sandbox->connectionConfig($name);
        config(['database.connections.p3_atomic' => $this->connection, 'ordering.transitions_enabled' => true,
            'vnpay.enabled' => true, 'vnpay.vnp_tmn_code' => 'P3MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-p3-secret',
            'vnpay.vnp_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', 'vnpay.vnp_return_url' => 'http://localhost/api/vnpay/return']);
        DB::setDefaultConnection('p3_atomic');
        foreach (['2026_10_28_000001_reconcile_application_schema.php', '2026_10_29_000001_create_verified_payments.php', '2026_10_29_000002_create_order_transitions.php', '2026_10_29_000003_create_inventory_protocol.php', '2026_10_29_000004_create_inventory_legacy_holds.php', '2026_10_30_000001_create_commerce_lifecycle.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->order = P3Fixture::seed();
        app(\App\Services\Inventory\LegacyReservationBackfill::class)->run(true);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->sandbox !== null) {
                DB::purge('p3_atomic');
                $this->sandbox->dropCreated();
            }
        } finally {
            parent::tearDown();
        }
    }

    private function runWorkers(array $commands): array
    {
        $workers = [];
        try {
            foreach ($commands as $command) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/order-transition-worker.php')],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                stream_set_timeout($pipes[1], 15);
                stream_set_timeout($pipes[2], 15);
                $workers[] = [$process, $pipes];
                fwrite($pipes[0], json_encode(array_merge(['connection' => $this->connection, 'order_id' => $this->order->id], $command))."\n");
                fflush($pipes[0]);
            }
            foreach ($workers as [, $pipes]) {
                $this->assertSame('READY', trim(fgets($pipes[1])));
            }
            foreach ($workers as [, $pipes]) {
                fwrite($pipes[0], "GO\n");
                fflush($pipes[0]);
                fclose($pipes[0]);
            }
            $results = [];
            foreach ($workers as [$process, $pipes]) {
                $results[] = json_decode(trim(stream_get_contents($pipes[1])), true);
                $this->assertSame('', stream_get_contents($pipes[2]));
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process));
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

    /** @dataProvider replayedTransitions */
    public function test_two_processes_replay_one_operation_without_duplicate_effects(string $target): void
    {
        if ($target === 'shipping') {
            app(OrderService::class)->changeStatus($this->order, 'confirmed', 910002, 'before-ship');
        }
        $command = ['action' => $target, 'operation' => 'same-'.$target];
        $results = $this->runWorkers([$command, $command]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'result'));
        $this->assertSame($results[0]['transition'], $results[1]['transition']);
        $this->assertSame(1, DB::table('order_transitions')->where('to_status', $target)->count());
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => $target === 'shipping' ? 4 : 5,
            'reserved_quantity' => $target === 'confirmed' ? 1 : 0, 'available_quantity' => $target === 'cancelled' ? 5 : 4]);
        $this->assertDatabaseCount('inventory_transactions', $target === 'confirmed' ? 1 : 2);
    }

    public static function replayedTransitions(): array
    {
        return [['confirmed'], ['cancelled'], ['shipping']];
    }

    public function test_confirm_cancel_race_has_only_one_legal_transition(): void
    {
        $results = $this->runWorkers([['action' => 'confirmed', 'operation' => 'race-confirm'], ['action' => 'cancelled', 'operation' => 'race-cancel']]);
        $outcomes = array_column($results, 'result');
        sort($outcomes);
        $this->assertSame(['conflict', 'ok'], $outcomes);
        $this->assertDatabaseCount('order_transitions', 1);
        $status = $this->order->fresh()->status;
        $this->assertContains($status, ['confirmed', 'cancelled']);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5,
            'reserved_quantity' => $status === 'cancelled' ? 0 : 1, 'available_quantity' => $status === 'cancelled' ? 5 : 4]);
    }

    public function test_payment_cancel_race_keeps_money_and_release_consistent(): void
    {
        $this->order->update(['payment_method' => 'VNPAY']);
        $attempt = app(PaymentService::class)->start($this->order);
        $query = ['vnp_Amount' => '2500000000', 'vnp_TmnCode' => 'P3MERCHANT', 'vnp_TxnRef' => $attempt->reference,
            'vnp_TransactionNo' => '14226112', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00'];
        ksort($query);
        $query['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($query, '', '&', PHP_QUERY_RFC1738), 'synthetic-p3-secret');
        $results = $this->runWorkers([['action' => 'payment', 'query' => $query], ['action' => 'cancelled', 'operation' => 'cancel-payment-race']]);
        $this->assertSame('00', $results[0]['ack']);
        $this->assertSame('ok', $results[1]['result']);
        $this->assertSame('cancelled', $this->order->fresh()->status);
        $paymentStatus = $this->order->fresh()->payment_status;
        $this->assertSame($paymentStatus === 'paid' ? 'paid' : 'reconciliation_required', $attempt->fresh()->status);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 0, 'available_quantity' => 5]);
        $this->assertDatabaseCount('order_transitions', 1);
        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_movement_failure_rolls_back_history_stock_status_and_retry_succeeds(): void
    {
        app(OrderService::class)->changeStatus($this->order, 'confirmed', 910002, 'confirm-before-fault');
        DB::unprepared("CREATE TRIGGER p3_fail_movement BEFORE INSERT ON inventory_transactions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic movement failure'");
        try {
            app(OrderService::class)->changeStatus($this->order, 'shipping', 910002, 'retry-shipping');
            $this->fail('Injected movement failure must roll back.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'status' => 'confirmed']);
            $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 1, 'available_quantity' => 4]);
            $this->assertDatabaseCount('order_transitions', 1);
            $this->assertDatabaseCount('inventory_transactions', 1);
        }
        DB::unprepared('DROP TRIGGER p3_fail_movement');
        app(OrderService::class)->changeStatus($this->order, 'shipping', 910002, 'retry-shipping');
        $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'status' => 'shipping']);
        $this->assertDatabaseCount('order_transitions', 2);
        $this->assertDatabaseCount('inventory_transactions', 2);
    }

    public function test_status_write_failure_rolls_back_already_written_movement(): void
    {
        app(OrderService::class)->changeStatus($this->order, 'confirmed', 910002, 'confirm-before-status-fault');
        DB::unprepared("CREATE TRIGGER p3_fail_status BEFORE UPDATE ON orders FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic order failure'");
        try {
            app(OrderService::class)->changeStatus($this->order, 'shipping', 910002, 'ship-status-fault');
            $this->fail('Order write failure must undo movement and history.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 1]);
            $this->assertDatabaseCount('inventory_transactions', 1);
            $this->assertDatabaseCount('order_transitions', 1);
        }
    }

    public function test_second_sku_failure_rolls_back_first_sku_too(): void
    {
        P3Fixture::addSecondSku();
        app(OrderService::class)->changeStatus($this->order, 'confirmed', 910002, 'multi-confirm');
        DB::unprepared("CREATE TRIGGER p3_fail_second_sku BEFORE INSERT ON inventory_transactions FOR EACH ROW BEGIN IF NEW.product_variant_id = 910002 AND NEW.type = 'commit' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic second SKU failure'; END IF; END");
        try {
            app(OrderService::class)->changeStatus($this->order, 'shipping', 910002, 'multi-ship');
            $this->fail('Second SKU failure must undo first SKU too.');
        } catch (\Illuminate\Database\QueryException) {
            foreach ([910001, 910003] as $id) {
                $this->assertDatabaseHas('inventory', ['id' => $id, 'quantity' => 5, 'reserved_quantity' => 1, 'available_quantity' => 4]);
            }
            $this->assertSame(0, DB::table('inventory_transactions')->where('type', 'commit')->count());
            $this->assertDatabaseCount('order_transitions', 1);
        }
        DB::unprepared('DROP TRIGGER p3_fail_second_sku');
        app(OrderService::class)->changeStatus($this->order, 'shipping', 910002, 'multi-ship');
        $this->assertSame(2, DB::table('inventory_transactions')->where('type', 'commit')->count());
    }

    public function test_two_orders_ship_same_sku_without_movement_code_collisions(): void
    {
        $other = Order::create(['user_id' => 910001, 'code' => 'P3-OTHER-ORDER', 'total_price' => 25000000, 'final_amount' => 25000000]);
        DB::table('order_items')->insert(['order_id' => $other->id, 'product_id' => 910001, 'variant_id' => 910001, 'product_name' => 'Synthetic', 'quantity' => 1, 'price' => 25000000, 'total' => 25000000]);
        DB::table('inventory_transactions')->insert(['transaction_code' => 'P3-OTHER-RESERVE', 'warehouse_id' => 1, 'product_variant_id' => 910001,
            'type' => 'reserve', 'quantity' => 1, 'before_quantity' => 5, 'after_quantity' => 5, 'reference_type' => 'order', 'reference_id' => $other->id]);
        DB::table('inventory')->where('id', 910001)->update(['reserved_quantity' => 2, 'available_quantity' => 3]);
        app(\App\Services\Inventory\LegacyReservationBackfill::class)->run(true);
        app(OrderService::class)->changeStatus($this->order, 'confirmed', 910002, 'confirm-order-one');
        app(OrderService::class)->changeStatus($other, 'confirmed', 910002, 'confirm-order-two');
        $results = $this->runWorkers([['action' => 'shipping', 'operation' => 'ship-order-one'], ['action' => 'shipping', 'operation' => 'ship-order-two', 'order_id' => $other->id]]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'result'));
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 3, 'reserved_quantity' => 0, 'available_quantity' => 3]);
        $this->assertSame(2, DB::table('inventory_transactions')->where('type', 'commit')->distinct()->count('transaction_code'));
    }
}
