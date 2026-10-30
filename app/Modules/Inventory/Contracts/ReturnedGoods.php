<?php

namespace App\Modules\Inventory\Contracts;

use App\SharedKernel\ActorContext;

interface ReturnedGoods
{
    public function receive(int $orderId, int $itemId, int $warehouseId, int $variantId, int $shippedQuantity, ActorContext $actor, string $key, array $input): array;

    public function history(int $orderId): array;
}
