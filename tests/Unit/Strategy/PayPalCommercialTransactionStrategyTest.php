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
     * @return iterable<string, array{
     *     baseInput: string,
     *     expectedForwardBase: string,
     *     expectedBackwardBase: string,
     *     expectedFee: string,
     *     expectedTotal: string,
     *     context: array<string, mixed>
     * }>
     */
    public static function provideCalculations(): iterable
    {
        yield 'typical adjustments' => [
            '100',
            '100.00',
            '100.00',
            '6.58',
            '106.58',
            [
                'cross_border' => true,
                'additional_percentage' => '0.01',
                'additional_fixed_fee' => '0.10',
            ],
        ];

        yield 'no adjustments' => ['50', '50.00', '49.99', '2.23', '52.23', []];

        yield 'zero base corner case' => ['0', '0.00', '0.00', '0.49', '0.49', []];

        yield 'currency conversion adjustments' => [
            '23.45',
            '23.45',
            '23.44',
            '2.29',
            '25.74',
            [
                'cross_border' => true,
                'currency_conversion_percentage' => '0.02',
                'additional_percentage' => '0.005',
                'additional_fixed_fee' => '0.05',
            ],
        ];
    }

    /**
     * @dataProvider provideCalculations
     * @param array<string, mixed> $context
     */
    public function testBidirectionalCalculation(
        string $baseInput,
        string $expectedForwardBase,
        string $expectedBackwardBase,
        string $expectedFee,
        string $expectedTotal,
        array $context
    ): void {
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
