<?php

namespace App\Reporting;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Read adapter only: stock mutations must continue through StockWriter. */
final class InventoryReadQuery
{
    public function filtered(Request $request): Builder
    {
        return Inventory::query()
            ->when($request->warehouse_id, fn ($q) => $q->where('inventory.warehouse_id', $request->warehouse_id))
            ->when($request->status === 'low_stock', fn ($q) => $q->lowStock())
            ->when($request->status === 'out_of_stock', fn ($q) => $q->outOfStock())
            ->when($request->search, fn ($q) => $q->where(function ($search) use ($request) {
                $search->whereHas('productVariant', fn ($sku) => $sku->where('sku', 'like', "%{$request->search}%"))
                    ->orWhereHas('productVariant.product', fn ($product) => $product->where('name', 'like', "%{$request->search}%"));
            }));
    }

    public function rows(Request $request): \Illuminate\Support\LazyCollection
    {
        $query = $this->filtered($request)->toBase()
            ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory.product_variant_id')
            ->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'inventory.warehouse_id')
            ->select(['inventory.id', 'product_variants.sku', 'products.name as product_name',
                'warehouses.name as warehouse_name', 'inventory.quantity', 'inventory.available_quantity',
                'inventory.reserved_quantity', 'inventory.location']);

        return \Illuminate\Support\LazyCollection::make(function () use ($query) {
            // Bound the scan even if new stock rows keep being inserted during download.
            $lastId = Inventory::max('id') ?? 0;
            yield from $query->where('inventory.id', '<=', $lastId)->lazyById(1000, 'inventory.id', 'id');
        });
    }

    public static function csvText(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+@\-\t\r\n]/', $value) ? "'".$value : $value;
    }
}
