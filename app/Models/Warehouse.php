<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
   protected $fillable = ['name','location'];

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }
}
