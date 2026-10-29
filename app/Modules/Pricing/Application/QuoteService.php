<?php

namespace App\Modules\Pricing\Application;

use App\Modules\Catalog\Contracts\SellableSkuReader;
use App\Modules\Catalog\Contracts\SkuUnavailable;
use App\Modules\Pricing\Contracts\Pricing;
use App\Modules\Pricing\Contracts\Quote;
use App\Modules\Pricing\Contracts\QuoteLine;
use App\Modules\Pricing\Domain\InvalidQuote;
use App\SharedKernel\Vnd;

final class QuoteService implements Pricing
{
    public function __construct(private SellableSkuReader $catalog) {}

    public function sku(int $productId, ?int $variantId): QuoteLine
    {
        try {
            $sku = $this->catalog->find($productId, $variantId);
        } catch (SkuUnavailable $error) {
            throw new InvalidQuote($error->getMessage());
        }
        try {
            $price = Vnd::amount($sku->salePrice);
        } catch (\InvalidArgumentException) {
            throw new InvalidQuote('Giá bán VND chưa hợp lệ. Vui lòng liên hệ cửa hàng.');
        }

        return new QuoteLine($sku, 1, $price, $price);
    }

    public function quote(array $items): Quote
    {
        $total = 0;
        $lines = [];
        foreach ($items as $item) {
            $priced = $this->sku((int) $item['product_id'], (int) $item['variant_id']);
            try {
                $lineTotal = Vnd::line($priced->price, (int) $item['quantity']);
                if ($total > Vnd::MAX - $lineTotal) {
                    throw new \InvalidArgumentException;
                }
            } catch (\InvalidArgumentException) {
                throw new InvalidQuote('Tổng đơn vượt giới hạn hỗ trợ.');
            }
            $lines[] = new QuoteLine($priced->sku, (int) $item['quantity'], $priced->price, $lineTotal);
            $total += $lineTotal;
        }

        return new Quote($lines, $total);
    }
}
