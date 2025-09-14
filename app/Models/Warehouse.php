<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Warehouse extends Model
{
   protected $fillable = [
        'code', 'name', 'type', 'address', 'city', 'district',
        'phone', 'email', 'manager_name', 'manager_phone',
        'is_active', 'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    // Methods
    public function getTotalValue()
    {
        return $this->inventory()
            ->join('product_variants', 'inventory.product_variant_id', '=', 'product_variants.id')
            ->sum(DB::raw('inventory.quantity * product_variants.cost_price'));
    }

    public function getLowStockItems()
    {
        return $this->inventory()
            ->whereRaw('quantity <= min_stock_level')
            ->with('productVariant.product')
            ->get();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
