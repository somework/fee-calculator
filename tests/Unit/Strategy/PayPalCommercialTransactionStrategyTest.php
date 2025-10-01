<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\PayPalCommercialTransactionStrategy;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class PayPalCommercialTransactionStrategyTest extends TestCase
{
    /**
     * @return iterable<string, list<array{
     *     baseInput: string,
     *     expectedForwardBase: string,
     *     expectedBackwardBase: string,
     *     expectedFee: string,
     *     expectedTotal: string,
     *     context: array<string, mixed>
     * }>>
     */
    public static function provideCalculations(): iterable
    {
        yield 'typical adjustments' => [[
            'baseInput' => '100',
            'expectedForwardBase' => '100.00',
            'expectedBackwardBase' => '100.00',
            'expectedFee' => '6.58',
            'expectedTotal' => '106.58',
            'context' => [
                'cross_border' => true,
                'additional_percentage' => '0.01',
                'additional_fixed_fee' => '0.10',
            ],
        ]];

        yield 'no adjustments' => [[
            'baseInput' => '50',
            'expectedForwardBase' => '50.00',
            'expectedBackwardBase' => '49.99',
            'expectedFee' => '2.23',
            'expectedTotal' => '52.23',
            'context' => [],
        ]];

        yield 'zero base corner case' => [[
            'baseInput' => '0',
            'expectedForwardBase' => '0.00',
            'expectedBackwardBase' => '0.00',
            'expectedFee' => '0.49',
            'expectedTotal' => '0.49',
            'context' => [],
        ]];

        yield 'currency conversion adjustments' => [[
            'baseInput' => '23.45',
            'expectedForwardBase' => '23.45',
            'expectedBackwardBase' => '23.44',
            'expectedFee' => '2.29',
            'expectedTotal' => '25.74',
            'context' => [
                'cross_border' => true,
                'currency_conversion_percentage' => '0.02',
                'additional_percentage' => '0.005',
                'additional_fixed_fee' => '0.05',
            ],
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
     *     context: array<string, mixed>
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

        $strategy = new PayPalCommercialTransactionStrategy();
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
