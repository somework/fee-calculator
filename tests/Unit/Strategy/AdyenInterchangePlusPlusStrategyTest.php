<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\AdyenInterchangePlusPlusStrategy;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class AdyenInterchangePlusPlusStrategyTest extends TestCase
{
    /**
     * @return iterable<string, array{
     *     baseInput: string,
     *     expectedForwardBase: string,
     *     expectedBackwardBase: string,
     *     expectedFee: string,
     *     expectedTotal: string,
     *     context: array<string, string>
     * }>
     */
    public static function provideCalculations(): iterable
    {
        yield 'full interchange components' => [
            '100',
            '100.00',
            '100.00',
            '1.26',
            '101.26',
            [
                'interchange_percentage' => '0.0085',
                'interchange_fixed' => '0.05',
                'scheme_percentage' => '0.0012',
                'scheme_fixed' => '0.02',
                'markup_percentage' => '0.001',
                'markup_fixed' => '0.12',
            ],
        ];

        yield 'custom mix of components' => [
            '200',
            '200.00',
            '200.00',
            '1.85',
            '201.85',
            [
                'interchange_percentage' => '0.005',
                'scheme_percentage' => '0.002',
                'markup_percentage' => '0.0015',
                'interchange_fixed' => '0.10',
                'scheme_fixed' => '0.02',
                'markup_fixed' => '0.03',
            ],
        ];

        yield 'fractional base amount' => [
            '12.34',
            '12.34',
            '12.33',
            '0.10',
            '12.44',
            [
                'interchange_percentage' => '0.0042',
                'scheme_percentage' => '0.0013',
                'markup_percentage' => '0.0005',
                'interchange_fixed' => '0.02',
                'scheme_fixed' => '0',
                'markup_fixed' => '0.015',
            ],
        ];

        yield 'zero base corner case' => [
            '0',
            '0.00',
            '0.00',
            '0.19',
            '0.19',
            [
                'interchange_percentage' => '0.0085',
                'interchange_fixed' => '0.05',
                'scheme_percentage' => '0.0012',
                'scheme_fixed' => '0.02',
                'markup_percentage' => '0.001',
                'markup_fixed' => '0.12',
            ],
        ];
    }

    /**
     * @dataProvider provideCalculations
     * @param array<string, string> $context
     */
    public function testBidirectionalCalculation(
        string $baseInput,
        string $expectedForwardBase,
        string $expectedBackwardBase,
        string $expectedFee,
        string $expectedTotal,
        array $context
    ): void {
        $strategy = new AdyenInterchangePlusPlusStrategy();
        $currency = new Currency('USD', 2);

        $forwardRequest = CalculationRequest::forward(
            $strategy->getName(),
            Amount::fromString($baseInput, $currency),
            $context
        );
        $forwardResult = $strategy->calculateForward($forwardRequest);

        self::assertSame($expectedForwardBase, $forwardResult->getBaseAmount()->getValue());
        self::assertSame($expectedFee, $forwardResult->getFeeAmount()->getValue());
        self::assertSame($expectedTotal, $forwardResult->getTotalAmount()->getValue());
        self::assertSame(CalculationDirection::FORWARD, $forwardResult->getDirection());

        $backwardRequest = CalculationRequest::backward(
            $strategy->getName(),
            Amount::fromString($expectedTotal, $currency),
            $context
        );
        $backwardResult = $strategy->calculateBackward($backwardRequest);

        self::assertSame($expectedBackwardBase, $backwardResult->getBaseAmount()->getValue());
        self::assertSame($expectedFee, $backwardResult->getFeeAmount()->getValue());
        self::assertSame($expectedTotal, $backwardResult->getTotalAmount()->getValue());
        self::assertSame(CalculationDirection::BACKWARD, $backwardResult->getDirection());
    }
}
