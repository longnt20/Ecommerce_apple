<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'total_price',
        'shipping_fee',
        'discount_amount',
        'payment_method',
        'payment_status',
        'final_amount',
        'status',
        'transaction_id',
        'fullname',
        'phone',
        'email',
        'address',
        'ward',
        'district',
        'province',
        'note',
        'paid_at',
        'delivered_at',
        'cancelled_at'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    protected $appends = ['status_label'];

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending'   => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'shipping'  => 'Đang giao hàng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default     => 'Không xác định',
        };
    }
}
