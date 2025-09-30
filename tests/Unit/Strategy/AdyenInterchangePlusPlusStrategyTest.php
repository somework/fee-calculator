<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\AdyenInterchangePlusPlusStrategy;

final class AdyenInterchangePlusPlusStrategyTest extends TestCase
{
    /** @var array<string, string> */
    private array $context = [
        'interchange_percentage' => '0.0085',
        'interchange_fixed' => '0.05',
        'scheme_percentage' => '0.0012',
        'scheme_fixed' => '0.02',
        'markup_percentage' => '0.001',
        'markup_fixed' => '0.12',
    ];

    public function testForwardCalculation(): void
    {
        $strategy = new AdyenInterchangePlusPlusStrategy();
        $request = CalculationRequest::forward($strategy->getName(), '100', $this->context);

        $result = $strategy->calculateForward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('1.26', $result->getFeeAmount());
        self::assertSame('101.26', $result->getTotalAmount());
        self::assertSame(CalculationDirection::FORWARD, $result->getDirection());
    }

    public function testBackwardCalculation(): void
    {
        $strategy = new AdyenInterchangePlusPlusStrategy();
        $request = CalculationRequest::backward($strategy->getName(), '101.26', $this->context);

        $result = $strategy->calculateBackward($request);

        self::assertSame('100', $result->getBaseAmount());
        self::assertSame('1.26', $result->getFeeAmount());
        self::assertSame('101.26', $result->getTotalAmount());
        self::assertSame(CalculationDirection::BACKWARD, $result->getDirection());
    }
}
