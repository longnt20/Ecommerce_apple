<?php

namespace App\SharedKernel;

final readonly class ActorContext
{
    public function __construct(public ?int $id)
    {
        if ($id !== null && $id < 1) {
            throw new \InvalidArgumentException('Actor ID must be positive.');
        }
    }

    public static function system(): self
    {
        return new self(null);
    }
}
