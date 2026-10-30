<?php

namespace App\Jobs;

use App\Mail\CouponAssignedMail;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class NotifyCouponAssignment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $assignmentId)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $delivery = DB::table('coupon_assignment_deliveries')->where('assignment_id', $this->assignmentId)->lockForUpdate()->first();
            if (! $delivery || $delivery->status !== 'pending') {
                return;
            }
            $assignment = DB::table('coupon_uses')->where('id', $this->assignmentId)->first();
            $coupon = Coupon::find($assignment->coupon_id);
            $user = User::find($assignment->user_id);
            if (! $coupon || ! $user || ! $coupon->status || $assignment->revoked_at || $assignment->status !== 'unused'
                || ($assignment->expired_at && $assignment->expired_at < now()->toDateString())) {
                DB::table('coupon_assignment_deliveries')->where('id', $delivery->id)->update(['status' => 'suppressed', 'updated_at' => now()]);

                return;
            }
            // Delivery is at-least-once: a crash after SMTP acceptance can resend the notice.
            Mail::to($user->email)->send(new CouponAssignedMail($user, $coupon));
            DB::table('coupon_assignment_deliveries')->where('id', $delivery->id)->update(['status' => 'queued', 'updated_at' => now()]);
            DB::table('coupon_uses')->where('id', $this->assignmentId)->update(['notified_at' => now()]);
        });
    }
}
