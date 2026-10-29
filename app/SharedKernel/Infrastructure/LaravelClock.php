<?php

namespace App\SharedKernel\Infrastructure;

use App\SharedKernel\Clock;
use Illuminate\Support\Carbon;

final class LaravelClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return Carbon::now()->toDateTimeImmutable();
    }
}
