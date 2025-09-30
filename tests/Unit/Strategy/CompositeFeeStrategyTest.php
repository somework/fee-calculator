<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\CompositeFeeStrategy;
use SomeWork\FeeCalculator\Strategy\StripeInternationalSurchargeStrategy;
use SomeWork\FeeCalculator\Strategy\StripeStandardCardStrategy;

final class CompositeFeeStrategyTest extends TestCase
{
    public function testForwardAggregatesChildStrategies(): void
    {
        $strategy = new CompositeFeeStrategy([
            new StripeStandardCardStrategy(),
            new StripeInternationalSurchargeStrategy(),
        ], 'stripe.bundle');

        $request = CalculationRequest::forward('stripe.bundle', '100');
        $result = $strategy->calculateForward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('4.7', $result->getFeeAmount());
        self::assertSame('104.7', $result->getTotalAmount());
        self::assertSame(CalculationDirection::FORWARD, $result->getDirection());

        $componentResults = $result->getContext()['component_results'] ?? [];
        self::assertIsArray($componentResults);
        self::assertCount(2, $componentResults);
        self::assertArrayHasKey('stripe.standard_card', $componentResults);
        self::assertArrayHasKey('stripe.international_surcharge', $componentResults);
    }

    public function testBackwardAggregatesChildStrategies(): void
    {
        $strategy = new CompositeFeeStrategy([
            new StripeStandardCardStrategy(),
            new StripeInternationalSurchargeStrategy(),
        ], 'stripe.bundle');

        $request = CalculationRequest::backward('stripe.bundle', '104.7');
        $result = $strategy->calculateBackward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('4.7', $result->getFeeAmount());
        self::assertSame('104.7', $result->getTotalAmount());
        self::assertSame(CalculationDirection::BACKWARD, $result->getDirection());
    }

    public function testBackwardThrowsWhenBelowMinimalTotal(): void
    {
        $strategy = new CompositeFeeStrategy([
            new StripeStandardCardStrategy(),
            new StripeInternationalSurchargeStrategy(),
        ], 'stripe.bundle');

        $request = CalculationRequest::backward('stripe.bundle', '0.1');

        $this->expectException(InvalidArgumentException::class);
        $strategy->calculateBackward($request);
    }
}
