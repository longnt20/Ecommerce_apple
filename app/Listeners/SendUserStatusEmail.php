<?php

namespace App\Listeners;

use App\Events\UserStatusChanged;
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

        // Gửi email khi trạng thái thay đổi
        if ($event->oldStatus !== $event->newStatus) {
            if ($event->newStatus === 'blocked') {
                Mail::to($user->email)->send(new AccountBlockedMail($user));
            } elseif ($event->newStatus === 'active') {
                Mail::to($user->email)->send(new AccountUnblockedMail($user));
            }
        }

        // Gửi email khi vai trò thay đổi
        if ($event->oldRole !== $event->newRole) {
            Mail::to($user->email)->send(new RoleChangedMail($user, $event->oldRole, $event->newRole));
        }
    }
}
