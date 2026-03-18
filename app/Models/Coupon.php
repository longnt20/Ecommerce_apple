<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'discount_max_value',
        'start_date',
        'expire_date',
        'status',
        'max_usage',
        'used_count',
        'specific_course'
    ];
    
    // protected $casts = [
    //     'expire_date' => 'date',
    // ];

    public function scopeSearch($query, $keyword)
    {
        return $query->where('code', 'LIKE', "%{$keyword}%")
            ->orWhere('name', 'LIKE', "%{$keyword}%");
    }

    public function couponUses()
    {
        return $this->hasMany(CouponUse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
