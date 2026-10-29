<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAttempt extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount_vnd' => 'integer', 'expires_at' => 'immutable_datetime'];
    }
}
