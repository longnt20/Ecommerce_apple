<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    public function update(User $user, CartItem $item): bool
    {
        return $item->cart()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, CartItem $item): bool
    {
        return $this->update($user, $item);
    }
}
