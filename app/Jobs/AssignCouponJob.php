<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignCouponJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $revision = 0;

    protected $coupon;

    protected $userIds;

    protected const BATCH_SIZE = 1000;

    public function __construct($coupon, $userIds)
    {
        $this->afterCommit();
        $this->revision = (int) ($coupon->assignment_revision ?? 0);
        $this->coupon = $coupon;
        $this->userIds = $userIds;
    }

    public function handle(): void
    {
        $assignments = app(\App\Modules\Pricing\Infrastructure\CouponAssignments::class);
        foreach (array_unique(array_map('intval', $this->userIds ?? [])) as $userId) {
            if (! User::whereKey($userId)->where('status', 'active')->whereNotIn('role', ['admin', 'employee'])->exists()) {
                continue;
            }
            $id = $assignments->assign($this->coupon->id, $userId, $this->revision);
            if ($id !== null) {
                NotifyCouponAssignment::dispatch($id)->afterCommit();
            }
        }
    }
}
