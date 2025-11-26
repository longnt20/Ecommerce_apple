<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewExperience extends Model
{
    protected $fillable = ['review_id', 'criterion', 'rating'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}

