<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\DTO\Amount;
use SomeWork\MonetaryCalculator\Core\DTO\Currency;
use SomeWork\MonetaryCalculator\Fee\DTO\Fee;
use SomeWork\MonetaryCalculator\Fee\Exception\InvalidPercentageException;

final class FeeTest extends TestCase
{
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currency = new Currency('USD', 2);
    }

    public function testItStoresBasicData(): void
    {
        $fee = new Fee('0.025', null);

        self::assertSame('0.025', $fee->getPercent());
        self::assertNull($fee->getFixed());
        self::assertFalse($fee->hasFixedAmount());
    }

    public function testItStoresFixedAmount(): void
    {
        $fixedAmount = new Amount('10.00', $this->currency);
        $fee = new Fee('0.025', $fixedAmount);

        self::assertSame('0.025', $fee->getPercent());
        self::assertSame($fixedAmount, $fee->getFixed());
        self::assertTrue($fee->hasFixedAmount());
    }

    public function testItRejectsNegativePercent(): void
    {
        $this->expectException(InvalidPercentageException::class);

        new Fee('-1');
    }

    public function testItRejectsPercentOver100(): void
    {
        $this->expectException(InvalidPercentageException::class);

        new Fee('2.0');
    }

    public function testItRejectsInvalidPercentFormat(): void
    {
        $this->expectException(InvalidPercentageException::class);

        new Fee('abc');
    }

    public function testItAcceptsZeroPercent(): void
    {
        $fee = new Fee('0');

        self::assertSame('0', $fee->getPercent());
    }

    public function testItAcceptsMaximumPercent(): void
    {
        $fee = new Fee('1');

        self::assertSame('1', $fee->getPercent());
    }
}

