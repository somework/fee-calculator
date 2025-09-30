<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\WiseTransferFeeStrategy;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class WiseTransferFeeStrategyTest extends TestCase
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
        yield 'customised fees' => [
            '200',
            '200.00',
            '200.00',
            '2.10',
            '202.10',
            [
                'variable_percentage' => '0.008',
                'fixed_fee' => '0.25',
                'additional_percentage' => '0.001',
                'additional_fixed_fee' => '0.05',
            ],
        ];

        yield 'defaults only' => ['50', '50.00', '49.99', '0.63', '50.63', []];

        yield 'mixed adjustments' => [
            '12.34',
            '12.34',
            '12.33',
            '0.37',
            '12.71',
            [
                'variable_percentage' => '0.0075',
                'additional_percentage' => '0.0025',
                'fixed_fee' => '0.20',
                'additional_fixed_fee' => '0.05',
            ],
        ];

        yield 'zero base corner case' => ['0', '0.00', '0.00', '0.31', '0.31', []];
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
        $strategy = new WiseTransferFeeStrategy();
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
