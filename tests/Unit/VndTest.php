<?php

namespace Tests\Unit;

use App\Services\Pricing\Vnd;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VndTest extends TestCase
{
    public function test_exact_whole_vnd_and_boundaries(): void
    {
        $this->assertSame(25000000, Vnd::amount('25000000.00'));
        $this->assertSame(75000000, Vnd::line(25000000, 3));
        $this->assertSame(9999999999, Vnd::line(Vnd::MAX, 1));
    }

    /** @dataProvider invalidAmounts */
    public function test_invalid_prices_do_not_round_silently(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        Vnd::amount($value);
    }

    public static function invalidAmounts(): array
    {
        return array_map(fn ($value) => [$value], ['0', '-1', '10.01', '1e3', '10000000000', '1.000', '']);
    }

    public function test_multiplication_rejects_overflow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Vnd::line(Vnd::MAX, 2);
    }
}
