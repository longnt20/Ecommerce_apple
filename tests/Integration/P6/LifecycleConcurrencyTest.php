<?php

namespace Tests\Integration\P6;

use App\Services\Inventory\ReconcileInventory;
use App\Services\Ordering\PlaceOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DatabaseSandbox;
use Tests\Support\P3Fixture;
use Tests\TestCase;

class LifecycleConcurrencyTest extends TestCase
{
    private ?DatabaseSandbox $sandbox = null;

    private array $connection;

    protected function setUp(): void
    {
        if (getenv('P6_SCHEMA_TESTS') !== '1') {
            $this->markTestSkipped('P6_SCHEMA_TESTS=1 provisions disposable MySQL schemas for independent-process tests.');
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

    private function coupon(): int
    {
        $id = DB::table('coupons')->insertGetId(['user_id' => 910002, 'code' => 'P6RACE', 'name' => 'Race', 'discount_type' => 'fixed', 'discount_value' => 100000, 'status' => 1, 'max_usage' => 1]);
        foreach ([910001, 910002] as $user) {
            DB::table('coupon_uses')->insert(['coupon_id' => $id, 'user_id' => $user, 'status' => 'unused']);
        }

        return $id;
    }

    private function shipped(): int
    {
        $order = app(PlaceOrder::class)->place($this->payload(), 910001);
        foreach (['confirmed', 'shipping', 'completed'] as $target) {
            app(\App\Modules\Ordering\Contracts\OrderTransitions::class)->change($order->id, $target, new \App\SharedKernel\ActorContext(910002), $target);
        }

        return $order->id;
    }

    public function test_last_coupon_use_is_reserved_by_exactly_one_concurrent_buyer(): void
    {
        DB::table('inventory')->where('id', 910001)->update(['quantity' => 5, 'available_quantity' => 5]);
        $this->coupon();
        $results = $this->workers([['action' => 'checkout', 'payload' => $this->payload(['coupon_code' => 'P6RACE'])],
            ['action' => 'checkout', 'payload' => $this->payload(['coupon_code' => 'P6RACE']), 'actor' => 910002]]);
        $states = array_column($results, 'result');
        sort($states);
        $this->assertSame(['conflict', 'ok'], $states);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('coupon_redemptions', 1);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'reserved_quantity' => 1]);
        $this->reconcile();
    }

    public function test_concurrent_collection_replay_and_partial_refund_cap_are_serialized(): void
    {
        $id = $this->shipped();
        $collect = ['action' => 'manual-money', 'order_id' => $id, 'key' => 'collect-race',
            'payload' => ['action' => 'collect_cod', 'amount' => 25000000, 'reference' => 'COD-RACE']];
        $result = $this->workers([$collect, $collect]);
        $this->assertSame(['ok', 'ok'], array_column($result, 'result'));
        $this->assertDatabaseCount('payment_adjustments', 1);
        $refund = ['action' => 'manual-money', 'order_id' => $id, 'key' => 'refund-a',
            'payload' => ['action' => 'request_refund', 'amount' => 20000000, 'reason' => 'Verified request']];
        $result = $this->workers([$refund, array_replace($refund, ['key' => 'refund-b'])]);
        $states = array_column($result, 'result');
        sort($states);
        $this->assertSame(['conflict', 'ok'], $states);
        $this->assertSame(20000000, (int) DB::table('payment_adjustments')->where('kind', 'refund')->sum('amount'));
        $this->assertDatabaseCount('inventory_return_receipts', 0);
    }

