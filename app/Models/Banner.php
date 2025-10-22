<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'redirect_url',
        'image',
        'content',
        'order',
        'status'
    ];

    public $attributes = [
        'status'=>1,
        'order'=>0,
    ];
}
