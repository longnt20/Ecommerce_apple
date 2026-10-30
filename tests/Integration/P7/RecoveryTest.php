<?php

namespace Tests\Integration\P7;

use App\Models\Order;
use App\Services\Inventory\ReconcileInventory;
use App\Services\Ordering\PlaceOrder;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Support\P7RecoveryProbe;
use Tests\Support\P7Runtime;
use Tests\Support\P7Sandbox;
use Tests\TestCase;

class RecoveryTest extends TestCase
{
    private ?P7Sandbox $sandbox = null;

    private ?P7Runtime $runtime = null;

    protected function setUp(): void
    {
        if (getenv('P7_SCHEMA_TESTS') !== '1') {
            $this->markTestSkipped('P7_SCHEMA_TESTS=1 permits sandbox runtime rehearsal.');
        }
        parent::setUp();
        $this->sandbox = new P7Sandbox;
        $this->sandbox->catalog(1, 1, 1);
        $this->runtime = new P7Runtime($this->sandbox->connection);
        P7Runtime::realQueue();
    }

    protected function tearDown(): void
    {
        $this->runtime?->close();
        $this->sandbox?->close();
        parent::tearDown();
    }

    public function test_real_worker_failed_job_retry_and_crash_recovery(): void
    {
        DB::beginTransaction();
        Queue::push(new P7RecoveryProbe('rollback'));
        $this->assertSame(0, DB::table('jobs')->count());
        DB::rollBack();
        $this->assertSame(0, DB::table('jobs')->count());
        DB::transaction(fn () => Queue::push(new P7RecoveryProbe('failure')));
        $this->assertSame(1, DB::table('jobs')->count());
        $this->runtime->run(['queue:work', 'database', '--once', '--tries=1', '--sleep=0', '--timeout=60']);
        $this->assertSame(1, DB::table('failed_jobs')->count());
        $health = json_decode($this->runtime->run(['operations:health']), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $health['failed_jobs']);
        $this->assertArrayNotHasKey('payload', $health);
        $this->resume('failure');
        $this->runtime->run(['queue:retry', DB::table('failed_jobs')->value('uuid')]);
        $this->runtime->run(['queue:work', 'database', '--once', '--tries=3', '--sleep=0', '--timeout=60']);
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(1, DB::table('cache')->where('key', 'p7_done_failure')->count());
        Queue::push(new P7RecoveryProbe('crash'));
        $worker = $this->runtime->process(['queue:work', 'database', '--once', '--tries=3', '--sleep=0', '--timeout=60']);
        try {
            $worker->start();
            $ready = $worker->waitUntil(fn ($type, $output) => str_contains($output, 'P7_CRASH_READY'));
            $this->assertTrue($ready, 'Worker must reach the post-effect/pre-ack barrier.');
        } finally {
            $worker->stop(0);
        }
        $this->assertSame(1, DB::table('jobs')->whereNotNull('reserved_at')->count());
        $this->resume('crash');
        // Advance only this synthetic job's visibility clock; do not wait 90 seconds.
        DB::table('jobs')->update(['reserved_at' => time() - 91]);
        $this->runtime->run(['queue:work', 'database', '--once', '--tries=3', '--sleep=0', '--timeout=60']);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('cache')->where('key', 'p7_effect_crash')->count());
        $this->assertSame(1, DB::table('cache')->where('key', 'p7_done_crash')->count());
    }

    public function test_real_scheduler_expires_once_and_release_route_cache_rehearsal(): void
    {
        DB::table('users')->insert(['id' => 910001, 'name' => 'Synthetic customer', 'email' => 'customer@example.test',
            'password' => 'synthetic', 'code' => 'P7-USER', 'role' => 'user', 'status' => 'active']);
        config(['ordering.transitions_enabled' => true]);
        $order = app(PlaceOrder::class)->place(['idempotency_key' => (string) Str::uuid(), 'payment_method' => 'cod',
            'items' => [['product_id' => 1000001, 'variant_id' => 2000001, 'quantity' => 1]],
            'fullname' => 'Synthetic', 'phone' => '0000000000', 'address' => 'Synthetic',
            'ward' => 'Synthetic', 'district' => 'Synthetic', 'province' => 'Synthetic'], 910001);
        // Create an overdue online hold without calling a payment provider.
        DB::table('orders')->where('id', $order->id)->update(['payment_method' => 'vnpay']);
        DB::table('inventory_reservations')->where('order_id', $order->id)->update(['expires_at' => now()->subMinute()]);
        $this->runtime->run(['config:cache']);
        $this->runtime->run(['route:cache']);
        $this->runtime->run(['schedule:run']);
        $this->assertSame('cancelled', Order::findOrFail($order->id)->status);
        $count = DB::table('inventory_transactions')->count();
        $this->runtime->run(['schedule:run']);
        $this->assertSame($count, DB::table('inventory_transactions')->count());
        $this->assertSame(0, (int) DB::table('inventory')->sum('reserved_quantity'));
        $this->assertSame([], app(ReconcileInventory::class)->report()['unexplained']);
        $this->runtime->run(['inventory:reconcile']);
        $this->runtime->run(['route:clear']);
        $this->runtime->run(['config:clear']);
        $this->runtime->run(['inventory:reconcile']);
    }

    private function resume(string $mode): void
    {
        DB::table('cache')->insert(['key' => 'p7_resume_'.$mode, 'value' => 'synthetic', 'expiration' => 2147483647]);
    }

    public function test_sandbox_row_lock_wait_is_measured_and_no_balance_changes(): void
    {
        $before = DB::table('inventory')->first();
        $result = \Tests\Support\P7LockWait::measure($this->sandbox->connection, 1);
        $this->assertGreaterThanOrEqual(400, $result['min_ms']);
        $this->assertEquals($before, DB::table('inventory')->first());
    }

    public function test_full_backup_restores_all_tables_foreign_keys_and_inventory_balances(): void
    {
        DB::table('users')->insert(['id' => 910001, 'name' => 'Synthetic customer', 'email' => 'customer@example.test',
            'password' => 'synthetic', 'code' => 'P7-USER', 'role' => 'user', 'status' => 'active']);
        config(['ordering.transitions_enabled' => true]);
        $order = app(PlaceOrder::class)->place(['idempotency_key' => (string) Str::uuid(), 'payment_method' => 'cod',
            'items' => [['product_id' => 1000001, 'variant_id' => 2000001, 'quantity' => 1]],
            'fullname' => 'Synthetic', 'phone' => '0000000000', 'address' => 'Synthetic',
            'ward' => 'Synthetic', 'district' => 'Synthetic', 'province' => 'Synthetic'], 910001);
        app(OrderService::class)->changeStatus($order, 'confirmed', 910002, 'p7-confirm');
        app(OrderService::class)->changeStatus($order, 'shipping', 910002, 'p7-ship');
        $this->assertSame(9, (int) DB::table('inventory')->value('quantity'));
        $result = \Tests\Support\P7RestoreDrill::run($this->sandbox);
        $this->assertSame(56, $result['table_count']);
        $this->assertTrue($result['all_table_data_equal']);
        $this->assertGreaterThan(20, $result['fk_count']);
        $this->assertSame(0, $result['orphan_count']);
        $this->assertSame(0, $result['reconcile_exit']);
    }
}
