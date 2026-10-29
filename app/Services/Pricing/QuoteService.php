<?php

namespace App\Services\Pricing;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Modules\Pricing\Contracts\Pricing;
use App\Modules\Pricing\Contracts\QuoteLine;
use App\Modules\Pricing\Domain\InvalidQuote;
use Illuminate\Validation\ValidationException;

/** @deprecated Legacy Eloquent response adapter; all price rules live in Pricing module. */
class QuoteService
{
    public function __construct(private Pricing $pricing) {}

    private function legacy(QuoteLine $line): array
    {
        return ['product' => Product::findOrFail($line->sku->productId),
            'variant' => $line->sku->variantId ? ProductVariant::findOrFail($line->sku->variantId) : null,
            'price' => $line->price, 'quantity' => $line->quantity, 'total' => $line->total];
    }

    public function sku(int $productId, ?int $variantId): array
    {
        try {
            $line = $this->legacy($this->pricing->sku($productId, $variantId));
            unset($line['quantity'], $line['total']);

            return $line;
        } catch (InvalidQuote $error) {
            throw ValidationException::withMessages(['items' => $error->getMessage()]);
        }
    }

    public function quote(array $items): array
    {
        try {
            $quote = $this->pricing->quote($items);

            return ['lines' => array_map(fn ($line) => $this->legacy($line), $quote->lines), 'total' => $quote->total];
        } catch (InvalidQuote $error) {
            throw ValidationException::withMessages(['items' => $error->getMessage()]);
        }
    }
}
