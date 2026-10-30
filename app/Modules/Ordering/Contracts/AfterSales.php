<?php

namespace App\Modules\Ordering\Contracts;

use App\SharedKernel\ActorContext;

interface AfterSales
{
    public function money(int $orderId, ActorContext $actor, string $key, array $input): array;

    public function receive(int $orderId, ActorContext $actor, string $key, array $input): array;

    public function history(int $orderId, ActorContext $actor): array;
}
