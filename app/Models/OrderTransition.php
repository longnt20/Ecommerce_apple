<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTransition extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
