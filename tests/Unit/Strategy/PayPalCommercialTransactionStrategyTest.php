<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\PayPalCommercialTransactionStrategy;

final class PayPalCommercialTransactionStrategyTest extends TestCase
{
    public function testForwardCalculationWithAdjustments(): void
    {
        $strategy = new PayPalCommercialTransactionStrategy();
        $request = CalculationRequest::forward($strategy->getName(), '100', [
            'cross_border' => true,
            'additional_percentage' => '0.01',
            'additional_fixed_fee' => '0.10',
        ]);

        $result = $strategy->calculateForward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('6.58', $result->getFeeAmount());
        self::assertSame('106.58', $result->getTotalAmount());
        self::assertSame(CalculationDirection::FORWARD, $result->getDirection());
    }

    public function testBackwardCalculationWithAdjustments(): void
    {
        $strategy = new PayPalCommercialTransactionStrategy();
        $request = CalculationRequest::backward($strategy->getName(), '106.58', [
            'cross_border' => true,
            'additional_percentage' => '0.01',
            'additional_fixed_fee' => '0.10',
        ]);

        $result = $strategy->calculateBackward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('6.58', $result->getFeeAmount());
        self::assertSame('106.58', $result->getTotalAmount());
        self::assertSame(CalculationDirection::BACKWARD, $result->getDirection());
    }
}
