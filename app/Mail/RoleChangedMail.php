<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RoleChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $oldRole;
    public $newRole;

    public function __construct(User $user, string $oldRole, string $newRole)
    {
        $this->user = $user;
        $this->oldRole = $oldRole;
        $this->newRole = $newRole;
    }

    public function build()
    {
        return $this->subject('Vai trò của bạn đã được thay đổi')
                    ->markdown('admin.mails.role_changed', [
                        'user' => $this->user,
                        'oldRole' => ucfirst($this->oldRole),
                        'newRole' => ucfirst($this->newRole),
                    ]);
    }
}
