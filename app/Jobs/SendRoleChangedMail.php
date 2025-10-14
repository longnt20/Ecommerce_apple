<?php

namespace App\Jobs;

use App\Mail\RoleChangedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class SendRoleChangedMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $oldRole;
    public $newRole;

    public function __construct(User $user, string $oldRole, string $newRole)
    {
        $this->user = $user;
        $this->oldRole = $oldRole;
        $this->newRole = $newRole;
    }

    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new RoleChangedMail($this->user, $this->oldRole, $this->newRole)
        );
    }
}

