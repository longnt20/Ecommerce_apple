<?php

namespace Tests\Support;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class CommerceFixture
{
    // Synthetic data only. Construction never saves models or imports the SQL dump.
    public static function make(): array
    {
        $users = [];
        foreach ([910001, 910002] as $id) {
            $user = new User;
            $user->forceFill(['id' => $id, 'name' => 'P0 user '.$id, 'email' => $id.'@example.test', 'role' => 'user', 'status' => 'active']);
            $users[] = $user;
        }

        $stocks = [];
        foreach ([910001 => 5, 910002 => 8] as $warehouse => $quantity) {
            $stock = new Inventory;
            $stock->forceFill([
                'warehouse_id' => $warehouse, 'product_variant_id' => 910001,
                'quantity' => $quantity, 'available_quantity' => $quantity - 1,
                'reserved_quantity' => 1, 'incoming_quantity' => 0,
            ]);
            $stocks[] = $stock;
        }

        $cart = new Cart(['user_id' => 910001]);
        $cart->setRelation('items', new Collection([
            new CartItem(['product_id' => 910001, 'product_variant_id' => 910001, 'quantity' => 2, 'price_at_add' => '25000000.00']),
            new CartItem(['product_id' => 910002, 'product_variant_id' => 910002, 'quantity' => 1, 'price_at_add' => '15000000.00']),
        ]));

        return ['users' => $users, 'stocks' => $stocks, 'cart' => $cart];
    }
}
