<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Currency;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Currency\Currency;

final class CurrencyTest extends TestCase
{
    public function testItStoresBasicData(): void
    {
        $currency = new Currency('usd', 2);

        self::assertSame('USD', $currency->getCode());
        self::assertSame(2, $currency->getPrecision());
    }
}
