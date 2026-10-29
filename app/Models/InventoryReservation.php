<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReservation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime'];
    }

    public function items()
    {
        return $this->hasMany(InventoryReservationItem::class, 'reservation_id');
    }
}
