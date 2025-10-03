<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculatorTests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\DTO\Currency;

final class CurrencyTest extends TestCase
{
    public function testItStoresBasicData(): void
    {
        $currency = new Currency('usd', 2);

        self::assertSame('usd', $currency->getIdentifier());
        self::assertSame(2, $currency->getPrecision());
    }
}
