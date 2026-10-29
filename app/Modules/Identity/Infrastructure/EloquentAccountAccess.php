<?php

namespace App\Modules\Identity\Infrastructure;

use App\Models\User;
use App\Modules\Identity\Contracts\AccountAccess;
use App\SharedKernel\ActorContext;

final class EloquentAccountAccess implements AccountAccess
{
    public function active(ActorContext $actor): bool
    {
        return $actor->id !== null && User::find($actor->id)?->status === 'active';
    }

    public function administrator(ActorContext $actor): bool
    {
        $user = $actor->id === null ? null : User::find($actor->id);

        return $user && $user->role === 'admin' && $user->status === 'active';
    }
}
