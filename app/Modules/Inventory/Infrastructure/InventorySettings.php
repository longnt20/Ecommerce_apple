<?php

namespace App\Modules\Inventory\Infrastructure;

use App\Exceptions\InventoryConflict;
use App\Modules\Identity\Contracts\AccountAccess;
use App\SharedKernel\ActorContext;
use Illuminate\Support\Facades\DB;

final class InventorySettings
{
    public function read(): array
    {
        return ['low_stock_threshold' => (int) (DB::table('inventory_settings')->where('id', 1)->value('low_stock_threshold') ?? config('inventory.low_stock_threshold', 10)),
            'auto_reserve' => true, 'negative_stock_allowed' => false];
    }

    public function save(int $threshold, ActorContext $actor): void
    {
        if ($threshold < 0 || $threshold > 2147483647 || ! app(AccountAccess::class)->administrator($actor)) {
            throw new InventoryConflict('forbidden_actor', 'Cài đặt kho không hợp lệ hoặc thiếu quyền.');
        }
        DB::table('inventory_settings')->upsert([['id' => 1, 'low_stock_threshold' => $threshold,
            'actor_id' => $actor->id, 'updated_at' => now()]], ['id'], ['low_stock_threshold', 'actor_id', 'updated_at']);
    }
}
