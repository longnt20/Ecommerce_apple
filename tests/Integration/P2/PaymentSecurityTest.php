<?php

namespace Tests\Integration\P2;

use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\MySqlTestCase;

class PaymentSecurityTest extends MySqlTestCase
{
    private int $order;

    private PaymentAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCommerce();
        config([
            'vnpay.enabled' => true,
            'vnpay.vnp_tmn_code' => 'P2MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-private-key',
            'vnpay.vnp_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'vnpay.vnp_return_url' => 'http://localhost/api/vnpay/return',
            'vnpay.vnp_frontend_return_url' => 'http://localhost:3000/payment-success',
        ]);
        $this->actingAs(User::findOrFail(910001), 'sanctum');
        $payload = $this->checkoutPayload('0');
        $payload['payment_method'] = 'vnpay';
        $response = $this->postJson('/api/checkout', $payload)->assertOk()->assertJsonStructure(['payment_url', 'order_id']);
        $this->order = $response->json('order_id');
        $this->attempt = PaymentAttempt::where('order_id', $this->order)->firstOrFail();
    }

    private function query(array $override = []): array
    {
        $data = array_merge([
            'vnp_Amount' => '2500000000', 'vnp_TmnCode' => 'P2MERCHANT',
            'vnp_TxnRef' => $this->attempt->reference, 'vnp_TransactionNo' => '14226112',
            'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00',
            'vnp_OrderInfo' => 'Thanh toan: P2 + dien thoai', 'vnp_PayDate' => '20261029100000',
        ], $override);
        // Independent implementation of the official PHP algorithm (not gateway sign()).
        ksort($data);
        $parts = [];
        foreach ($data as $key => $value) {
            $parts[] = urlencode($key).'='.urlencode($value);
        }
        $data['vnp_SecureHash'] = hash_hmac('sha512', implode('&', $parts), 'synthetic-private-key');

        return $data;
    }

    private function ipn(array $query)
    {
        return $this->getJson('/api/vnpay/ipn?'.http_build_query($query));
    }

    public function test_verified_ipn_pays_once_and_return_does_not_pay(): void
    {
        $query = $this->query();
        $this->get('/api/vnpay/return?'.http_build_query($query))->assertRedirect('http://localhost:3000/payment-success?order_id='.$this->order);
        $this->assertDatabaseHas('orders', ['id' => $this->order, 'payment_status' => 'unpaid']);
        $this->ipn($query)->assertOk()->assertJsonPath('RspCode', '00');
        $paidAt = DB::table('orders')->where('id', $this->order)->value('paid_at');
        $this->ipn(array_reverse($query, true))->assertOk()->assertJsonPath('RspCode', '02');
        $this->assertDatabaseHas('orders', ['id' => $this->order, 'payment_status' => 'paid', 'transaction_id' => '14226112', 'paid_at' => $paidAt]);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 5, 'reserved_quantity' => 1]);
    }

    /** @dataProvider invalidCallbacks */
    public function test_invalid_callback_cannot_pay(string $case, string $expected): void
    {
        $query = match ($case) {
            'merchant' => $this->query(['vnp_TmnCode' => 'OTHER']),
            'amount' => $this->query(['vnp_Amount' => '100']),
            'currency' => $this->query(['vnp_CurrCode' => 'USD']),
            'unknown' => $this->query(['vnp_TxnRef' => 'UNKNOWN']),
            default => $this->query(),
        };
        if ($case === 'signature') {
            $query['vnp_SecureHash'] = str_repeat('0', 128);
        }
        if ($case === 'unsigned') {
            unset($query['vnp_SecureHash']);
        }
        if ($case === 'cod') {
            DB::table('orders')->where('id', $this->order)->update(['payment_method' => 'COD']);
        }
        $this->ipn($query)->assertOk()->assertJsonPath('RspCode', $expected);
        $this->assertDatabaseHas('orders', ['id' => $this->order, 'payment_status' => 'unpaid']);
        $this->assertDatabaseCount('payment_events', 0);
    }

    public static function invalidCallbacks(): array
    {
        return [['merchant', '01'], ['amount', '04'], ['currency', '04'], ['unknown', '01'], ['signature', '97'], ['unsigned', '97'], ['cod', '01']];
    }

    public function test_duplicate_query_parameters_and_arrays_are_rejected(): void
    {
        $this->getJson('/api/vnpay/ipn?'.http_build_query($this->query()).'&vnp_Amount=2500000000')->assertJsonPath('RspCode', '97');
        $this->ipn(['vnp_Amount' => ['1'], 'vnp_SecureHash' => str_repeat('0', 128)])->assertJsonPath('RspCode', '97');
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_retry_reuses_attempt_and_cannot_repeat_order_or_reservation(): void
    {
        $path = '/api/orders/'.$this->order.'/payment';
        $first = $this->postJson($path)->assertOk()->json('payment_url');
        $second = $this->postJson($path)->assertOk()->json('payment_url');
        $this->assertSame($first, $second);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payment_attempts', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->actingAs(User::findOrFail(910002), 'sanctum')->postJson($path)->assertNotFound();
    }

    public function test_failed_attempt_cannot_later_be_promoted_by_a_replay(): void
    {
        $this->ipn($this->query(['vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02']))->assertJsonPath('RspCode', '00');
        $this->ipn($this->query())->assertJsonPath('RspCode', '02');
        $this->assertDatabaseHas('orders', ['id' => $this->order, 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('payment_attempts', ['id' => $this->attempt->id, 'status' => 'failed']);
    }

    /** @dataProvider lateCases */
    public function test_late_or_cancelled_payment_requires_reconciliation(string $case): void
    {
        if ($case === 'cancelled') {
            DB::table('orders')->where('id', $this->order)->update(['status' => 'cancelled']);
        } else {
            $this->travel(16)->minutes();
        }
        $this->ipn($this->query())->assertJsonPath('RspCode', '00');
        $this->assertDatabaseHas('orders', ['id' => $this->order, 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('payment_attempts', ['id' => $this->attempt->id, 'status' => 'reconciliation_required']);
        $this->postJson('/api/orders/'.$this->order.'/payment')->assertStatus(409);
    }

    public static function lateCases(): array
    {
        return [['cancelled'], ['expired']];
    }

    public function test_one_provider_transaction_cannot_pay_two_orders(): void
    {
        $this->ipn($this->query())->assertJsonPath('RspCode', '00');
        $payload = $this->checkoutPayload();
        $payload['payment_method'] = 'vnpay';
        $other = $this->postJson('/api/checkout', $payload)->assertOk()->json('order_id');
        $reference = PaymentAttempt::where('order_id', $other)->value('reference');
        $this->ipn($this->query(['vnp_TxnRef' => $reference]))->assertJsonPath('RspCode', '99');
        $this->assertDatabaseHas('orders', ['id' => $other, 'payment_status' => 'unpaid']);
        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_disabling_new_payments_keeps_in_flight_notifications_working(): void
    {
        config(['vnpay.enabled' => false]);
        $this->postJson('/api/orders/'.$this->order.'/payment')->assertStatus(503);
        $this->get('/api/vnpay/return?'.http_build_query($this->query()))
            ->assertRedirect('http://localhost:3000/payment-success?order_id='.$this->order);
        $this->ipn($this->query())->assertJsonPath('RspCode', '00');
        $this->assertDatabaseHas('orders', ['id' => $this->order, 'payment_status' => 'paid']);
        $this->assertDatabaseCount('payment_events', 1);
    }
}
