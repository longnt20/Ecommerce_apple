<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\CommerceFixture;

class CommerceBaselineTest extends TestCase
{
    public function test_existing_cart_calculates_quantity_and_total_from_loaded_items(): void
    {
        $fixture = CommerceFixture::make();
        $this->assertEquals(3, $fixture['cart']->totalQuantity());
        $this->assertEquals(65000000, $fixture['cart']->totalPrice());
        $this->assertEquals(50000000, $fixture['cart']->items[0]->total);
    }

    public function test_synthetic_baseline_has_two_actors_and_two_distinct_warehouse_balances(): void
    {
        $fixture = CommerceFixture::make();
        $this->assertNotSame($fixture['users'][0]->id, $fixture['users'][1]->id);
        $this->assertNotSame($fixture['stocks'][0]->warehouse_id, $fixture['stocks'][1]->warehouse_id);
        foreach ($fixture['stocks'] as $stock) {
            $this->assertFalse($stock->exists);
            $this->assertSame($stock->quantity, $stock->available_quantity + $stock->reserved_quantity);
        }
    }
}
