<?php

namespace App\Modules\Inventory\Contracts;

final readonly class Hold
{
    public function __construct(public int $id, public string $status, public ?\DateTimeImmutable $expiresAt) {}
}
