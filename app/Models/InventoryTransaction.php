<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'transaction_code', 'warehouse_id', 'product_variant_id',
        'from_warehouse_id', 'to_warehouse_id', 'type',
        'quantity', 'before_quantity', 'after_quantity',
        'reference_type', 'reference_id', 'reference_code',
        'unit_cost', 'total_cost', 'reason', 'notes', 'meta_data',
        'created_by', 'approved_by', 'approved_at', 'status'
    ];

    protected $casts = [
        'meta_data' => 'array',
        'approved_at' => 'datetime',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            $transaction->transaction_code = self::generateTransactionCode();
            $transaction->created_by = auth()->id();
        });
    }

    // Generate unique transaction code
    public static function generateTransactionCode()
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastTransaction = self::whereDate('created_at', now())->latest()->first();
        
        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction->transaction_code, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return "{$prefix}-{$date}-{$newNumber}";
    }

    // Relations
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
