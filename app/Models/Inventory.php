<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $table = 'inventory';
    
    protected $fillable = [
        'warehouse_id', 'product_variant_id',
        'quantity', 'available_quantity', 'reserved_quantity', 'incoming_quantity',
        'min_stock_level', 'max_stock_level', 'reorder_point', 'reorder_quantity',
        'location', 'bin_code',
        'last_counted_at', 'last_received_at', 'last_shipped_at'
    ];

    protected $casts = [
        'last_counted_at' => 'datetime',
        'last_received_at' => 'datetime',
        'last_shipped_at' => 'datetime',
    ];

    // Relations
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Methods
    public function updateQuantity($quantity, $type = 'adjust')
    {
        $this->quantity += $quantity;
        $this->available_quantity = $this->quantity - $this->reserved_quantity;
        $this->save();
        
        return $this;
    }

    public function reserve($quantity)
    {
        if ($this->available_quantity >= $quantity) {
            $this->reserved_quantity += $quantity;
            $this->available_quantity -= $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    public function release($quantity)
    {
        $this->reserved_quantity -= $quantity;
        $this->available_quantity += $quantity;
        $this->save();
        return $this;
    }

    // Checks
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_stock_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->available_quantity <= 0;
    }

    public function needsReorder(): bool
    {
        return $this->quantity <= $this->reorder_point;
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= min_stock_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('available_quantity', '<=', 0);
    }
}
