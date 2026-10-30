<?php

namespace Tests\Integration\P6;

use App\Exceptions\InventoryConflict;
use App\Jobs\AssignCouponJob;
use App\Jobs\NotifyCouponAssignment;
use App\Models\Coupon;
use App\Models\InventoryReservation;
use App\Models\User;
use App\Modules\Ordering\Contracts\Checkout;
use App\Modules\Ordering\Contracts\OrderTransitions;
use App\Modules\Ordering\Contracts\PaymentWorkflow;
use App\Modules\Ordering\Contracts\ReservationExpiry;
use App\SharedKernel\ActorContext;
use Illuminate\Support\Facades\DB;
use Tests\Support\MySqlTestCase;

class CouponTest extends MySqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        config(['ordering.transitions_enabled' => true, 'vnpay.enabled' => true,
            'vnpay.vnp_tmn_code' => 'P6MERCHANT', 'vnpay.vnp_hash_secret' => 'p6-synthetic-secret',
            'vnpay.vnp_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', 'vnpay.vnp_return_url' => 'http://localhost/api/vnpay/return']);
    }

    private function coupon(): Coupon
    {
        $coupon = Coupon::create(['user_id' => 910002, 'code' => 'P6SAVE', 'name' => 'P6 coupon', 'discount_type' => 'fixed',
            'discount_value' => 100000, 'discount_max_value' => 0, 'max_usage' => 1, 'status' => 1,
            'start_date' => now()->subDay(), 'expire_date' => now()->addDays(10)]);
        (new AssignCouponJob($coupon, [910001, 910001]))->handle();

        return $coupon;
    }

    public function test_job_retry_does_not_duplicate_assignments_or_delivery_records_and_vouchers_are_user_scoped(): void
    {
        $coupon = $this->coupon();
        (new AssignCouponJob($coupon, [910001]))->handle();
        $this->assertDatabaseCount('coupon_uses', 1);
        $this->assertDatabaseCount('coupon_assignment_deliveries', 1);
        $this->actingAs(User::findOrFail(910001), 'sanctum')->getJson('/api/user')->assertOk()->assertJsonCount(1, 'vouchers');
        $this->actingAs(User::findOrFail(910002), 'sanctum')->getJson('/api/user')->assertOk()->assertJsonCount(0, 'vouchers');
        $id = DB::table('coupon_uses')->value('id');
        (new NotifyCouponAssignment($id))->handle();
        (new NotifyCouponAssignment($id))->handle();
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\CouponAssignedMail::class, 1);
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\CouponAssignedMail::class, fn ($mail) => str_contains($mail->render(), 'P6SAVE'));
    }

    public function test_pending_cancellation_releases_cap_and_cod_confirmation_redeems_immutable_discount_once(): void
    {
        $coupon = $this->coupon();
        $payload = array_merge($this->checkoutPayload(), ['coupon_code' => 'P6SAVE']);
        $first = app(Checkout::class)->place($payload, new ActorContext(910001));
        $this->assertSame('24900000.00', $first->amount);
        try {
            app(Checkout::class)->place(array_replace($payload, ['idempotency_key' => (string) \Illuminate\Support\Str::uuid()]), new ActorContext(910001));
            $this->fail('A reserved assignment must not be reused.');
        } catch (InventoryConflict $error) {
            $this->assertSame('coupon_unavailable', $error->reasonCode);
            $this->assertDatabaseCount('orders', 1);
        }
        app(OrderTransitions::class)->change($first->id, 'cancelled', new ActorContext(910002), 'cancel');
        $this->assertDatabaseHas('coupon_redemptions', ['order_id' => $first->id, 'status' => 'released']);
        $second = app(Checkout::class)->place(array_replace($payload, ['idempotency_key' => (string) \Illuminate\Support\Str::uuid()]), new ActorContext(910001));
        $coupon->update(['discount_value' => 500000, 'status' => 0]);
        app(OrderTransitions::class)->change($second->id, 'confirmed', new ActorContext(910002), 'confirm');
        app(OrderTransitions::class)->change($second->id, 'confirmed', new ActorContext(910002), 'confirm');
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 1]);
        $this->assertDatabaseHas('orders', ['id' => $second->id, 'discount_amount' => 100000, 'final_amount' => 24900000]);
        $this->assertDatabaseHas('coupon_uses', ['user_id' => 910001, 'status' => 'used']);
    }

    public function test_online_payment_redeems_once_using_discounted_attempt_amount_and_expiry_releases(): void
    {
        $this->coupon();
        $payload = array_merge($this->checkoutPayload(), ['coupon_code' => 'P6SAVE', 'payment_method' => 'vnpay']);
        $first = app(Checkout::class)->place($payload, new ActorContext(910001));
        InventoryReservation::where('order_id', $first->id)->update(['expires_at' => now()->subMinute()]);
        $this->assertTrue(app(ReservationExpiry::class)->expire($first->id));
        $this->assertDatabaseHas('coupon_redemptions', ['order_id' => $first->id, 'status' => 'released']);
        $second = app(Checkout::class)->place(array_replace($payload, ['idempotency_key' => (string) \Illuminate\Support\Str::uuid()]), new ActorContext(910001));
        $attempt = DB::table('payment_attempts')->where('order_id', $second->id)->first();
        $query = ['vnp_Amount' => '2490000000', 'vnp_TmnCode' => 'P6MERCHANT', 'vnp_TxnRef' => $attempt->reference,
            'vnp_TransactionNo' => '666661', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00'];
        ksort($query);
        $query['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($query, '', '&', PHP_QUERY_RFC1738), 'p6-synthetic-secret');
        $workflow = app(PaymentWorkflow::class);
        $this->assertSame('00', $workflow->notify($query)['RspCode']);
        $workflow->notify($query);
        $this->assertDatabaseHas('coupons', ['code' => 'P6SAVE', 'used_count' => 1]);
        $this->assertDatabaseHas('orders', ['id' => $second->id, 'payment_status' => 'paid']);
        $this->assertFalse(app(ReservationExpiry::class)->expire($second->id));
    }

    public function test_ambiguous_legacy_assignments_are_preserved_and_rejected(): void
    {
        $coupon = $this->coupon();
        DB::table('coupon_uses')->insert(['coupon_id' => $coupon->id, 'user_id' => 910001, 'status' => 'unused']);
        try {
            app(Checkout::class)->place(array_merge($this->checkoutPayload(), ['coupon_code' => 'P6SAVE']), new ActorContext(910001));
            $this->fail('Duplicate legacy assignments need reconciliation.');
        } catch (InventoryConflict $error) {
            $this->assertSame('coupon_unavailable', $error->reasonCode);
            $this->assertDatabaseCount('coupon_uses', 2);
            $this->assertDatabaseCount('orders', 0);
            $this->assertSame(0, (int) DB::table('inventory')->sum('reserved_quantity'));
        }
    }

    public function test_stale_assignment_job_cannot_regrant_revoked_coupon_and_percentage_cap_is_exact(): void
    {
        $coupon = $this->coupon();
        $stale = new AssignCouponJob($coupon, [910001]);
        DB::table('coupons')->where('id', $coupon->id)->increment('assignment_revision');
        DB::table('coupon_uses')->where('coupon_id', $coupon->id)->update(['revoked_at' => now()]);
        $stale->handle();
        $this->assertNotNull(DB::table('coupon_uses')->where('coupon_id', $coupon->id)->value('revoked_at'));
        $coupon->refresh();
        (new AssignCouponJob($coupon, [910001]))->handle();
        $coupon->update(['discount_type' => 'percentage', 'discount_value' => '12.34', 'discount_max_value' => 1000000]);
        $order = app(Checkout::class)->place(array_merge($this->checkoutPayload(), ['coupon_code' => 'P6SAVE']), new ActorContext(910001));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'discount_amount' => 1000000, 'final_amount' => 24000000]);
    }
}
