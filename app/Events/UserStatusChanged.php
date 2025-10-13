<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $user;
    public $oldStatus;
    public $newStatus;
    public $oldRole;
    public $newRole;

    public function __construct(User $user, $oldStatus, $newStatus, $oldRole, $newRole)
    {
        $this->user = $user;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->oldRole = $oldRole;
        $this->newRole = $newRole;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
