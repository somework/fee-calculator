<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\StripeStandardCardStrategy;

final class StripeStandardCardStrategyTest extends TestCase
{
    public function testForwardCalculation(): void
    {
        $strategy = new StripeStandardCardStrategy();
        $request = CalculationRequest::forward($strategy->getName(), '100');

        $result = $strategy->calculateForward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('3.2', $result->getFeeAmount());
        self::assertSame('103.2', $result->getTotalAmount());
        self::assertSame(CalculationDirection::FORWARD, $result->getDirection());
    }

    public function testBackwardCalculation(): void
    {
        $strategy = new StripeStandardCardStrategy();
        $request = CalculationRequest::backward($strategy->getName(), '103.2');

        $result = $strategy->calculateBackward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('3.2', $result->getFeeAmount());
        self::assertSame('103.2', $result->getTotalAmount());
        self::assertSame(CalculationDirection::BACKWARD, $result->getDirection());
    }
}
