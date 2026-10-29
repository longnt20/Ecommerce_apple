<?php

namespace App\SharedKernel;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
