<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculatorTests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\DTO\Amount;
use SomeWork\FeeCalculator\DTO\Currency;

final class AmountTest extends TestCase
{
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = new Currency('USD', 2);
    }

    public function testItNormalizesValue(): void
    {
        $amount = new Amount('1', $this->currency);

        self::assertSame('1.00', $amount->getValue());
    }

    public function testItHandlesValuesWithAdditionalZeros(): void
    {
        $amount = new Amount('00.000010000', $this->currency);

        self::assertSame('0.00', $amount->getValue());
    }

    public function testEqualityRequiresSameCurrencyAndValue(): void
    {
        $amount = new Amount('1.00', $this->currency);
        $same = new Amount('1.000', $this->currency);
        $differentCurrency = new Amount('1.00', new Currency('EUR', 2));
        $differentValue = new Amount('2.00', $this->currency);

        self::assertTrue($amount->equals($same));
        self::assertFalse($amount->equals($differentCurrency));
        self::assertFalse($amount->equals($differentValue));
    }
}
