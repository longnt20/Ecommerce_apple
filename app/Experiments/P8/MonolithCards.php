<?php

namespace App\Experiments\P8;

use App\Experiments\P8\Contracts\CatalogCards;
use App\Models\Product;

/** Baseline read for the exact same card fields, not the richer public home endpoint. */
final class MonolithCards implements CatalogCards
{
    public function page(int $afterId = 0, int $limit = 20): array
    {
        return Product::with('variants')->where('status', 'published')->where('visibility', 'public')
            ->where('id', '>', $afterId)->orderBy('id')->limit(max(1, min(100, $limit)))->get()->map(function ($product) {
                $prices = $product->variants->pluck('price')->all();
                usort($prices, fn ($a, $b) => (int) str_replace('.', '', $a) <=> (int) str_replace('.', '', $b));

                return ['product_id' => (int) $product->id, 'name' => $product->name, 'slug' => $product->slug,
                    'category_id' => $product->category_id === null ? null : (int) $product->category_id,
                    'sku_count' => count($prices), 'min_price' => $prices[0] ?? null, 'max_price' => $prices === [] ? null : $prices[count($prices) - 1]];
            })->all();
    }
}
