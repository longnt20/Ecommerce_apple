<?php

namespace App\Listeners;

use App\Events\UserStatusChanged;
use App\Jobs\SendAccountBlockedMail;
use App\Jobs\SendAccountUnblockedMail;
use App\Jobs\SendRoleChangedMail;
use App\Mail\AccountBlockedMail;
use App\Mail\AccountUnblockedMail;
use App\Mail\RoleChangedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendUserStatusEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserStatusChanged $event): void
    {
        $user = $event->user;

        // Gửi mail trạng thái
        if ($event->oldStatus !== $event->newStatus) {
            if ($event->newStatus === 'blocked') {
                SendAccountBlockedMail::dispatch($user);
            } elseif ($event->newStatus === 'active') {
                SendAccountUnblockedMail::dispatch($user);
            }
        }

        // Gửi mail thay đổi vai trò
        if ($event->oldRole !== $event->newRole) {
            SendRoleChangedMail::dispatch($user, $event->oldRole, $event->newRole);
        }
    }
}
