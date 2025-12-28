<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Frame extends Model
{
    protected $fillable = [
        'name',
        'top_background',
        'bottom_background',
        'ribbon_image',
        'left_decor_image',
        'right_decor_image',
        'is_active',
    ];
}
