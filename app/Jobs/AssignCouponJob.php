<?php

namespace App\Jobs;

use App\Mail\CouponAssignedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AssignCouponJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $coupon;
    protected $userIds;
    protected const BATCH_SIZE = 1000;

    public function __construct($coupon, $userIds)
    {
        $this->coupon = $coupon;
        $this->userIds = $userIds;
    }

    public function handle()
    {
        $now = now();

        collect($this->userIds)->chunk(self::BATCH_SIZE)->each(function ($userBatch) use ($now) {
            $batchData = $userBatch->map(function ($userId) use ($now) {
                return [
                    'user_id' => $userId,
                    'coupon_id' => $this->coupon->id,
                    'status' => 'unused',
                    'expired_at' => $now->clone()->addDays(30),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });

            DB::table('coupon_uses')->insert($batchData->toArray());
        });

        User::query()
            ->whereIn('id', $this->userIds)
            ->chunk(500, function ($users) {
                foreach ($users as $user) {
                    Mail::to($user->email)->queue(new CouponAssignedMail($user, $this->coupon));
                }
            });
    }
}
