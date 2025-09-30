<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\WiseTransferFeeStrategy;

final class WiseTransferFeeStrategyTest extends TestCase
{
    /** @var array<string, string> */
    private array $context = [
        'variable_percentage' => '0.008',
        'fixed_fee' => '0.25',
        'additional_percentage' => '0.001',
        'additional_fixed_fee' => '0.05',
    ];

    public function testForwardCalculation(): void
    {
        $strategy = new WiseTransferFeeStrategy();
        $request = CalculationRequest::forward($strategy->getName(), '200', $this->context);

        $result = $strategy->calculateForward($request);

        self::assertSame('200', $result->getBaseAmount());
        self::assertSame('2.1', $result->getFeeAmount());
        self::assertSame('202.1', $result->getTotalAmount());
        self::assertSame(CalculationDirection::FORWARD, $result->getDirection());
    }

    public function testBackwardCalculation(): void
    {
        $strategy = new WiseTransferFeeStrategy();
        $request = CalculationRequest::backward($strategy->getName(), '202.1', $this->context);

        $result = $strategy->calculateBackward($request);

        self::assertSame('200', $result->getBaseAmount());
        self::assertSame('2.1', $result->getFeeAmount());
        self::assertSame('202.1', $result->getTotalAmount());
        self::assertSame(CalculationDirection::BACKWARD, $result->getDirection());
    }
}
