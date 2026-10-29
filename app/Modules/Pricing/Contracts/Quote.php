<?php

namespace App\Modules\Pricing\Contracts;

final readonly class Quote
{
    /** @param list<QuoteLine> $lines */
    public function __construct(public array $lines, public int $total) {}
}
