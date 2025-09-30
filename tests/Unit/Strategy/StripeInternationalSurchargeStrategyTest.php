<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\StripeInternationalSurchargeStrategy;

final class StripeInternationalSurchargeStrategyTest extends TestCase
{
    public function testForwardCalculation(): void
    {
        $strategy = new StripeInternationalSurchargeStrategy();
        $request = CalculationRequest::forward($strategy->getName(), '100');

        $result = $strategy->calculateForward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('1.5', $result->getFeeAmount());
        self::assertSame('101.5', $result->getTotalAmount());
        self::assertSame(CalculationDirection::FORWARD, $result->getDirection());
    }

    public function testBackwardCalculation(): void
    {
        $strategy = new StripeInternationalSurchargeStrategy();
        $request = CalculationRequest::backward($strategy->getName(), '101.5');

        $result = $strategy->calculateBackward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('1.5', $result->getFeeAmount());
        self::assertSame('101.5', $result->getTotalAmount());
        self::assertSame(CalculationDirection::BACKWARD, $result->getDirection());
    }
}
