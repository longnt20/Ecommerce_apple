<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

final class Vnd
{
    // Existing order DECIMAL(12,2); whole VND only, no floating point rounding.
    public const MAX = 9999999999;

    public static function amount(string|int $value): int
    {
        if (! preg_match('/^\d{1,10}(?:\.0{1,2})?$/D', (string) $value)) {
            throw new InvalidArgumentException('Invalid whole VND amount.');
        }

        $amount = (int) $value;
        if ($amount < 1 || $amount > self::MAX) {
            throw new InvalidArgumentException('VND amount outside supported range.');
        }

        return $amount;
    }

    public static function line(int $price, int $quantity): int
    {
        if ($price < 1 || $quantity < 1 || $price > intdiv(self::MAX, $quantity)) {
            throw new InvalidArgumentException('Order amount outside supported range.');
        }

        return $price * $quantity;
    }
}
