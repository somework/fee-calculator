<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Fee;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\DTO\Amount;
use SomeWork\MonetaryCalculator\Core\DTO\Currency;
use SomeWork\MonetaryCalculator\Fee\DTO\Fee;
use SomeWork\MonetaryCalculator\Enum\CalculationDirection;
use SomeWork\MonetaryCalculator\Fee\Applicator\FeeApplicator;
use SomeWork\MonetaryCalculator\Fee\Calculator\FeeCalculator;

final class FeeApplicatorTest extends TestCase
{
    private Currency $usd;
    private FeeCalculator $calculator;
    private FeeApplicator $applicator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usd = new Currency('USD', 2);
        $this->calculator = new FeeCalculator();
        $this->applicator = new FeeApplicator($this->calculator);
    }

    public function testApplyForward(): void
    {
        $amount = new Amount('100.00', $this->usd);
        $fee = new Fee('0.1');

        $result = $this->applicator->applyForward($amount, $fee);

        self::assertSame('110.00', $result->getValue());
        self::assertSame('USD', $result->getCurrency()->getIdentifier());
    }

    public function testApplyBackward(): void
    {
        $amount = new Amount('110.00', $this->usd);
        $fee = new Fee('0.1');

        $result = $this->applicator->applyBackward($amount, $fee);

        self::assertSame('99.00', $result->getValue());
        self::assertSame('USD', $result->getCurrency()->getIdentifier());
    }

    public function testSupportDirection(): void
    {
        self::assertTrue($this->applicator->supportDirection(CalculationDirection::FORWARD));
        self::assertTrue($this->applicator->supportDirection(CalculationDirection::BACKWARD));
    }
}

