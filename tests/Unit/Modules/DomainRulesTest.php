<?php

namespace Tests\Unit\Modules;

use App\Exceptions\InventoryConflict;
use App\Modules\Catalog\Contracts\SellableSku;
use App\Modules\Catalog\Contracts\SellableSkuReader;
use App\Modules\Inventory\Domain\StockBalance;
use App\Modules\Payments\Contracts\Attempt;
use App\Modules\Payments\Contracts\PaymentOrder;
use App\Modules\Payments\Domain\PaymentDecision;
use App\Modules\Pricing\Application\QuoteService;
use App\Modules\Pricing\Domain\InvalidQuote;
use App\SharedKernel\ActorContext;
use PHPUnit\Framework\TestCase;

class DomainRulesTest extends TestCase
{
    public function test_price_quote_is_framework_free_and_ignores_submitted_prices(): void
    {
        $catalog = new class implements SellableSkuReader
        {
            public function find(int $productId, ?int $variantId): SellableSku
            {
                return new SellableSku($productId, 'Phone', $variantId, '256GB-black', '25000000.00');
            }
        };
        $quote = (new QuoteService($catalog))->quote([['product_id' => 1, 'variant_id' => 2, 'quantity' => 3, 'price' => 1]]);
        $this->assertSame(75000000, $quote->total);
        $this->assertSame(25000000, $quote->lines[0]->price);
        $this->expectException(\Error::class);
        $quote->total = 1;
    }

    public function test_invalid_catalog_sale_price_is_not_silently_rounded(): void
    {
        $catalog = new class implements SellableSkuReader
        {
            public function find(int $productId, ?int $variantId): SellableSku
            {
                return new SellableSku(1, 'Phone', 2, '', '2.50');
            }
        };
        $this->expectException(InvalidQuote::class);
        (new QuoteService($catalog))->sku(1, 2);
    }

    /** @dataProvider invalidBalances */
    public function test_stock_invariant_without_laravel(int $onHand, int $held, int $available): void
    {
        $this->expectException(InventoryConflict::class);
        new StockBalance($onHand, $held, $available);
    }

    public static function invalidBalances(): array
    {
        return [[-1, 0, -1], [2, 3, -1], [2, -1, 3], [2, 1, 2], [2147483648, 0, 2147483648]];
    }

    public function test_payment_decision_uses_explicit_time_and_never_reactivates_expired_hold(): void
    {
        $now = new \DateTimeImmutable('2026-10-29T10:00:00+07:00');
        $attempt = new Attempt(1, 2, 'ref', 'merchant', 10000, 'VND', 'pending', $now, $now->modify('+15 minutes'));
        $decision = new PaymentDecision;
        $active = new PaymentOrder(2, '10000.00', 'vnpay', 'pending', 'unpaid', 'active', $now->modify('+1 minute'));
        $expired = new PaymentOrder(2, '10000.00', 'vnpay', 'cancelled', 'unpaid', 'expired');
        $this->assertSame('paid', $decision->outcome(true, $attempt, $active, $now));
        $this->assertSame('reconciliation_required', $decision->outcome(true, $attempt, $active, $now->modify('+2 minutes')));
        $this->assertSame('reconciliation_required', $decision->outcome(true, $attempt, $expired, $now));
        $this->assertSame('failed', $decision->outcome(false, $attempt, $expired, $now));
        $this->assertNull(ActorContext::system()->id);
    }
}
