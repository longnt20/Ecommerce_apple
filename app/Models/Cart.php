<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // Tổng số lượng trong giỏ
    public function totalQuantity()
    {
        return $this->items->sum('quantity');
    }

    // Tổng tiền giỏ hàng
    public function totalPrice()
    {
        return $this->items->sum(fn ($item) => $item->quantity * $item->price_at_add);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

