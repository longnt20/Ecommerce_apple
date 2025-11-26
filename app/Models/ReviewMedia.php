<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewMedia extends Model
{
    protected $fillable = ['review_id', 'url', 'type'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
