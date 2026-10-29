<?php

namespace App\SharedKernel;

interface Transactions
{
    public function run(callable $work, int $attempts = 3): mixed;

    public function isUniqueViolation(\Throwable $error): bool;
}
