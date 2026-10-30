<?php

namespace App\Modules\Pricing\Infrastructure;

use App\Exceptions\InventoryConflict;
use App\Modules\Pricing\Contracts\CouponRedemptions as Contract;
use App\SharedKernel\Clock;
use Illuminate\Support\Facades\DB;

final class CouponRedemptions implements Contract
{
    public function __construct(private Clock $clock) {}

    public function reserve(int $orderId, int $userId, string $code, int $subtotal): int
    {
        $this->transactionRequired();
        $coupon = DB::table('coupons')->where('code', $code)->lockForUpdate()->first();
        $date = $this->clock->now()->format('Y-m-d');
        if (! $coupon || $coupon->deleted_at || ! $coupon->status || ($coupon->start_date && $coupon->start_date > $date)
            || ($coupon->expire_date && $coupon->expire_date < $date) || $coupon->used_count < 0) {
            $this->invalid();
        }
        // Ambiguous legacy duplicate assignments are preserved and refused, never merged heuristically.
        $assignments = DB::table('coupon_uses')->where('coupon_id', $coupon->id)->where('user_id', $userId)->lockForUpdate()->get();
        $assignment = $assignments->count() === 1 ? $assignments->first() : null;
        if (! $assignment || $assignment->status !== 'unused' || $assignment->revoked_at
            || ($assignment->expired_at && $assignment->expired_at < $date)
            || DB::table('coupon_redemptions')->where('assignment_id', $assignment->id)->whereIn('status', ['reserved', 'redeemed'])->lockForUpdate()->get(['id'])->isNotEmpty()) {
            $this->invalid();
        }
        // A normal COUNT sees the earlier SKU snapshot under MySQL REPEATABLE READ.
        // Current locking reads must observe reservations committed while this checkout waited.
        $held = DB::table('coupon_redemptions')->where('coupon_id', $coupon->id)->where('status', 'reserved')->lockForUpdate()->get(['id'])->count();
        if ($coupon->max_usage !== null && $coupon->used_count + $held >= $coupon->max_usage) {
            $this->invalid();
        }
        $value = $this->cents($coupon->discount_value);
        if ($coupon->discount_type === 'percentage') {
            if ($value > 10000) {
                $this->invalid();
            }
            $discount = intdiv($subtotal, 10000) * $value + intdiv(($subtotal % 10000) * $value, 10000);
            $cap = $this->cents($coupon->discount_max_value);
            if ($cap % 100 !== 0) {
                $this->invalid();
            }
            if ($cap > 0) {
                $discount = min($discount, intdiv($cap, 100));
            }
        } else {
            if ($value % 100 !== 0) {
                $this->invalid();
            }
            $discount = intdiv($value, 100);
        }
        $discount = min($subtotal, $discount);
        if ($discount <= 0 || $discount >= $subtotal) {
            $this->invalid();
        }
        DB::table('coupon_redemptions')->insert(['order_id' => $orderId, 'coupon_id' => $coupon->id, 'user_id' => $userId,
            'assignment_id' => $assignment->id, 'status' => 'reserved', 'discount_amount' => $discount,
            'snapshot' => json_encode(['code' => $coupon->code, 'type' => $coupon->discount_type, 'value' => $coupon->discount_value,
                'cap' => $coupon->discount_max_value, 'subtotal' => $subtotal], JSON_THROW_ON_ERROR),
            'created_at' => $this->clock->now(), 'updated_at' => $this->clock->now()]);

        return $discount;
    }

    public function finish(int $orderId, bool $redeem): void
    {
        $this->transactionRequired();
        $row = DB::table('coupon_redemptions')->where('order_id', $orderId)->first();
        if (! $row) {
            return;
        }
        DB::table('coupons')->where('id', $row->coupon_id)->lockForUpdate()->first();
        $row = DB::table('coupon_redemptions')->where('id', $row->id)->lockForUpdate()->first();
        if ($row->status !== 'reserved') {
            return;
        }
        DB::table('coupon_redemptions')->where('id', $row->id)->update(['status' => $redeem ? 'redeemed' : 'released', 'updated_at' => $this->clock->now()]);
        if ($redeem) {
            DB::table('coupons')->where('id', $row->coupon_id)->increment('used_count');
            DB::table('coupon_uses')->where('id', $row->assignment_id)->update(['status' => 'used', 'applied_at' => $this->clock->now(), 'updated_at' => $this->clock->now()]);
        }
    }

    private function cents(string $value): int
    {
        if (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $value)) {
            $this->invalid();
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    private function transactionRequired(): void
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Coupon lifecycle must share the order transaction.');
        }
    }

    private function invalid(): never
    {
        throw new InventoryConflict('coupon_unavailable', 'Coupon không hợp lệ, chưa được cấp hoặc đã hết lượt dùng.');
    }
}
