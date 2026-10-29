<?php

namespace App\Services\Pricing;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class QuoteService
{
    public function sku(int $productId, ?int $variantId): array
    {
        $product = Product::where('status', 'published')->where('visibility', 'public')->find($productId);
        $variant = $variantId ? ProductVariant::where('product_id', $productId)->find($variantId) : null;
        if (! $product || ($variantId && ! $variant)) {
            throw ValidationException::withMessages(['items' => 'Sản phẩm/SKU không còn được bán hoặc không thuộc sản phẩm.']);
        }
        try {
            $price = Vnd::amount((string) ($variant ? $variant->price : $product->default_price));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['items' => 'Giá bán VND chưa hợp lệ. Vui lòng liên hệ cửa hàng.']);
        }

        return compact('product', 'variant', 'price');
    }

    public function quote(array $items): array
    {
        $total = 0;
        $lines = [];
        foreach ($items as $item) {
            $line = $this->sku((int) $item['product_id'], (int) $item['variant_id']);
            try {
                $line['total'] = Vnd::line($line['price'], (int) $item['quantity']);
                if ($total > Vnd::MAX - $line['total']) {
                    throw new InvalidArgumentException;
                }
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages(['items' => 'Tổng đơn vượt giới hạn hỗ trợ.']);
            }
            $line['quantity'] = (int) $item['quantity'];
            $total += $line['total'];
            $lines[] = $line;
        }

        return compact('lines', 'total');
    }
}
