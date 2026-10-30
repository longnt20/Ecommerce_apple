<?php

namespace Tests\Integration\P2;

use App\Models\Order;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\DB;
use Tests\Support\DatabaseSandbox;
use Tests\TestCase;

class PaymentAtomicityTest extends TestCase
{
    private ?DatabaseSandbox $sandbox = null;

    private array $connection;

    private array $query;

    protected function setUp(): void
    {
        if (getenv('P2_SCHEMA_TESTS') !== '1') {
            $this->markTestSkipped('P2_SCHEMA_TESTS=1: disposable sandbox for two-process concurrency and injected storage failures.');
        }
        parent::setUp();
        $this->sandbox = new DatabaseSandbox;
        $name = 'apple_project_p1_fresh_'.bin2hex(random_bytes(6));
        $target = $this->sandbox->create($name);
        DatabaseSandbox::loadSnapshot($target, file_get_contents(database_path('schema/mysql-schema.sql')));
        $this->connection = $this->sandbox->connectionConfig($name);
        config(['database.connections.p2_atomic' => $this->connection, 'vnpay.enabled' => true,
            'vnpay.vnp_tmn_code' => 'P2MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-private-key',
            'vnpay.vnp_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'vnpay.vnp_return_url' => 'http://localhost/api/vnpay/return']);
        DB::setDefaultConnection('p2_atomic');
        (require database_path('migrations/2026_10_28_000001_reconcile_application_schema.php'))->up();
        (require database_path('migrations/2026_10_29_000001_create_verified_payments.php'))->up();
        (require database_path('migrations/2026_10_29_000002_create_order_transitions.php'))->up();
        (require database_path('migrations/2026_10_29_000003_create_inventory_protocol.php'))->up();
        (require database_path('migrations/2026_10_30_000001_create_commerce_lifecycle.php'))->up();
        DB::table('users')->insert(['id' => 1, 'name' => 'P2 synthetic', 'email' => 'p2-atomic@example.test', 'password' => 'synthetic-only', 'code' => 'P2-ATOMIC']);
        DB::table('warehouses')->insert(['id' => 1, 'code' => 'P2-WH', 'name' => 'Synthetic', 'address' => 'Synthetic', 'city' => 'Synthetic']);
        $order = Order::create(['user_id' => 1, 'code' => 'P2-ATOMIC-ORDER', 'payment_method' => 'VNPAY', 'total_price' => 10000, 'final_amount' => 10000]);
        $attempt = app(PaymentService::class)->start($order);
        $this->query = ['vnp_Amount' => '1000000', 'vnp_TmnCode' => 'P2MERCHANT', 'vnp_TxnRef' => $attempt->reference,
            'vnp_TransactionNo' => '14226112', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00'];
        ksort($this->query);
        $canonical = implode('&', array_map(fn ($key, $value) => urlencode($key).'='.urlencode($value), array_keys($this->query), $this->query));
        $this->query['vnp_SecureHash'] = hash_hmac('sha512', $canonical, 'synthetic-private-key');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->sandbox !== null) {
                DB::purge('p2_atomic');
                $this->sandbox->dropCreated();
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_two_independent_processes_can_only_record_payment_once(): void
    {
        $workers = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/payment-notify-worker.php')],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                stream_set_timeout($pipes[1], 15);
                stream_set_timeout($pipes[2], 15);
                $workers[] = [$process, $pipes];
                // Credentials travel over stdin, never command line or test output.
                fwrite($pipes[0], json_encode(['connection' => $this->connection, 'query' => $this->query])."\n");
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
            $codes = [];
            foreach ($workers as [$process, $pipes]) {
                $result = json_decode(trim(stream_get_contents($pipes[1])), true);
                $this->assertSame('', stream_get_contents($pipes[2]));
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process));
                $codes[] = $result['RspCode'];
            }
            sort($codes);
            $this->assertSame(['00', '02'], $codes);
            $this->assertDatabaseCount('payment_events', 1);
            $this->assertDatabaseHas('orders', ['id' => 1, 'payment_status' => 'paid', 'transaction_id' => '14226112']);
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

    public function test_event_storage_failure_rolls_back_attempt_and_order(): void
    {
        // A temporary sandbox-only fault; the application schema is never modified.
        DB::unprepared("CREATE TRIGGER p2_fail_event BEFORE INSERT ON payment_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic write failure'");
        $this->getJson('/api/vnpay/ipn?'.http_build_query($this->query))->assertOk()->assertJsonPath('RspCode', '99');
        $this->assertDatabaseHas('orders', ['id' => 1, 'payment_status' => 'unpaid', 'transaction_id' => null]);
        $this->assertDatabaseHas('payment_attempts', ['order_id' => 1, 'status' => 'pending', 'provider_transaction' => null]);
        $this->assertDatabaseCount('payment_events', 0);
        DB::unprepared('DROP TRIGGER p2_fail_event');
        $this->getJson('/api/vnpay/ipn?'.http_build_query($this->query))->assertJsonPath('RspCode', '00');
        $this->assertDatabaseCount('payment_events', 1);
    }
}
