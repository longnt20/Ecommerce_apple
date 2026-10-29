<?php

namespace App\Modules\Identity\Contracts;

use App\SharedKernel\ActorContext;

interface AccountAccess
{
    public function active(ActorContext $actor): bool;

    public function administrator(ActorContext $actor): bool;
}
