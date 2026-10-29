<?php

namespace App\Exceptions;

use DomainException;

class OrderTransitionConflict extends DomainException
{
    public function __construct(public readonly string $reasonCode, string $message)
    {
        parent::__construct($message);
    }
}
