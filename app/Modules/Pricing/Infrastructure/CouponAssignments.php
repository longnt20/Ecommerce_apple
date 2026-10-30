<?php

namespace App\Modules\Pricing\Infrastructure;

use Illuminate\Support\Facades\DB;

final class CouponAssignments
{
    public function assign(int $couponId, int $userId, int $revision): ?int
    {
        return DB::transaction(function () use ($couponId, $userId, $revision) {
            $coupon = DB::table('coupons')->where('id', $couponId)->lockForUpdate()->first();
            if (! $coupon || (int) $coupon->assignment_revision !== $revision || $coupon->deleted_at || ! $coupon->status || ($coupon->expire_date && $coupon->expire_date < now()->toDateString())) {
                return null;
            }
            $existing = DB::table('coupon_uses')->where('coupon_id', $couponId)->where('user_id', $userId)->lockForUpdate()->get();
            if ($existing->count() === 1 && $existing->first()->status === 'unused') {
                DB::table('coupon_uses')->where('id', $existing->first()->id)->update(['revoked_at' => null, 'updated_at' => now()]);
            }
            if ($existing->isNotEmpty()) {
                return $existing->count() === 1 ? (int) $existing->first()->id : null;
            }
            $expires = now()->addDays(30)->toDateString();
            if ($coupon->expire_date) {
                $expires = min($expires, $coupon->expire_date);
            }
            $id = DB::table('coupon_uses')->insertGetId(['coupon_id' => $couponId, 'user_id' => $userId, 'status' => 'unused',
                'expired_at' => $expires, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('coupon_assignment_deliveries')->insert(['assignment_id' => $id, 'status' => 'pending', 'updated_at' => now()]);

            return $id;
        }, 3);
    }

    public function revokeExcept(int $couponId, array $users): void
    {
        // Caller holds the coupon lock. Revocation keeps both used and unused assignment history.
        DB::table('coupon_uses')->where('coupon_id', $couponId)->whereNotIn('user_id', $users)->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'updated_at' => now()]);
    }
}
