<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\DTO\Currency;
use SomeWork\MonetaryCalculator\Core\Exception\CurrencyOperationException;

final class CurrencyOperationExceptionTest extends TestCase
{
    public function testItStoresCurrencies(): void
    {
        $usd = new Currency('USD', 2);
        $eur = new Currency('EUR', 2);
        $exception = new CurrencyOperationException($usd, $eur, 'test');

        self::assertSame($usd, $exception->getCurrency1());
        self::assertSame($eur, $exception->getCurrency2());
        self::assertSame('test', $exception->getOperation());
    }

    public function testItStoresCurrenciesWithOperation(): void
    {
        $usd = new Currency('USD', 2);
        $eur = new Currency('EUR', 2);
        $exception = new CurrencyOperationException($usd, $eur, 'addition');

        self::assertSame($usd, $exception->getCurrency1());
        self::assertSame($eur, $exception->getCurrency2());
        self::assertSame('addition', $exception->getOperation());
    }

    public function testItHasCorrectMessageWithOperation(): void
    {
        $usd = new Currency('USD', 2);
        $eur = new Currency('EUR', 2);
        $exception = new CurrencyOperationException($usd, $eur, 'multiplication');

        $expectedMessage = 'Cannot perform multiplication operation between different currencies: USD and EUR';
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    public function testItHasCorrectCode(): void
    {
        $usd = new Currency('USD', 2);
        $eur = new Currency('EUR', 2);
        $exception = new CurrencyOperationException($usd, $eur, 'test');

        self::assertSame(2000, $exception->getCode());
    }
}
