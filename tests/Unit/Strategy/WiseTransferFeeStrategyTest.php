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
     * @return iterable<string, list<array{
     *     baseInput: string,
     *     expectedForwardBase: string,
     *     expectedBackwardBase: string,
     *     expectedFee: string,
     *     expectedTotal: string,
     *     context: array<string, string>
     * }>>
     */
    public static function provideCalculations(): iterable
    {
        yield 'customised fees' => [[
            'baseInput' => '200',
            'expectedForwardBase' => '200.00',
            'expectedBackwardBase' => '200.00',
            'expectedFee' => '2.10',
            'expectedTotal' => '202.10',
            'context' => [
                'variable_percentage' => '0.008',
                'fixed_fee' => '0.25',
                'additional_percentage' => '0.001',
                'additional_fixed_fee' => '0.05',
            ],
        ]];

        yield 'defaults only' => [[
            'baseInput' => '50',
            'expectedForwardBase' => '50.00',
            'expectedBackwardBase' => '49.99',
            'expectedFee' => '0.63',
            'expectedTotal' => '50.63',
            'context' => [],
        ]];

        yield 'mixed adjustments' => [[
            'baseInput' => '12.34',
            'expectedForwardBase' => '12.34',
            'expectedBackwardBase' => '12.33',
            'expectedFee' => '0.37',
            'expectedTotal' => '12.71',
            'context' => [
                'variable_percentage' => '0.0075',
                'additional_percentage' => '0.0025',
                'fixed_fee' => '0.20',
                'additional_fixed_fee' => '0.05',
            ],
        ]];

        yield 'zero base corner case' => [[
            'baseInput' => '0',
            'expectedForwardBase' => '0.00',
            'expectedBackwardBase' => '0.00',
            'expectedFee' => '0.31',
            'expectedTotal' => '0.31',
            'context' => [],
        ]];
    }

    /**
     * @dataProvider provideCalculations
     * @param array{
     *     baseInput: string,
     *     expectedForwardBase: string,
     *     expectedBackwardBase: string,
     *     expectedFee: string,
     *     expectedTotal: string,
     *     context: array<string, string>
     * } $case
     */
    public function testBidirectionalCalculation(array $case): void
    {
        [
            'baseInput' => $baseInput,
            'expectedForwardBase' => $expectedForwardBase,
            'expectedBackwardBase' => $expectedBackwardBase,
            'expectedFee' => $expectedFee,
            'expectedTotal' => $expectedTotal,
            'context' => $context,
        ] = $case;

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
