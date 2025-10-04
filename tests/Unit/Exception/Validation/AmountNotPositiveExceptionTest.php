<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Exception\Validation;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\DTO\Amount;
use SomeWork\MonetaryCalculator\Core\DTO\Currency;
use SomeWork\MonetaryCalculator\Fee\Exception\AmountNotPositiveException;

final class AmountNotPositiveExceptionTest extends TestCase
{
    public function testItStoresAmount(): void
    {
        $currency = new Currency('USD', 2);
        $amount = new Amount('-100.00', $currency);
        $exception = new AmountNotPositiveException($amount);

        self::assertSame($amount, $exception->getAmount());
        self::assertSame('amount', $exception->getField());
        self::assertSame('Must be positive for calculation operations', $exception->getRule());
    }

    public function testItHasCorrectMessage(): void
    {
        $currency = new Currency('USD', 2);
        $amount = new Amount('-100.00', $currency);
        $exception = new AmountNotPositiveException($amount);

        $expectedMessage = 'Validation failed for field "amount" with value "-100.00": Must be positive for calculation operations';
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    public function testItHasCorrectCode(): void
    {
        $currency = new Currency('USD', 2);
        $amount = new Amount('-100.00', $currency);
        $exception = new AmountNotPositiveException($amount);

        self::assertSame(3002, $exception->getCode()); // AMOUNT_NOT_POSITIVE
    }
}
