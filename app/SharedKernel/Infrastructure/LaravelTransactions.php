<?php

namespace App\SharedKernel\Infrastructure;

use App\SharedKernel\Transactions;
use Illuminate\Support\Facades\DB;

final class LaravelTransactions implements Transactions
{
    public function run(callable $work, int $attempts = 3): mixed
    {
        return DB::transaction($work, $attempts);
    }

    public function isUniqueViolation(\Throwable $error): bool
    {
        return $error instanceof \Illuminate\Database\UniqueConstraintViolationException;
    }
}
