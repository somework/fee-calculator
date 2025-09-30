<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\ValueObject;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\ValueObject\Amount;

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
        $amount = new Amount($this->currency, '1');

        self::assertSame('1.00', $amount->getValue());
    }

    public function testItHandlesValuesWithAdditionalZeros(): void
    {
        $amount = new Amount($this->currency, '00.000010000');

        self::assertSame('0.00', $amount->getValue());
    }

    public function testEqualityRequiresSameCurrencyAndValue(): void
    {
        $amount = new Amount($this->currency, '1.00');
        $same = new Amount($this->currency, '1.000');
        $differentCurrency = new Amount(new Currency('EUR', 2), '1.00');
        $differentValue = new Amount($this->currency, '2.00');

        self::assertTrue($amount->equals($same));
        self::assertFalse($amount->equals($differentCurrency));
        self::assertFalse($amount->equals($differentValue));
    }
}
