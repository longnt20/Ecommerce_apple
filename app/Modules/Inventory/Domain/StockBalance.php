<?php

namespace App\Modules\Inventory\Domain;

use App\Exceptions\InventoryConflict;

final readonly class StockBalance
{
    public const MAX = 2147483647;

    public function __construct(public int $onHand, public int $reserved, public int $available)
    {
        if ($onHand < 0 || $onHand > self::MAX || $reserved < 0 || $reserved > $onHand || $available !== $onHand - $reserved) {
            throw new InventoryConflict('stock_balance_conflict', 'Số dư tồn kho không nhất quán; cần đối soát.');
        }
    }
}
