<?php

namespace Tests\Integration\P5;

use App\Modules\Inventory\Contracts\InventoryMaintenance;
use App\Modules\Ordering\Contracts\Checkout;
use App\Modules\Ordering\Contracts\OrderTransitions;
use App\Services\Ordering\PlaceOrder;
use App\Services\OrderService;
use App\SharedKernel\ActorContext;
use App\SharedKernel\Clock;
use Illuminate\Support\Facades\DB;
use Tests\Support\MySqlTestCase;

class ModuleCompatibilityTest extends MySqlTestCase
{
    public function test_cli_with_no_logged_in_user_replays_legacy_and_public_contract_without_duplicate_effect(): void
    {
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        config(['ordering.transitions_enabled' => true]);
        $this->assertTrue(auth()->guest());
        $payload = $this->checkoutPayload();
        $legacy = app(PlaceOrder::class)->place($payload, 910001);
        $canonical = app(Checkout::class)->place($payload, new ActorContext(910001));
        $this->assertSame($legacy->id, $canonical->id);
        $this->assertSame((int) $legacy->final_amount, (int) $canonical->amount);
        $this->assertSame((int) $legacy->warehouse_id, $canonical->warehouseId);
        $first = app(OrderService::class)->changeStatus($legacy, 'confirmed', 910002, 'bridge-confirm');
        $second = app(OrderTransitions::class)->change($canonical->id, 'confirmed', new ActorContext(910002), 'bridge-confirm');
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertDatabaseCount('order_transitions', 1);
        $this->assertSame([], app(InventoryMaintenance::class)->reconcile()['unexplained']);
    }

    public function test_order_clock_is_injected_independently_of_http_authentication(): void
    {
        $this->seedCommerce();
        app()->instance(Clock::class, new class implements Clock
        {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-12-02T12:00:00+07:00');
            }
        });
        $snapshot = app(Checkout::class)->place($this->checkoutPayload(), new ActorContext(910001));
        $this->assertStringStartsWith('DH20261202-', DB::table('orders')->where('id', $snapshot->id)->value('code'));
        $this->assertDatabaseHas('inventory_transactions', ['reference_id' => $snapshot->id, 'created_by' => 910001]);
    }

    public function test_payment_write_boundary_rejects_manufactured_or_mismatched_candidate_dtos(): void
    {
        $this->seedCommerce();
        config(['vnpay.enabled' => true, 'vnpay.vnp_tmn_code' => 'P5MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-p5-secret',
            'vnpay.vnp_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', 'vnpay.vnp_return_url' => 'http://localhost/api/vnpay/return']);
        $first = app(Checkout::class)->place(array_replace($this->checkoutPayload(), ['payment_method' => 'vnpay']), new ActorContext(910001));
        $second = app(Checkout::class)->place(array_replace($this->checkoutPayload(), ['payment_method' => 'vnpay']), new ActorContext(910001));
        $attempts = DB::table('payment_attempts')->orderBy('id')->get();
        $query = ['vnp_Amount' => '2500000000', 'vnp_TmnCode' => 'P5MERCHANT', 'vnp_TxnRef' => $attempts[0]->reference,
            'vnp_TransactionNo' => '555556', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00'];
        $ledger = app(\App\Modules\Payments\Contracts\PaymentLedger::class);
        $forged = new \App\Modules\Payments\Contracts\PaymentCandidate($attempts[0]->id, $first->id, $query);
        $this->assertSame('97', $ledger->settle($forged, fn () => $first->payment())->code);
        ksort($query);
        $query['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($query, '', '&', PHP_QUERY_RFC1738), 'synthetic-p5-secret');
        $mismatched = new \App\Modules\Payments\Contracts\PaymentCandidate($attempts[1]->id, $second->id, $query);
        $this->assertSame('01', $ledger->settle($mismatched, fn () => $second->payment())->code);
        $this->assertDatabaseCount('payment_events', 0);
        $this->assertSame(2, DB::table('payment_attempts')->where('status', 'pending')->count());
    }
}
