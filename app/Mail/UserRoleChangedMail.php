<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRoleChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $user;
    public $oldRole;
    public $newRole;

    public function __construct(User $user, $oldRole, $newRole)
    {
        $this->user = $user;
        $this->oldRole = $oldRole;
        $this->newRole = $newRole;
    }

    public function build()
    {
        return $this->view('admin.emails.user-role-changed')
            ->with([
                'user' => $this->user,
                'oldRole' => $this->oldRole,
                'newRole' => $this->newRole,
            ]);
    }
}