    public function test_concurrent_returns_cannot_receive_the_same_shipped_unit_twice(): void
    {
        $id = $this->shipped();
        $item = DB::table('order_items')->where('order_id', $id)->value('id');
        $command = ['action' => 'returned-goods', 'order_id' => $id, 'key' => 'receive-a',
            'payload' => ['order_item_id' => $item, 'quantity' => 1, 'disposition' => 'sellable', 'reference' => 'RETURN-A', 'reason' => 'Passed inspection']];
        $other = $command;
        $other['key'] = 'receive-b';
        $other['payload']['reference'] = 'RETURN-B';
        $result = $this->workers([$command, $other]);
        $states = array_column($result, 'result');
        sort($states);
        $this->assertSame(['conflict', 'ok'], $states);
        $this->assertDatabaseCount('inventory_return_receipts', 1);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 1, 'available_quantity' => 1]);
        $this->reconcile();
    }

    public function test_assignment_retry_in_two_processes_preserves_one_assignment(): void
    {
        $id = DB::table('coupons')->insertGetId(['user_id' => 910002, 'code' => 'P6ASSIGN', 'name' => 'Assign', 'discount_type' => 'fixed', 'discount_value' => 100000]);
        $command = ['action' => 'assign-coupon', 'coupon_id' => $id];
        $result = $this->workers([$command, $command]);
        $this->assertSame(['ok', 'ok'], array_column($result, 'result'));
        $this->assertSame($result[0]['data'], $result[1]['data']);
        $this->assertDatabaseCount('coupon_uses', 1);
        $this->assertDatabaseCount('coupon_assignment_deliveries', 1);
    }

    public function test_coupon_persistence_failure_rolls_back_order_stock_and_redemption(): void
    {
        $this->coupon();
        $before = DB::table('inventory')->orderBy('id')->get()->toJson();
        DB::unprepared("CREATE TRIGGER p6_fail_discount BEFORE UPDATE ON orders FOR EACH ROW BEGIN IF NEW.discount_amount > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic discount failure'; END IF; END");
        try {
            app(PlaceOrder::class)->place($this->payload(['coupon_code' => 'P6RACE']), 910001);
            $this->fail('Discount failure must roll back the whole checkout.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('coupon_redemptions', 0);
            $this->assertDatabaseCount('inventory_operations', 0);
            $this->assertSame($before, DB::table('inventory')->orderBy('id')->get()->toJson());
        }
    }

    public function test_migration_reentry_and_down_preserve_new_business_history(): void
    {
        $migration = require database_path('migrations/2026_10_30_000001_create_commerce_lifecycle.php');
        $before = DatabaseSandbox::schemaSnapshotFrom(DB::connection()->getPdo());
        $migration->up();
        $this->assertSame($before, DatabaseSandbox::schemaSnapshotFrom(DB::connection()->getPdo()));
        $id = $this->shipped();
        app(\App\Modules\Ordering\Contracts\AfterSales::class)->money($id, new \App\SharedKernel\ActorContext(910002), 'collect-down',
            ['action' => 'collect_cod', 'amount' => 25000000, 'reference' => 'COD-DOWN']);
        try {
            $migration->down();
            $this->fail('Down must retain real money history.');
        } catch (\RuntimeException) {
            $this->assertDatabaseCount('payment_adjustments', 1);
        }
    }

    public function test_partial_assignment_batch_failure_recovers_without_duplicate_first_user(): void
    {
        DB::table('users')->insert(['id' => 910003, 'name' => 'Synthetic recipient', 'email' => 'p6-partial@example.test',
            'password' => 'synthetic-only', 'code' => 'P6-PARTIAL', 'status' => 'active', 'role' => 'user']);
        $coupon = \App\Models\Coupon::create(['user_id' => 910002, 'code' => 'P6BATCH', 'name' => 'Batch', 'discount_type' => 'fixed', 'discount_value' => 100000, 'status' => 1]);
        $job = new \App\Jobs\AssignCouponJob($coupon, [910001, 910003]);
        DB::unprepared("CREATE TRIGGER p6_fail_recipient BEFORE INSERT ON coupon_uses FOR EACH ROW BEGIN IF NEW.user_id = 910003 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic batch failure'; END IF; END");
        try {
            $job->handle();
            $this->fail('Partial failure must be retried.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertDatabaseCount('coupon_uses', 1);
            $this->assertDatabaseCount('coupon_assignment_deliveries', 1);
        }
        DB::unprepared('DROP TRIGGER p6_fail_recipient');
        $job->handle();
        $job->handle();
        $this->assertDatabaseCount('coupon_uses', 2);
        $this->assertDatabaseCount('coupon_assignment_deliveries', 2);
    }

    public function test_catalog_commit_cleans_replaced_media_once_but_preserves_shared_legacy_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        foreach (['products/old.png', 'products/gallery/old.png', 'variants/shared.png'] as $path) {
            $disk->put($path, 'old');
        }
        \App\Models\Product::findOrFail(910001)->update(['thumbnail' => 'products/old.png', 'gallery' => ['products/gallery/old.png']]);
        \App\Models\ProductVariant::findOrFail(910001)->update(['thumbnail' => 'variants/shared.png']);
        \App\Models\Product::create(['name' => 'Legacy shared media', 'slug' => 'p6-shared', 'category_id' => 910001, 'thumbnail' => 'variants/shared.png']);
        app(\App\Modules\Catalog\Infrastructure\SaveProduct::class)->save(['name' => 'P6 media save', 'category_id' => 910001,
            'visibility' => 'public', 'status' => 'published', 'thumbnail' => \Illuminate\Http\UploadedFile::fake()->create('new.png', 1),
            'remove_gallery' => ['products/gallery/old.png'], 'gallery' => [\Illuminate\Http\UploadedFile::fake()->create('new-gallery.png', 1)],
            'variants' => [['id' => 910001, 'sku' => 'P3-SKU', 'price' => 25000000, 'thumbnail' => \Illuminate\Http\UploadedFile::fake()->create('new-sku.png', 1)]]], 910001);
        $disk->assertMissing('products/old.png');
        $disk->assertMissing('products/gallery/old.png');
        $disk->assertExists('variants/shared.png');
        $this->assertCount(4, $disk->allFiles());
    }
}
