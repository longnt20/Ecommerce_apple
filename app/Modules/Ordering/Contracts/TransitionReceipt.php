<?php

namespace App\Modules\Ordering\Contracts;

final readonly class TransitionReceipt
{
    public function __construct(public int $id, public string $payloadHash) {}
}
