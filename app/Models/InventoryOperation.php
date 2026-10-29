<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryOperation extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['result' => 'array'];
    }
}
