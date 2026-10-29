<?php

namespace App\Modules\Catalog\Infrastructure;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Modules\Catalog\Contracts\SellableSku;
use App\Modules\Catalog\Contracts\SellableSkuReader;
use App\Modules\Catalog\Contracts\SkuUnavailable;

final class EloquentSellableSkuReader implements SellableSkuReader
{
    public function find(int $productId, ?int $variantId): SellableSku
    {
        $product = Product::where('status', 'published')->where('visibility', 'public')->find($productId);
        $variant = $variantId ? ProductVariant::where('product_id', $productId)->find($variantId) : null;
        if (! $product || ($variantId && ! $variant)) {
            throw new SkuUnavailable('Sản phẩm/SKU không còn được bán hoặc không thuộc sản phẩm.');
        }

        return new SellableSku($product->id, $product->name, $variant?->id,
            $variant ? $variant->storage.'-'.$variant->color_label : '', (string) ($variant ? $variant->price : $product->default_price));
    }
}
