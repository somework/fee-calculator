<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\StripeInternationalSurchargeStrategy;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class StripeInternationalSurchargeStrategyTest extends TestCase
{
    /**
     * @return iterable<string, list<array{
     *     baseInput: string,
     *     expectedForwardBase: string,
     *     expectedBackwardBase: string,
     *     expectedFee: string,
     *     expectedTotal: string
     * }>>
     */
    public static function provideCalculations(): iterable
    {
        yield 'typical amount' => [[
            'baseInput' => '100',
            'expectedForwardBase' => '100.00',
            'expectedBackwardBase' => '100.00',
            'expectedFee' => '1.50',
            'expectedTotal' => '101.50',
        ]];
        yield 'zero amount corner case' => [[
            'baseInput' => '0',
            'expectedForwardBase' => '0.00',
            'expectedBackwardBase' => '0.00',
            'expectedFee' => '0.00',
            'expectedTotal' => '0.00',
        ]];
        yield 'fractional amount' => [[
            'baseInput' => '12.34',
            'expectedForwardBase' => '12.34',
            'expectedBackwardBase' => '12.33',
            'expectedFee' => '0.18',
            'expectedTotal' => '12.52',
        ]];
    }

    /**
     * @dataProvider provideCalculations
     * @param array{
     *     baseInput: string,
     *     expectedForwardBase: string,
     *     expectedBackwardBase: string,
     *     expectedFee: string,
     *     expectedTotal: string
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
        ] = $case;

        $strategy = new StripeInternationalSurchargeStrategy();
        $currency = new Currency('USD', 2);

        $forwardRequest = CalculationRequest::forward($strategy->getName(), Amount::fromString($baseInput, $currency));
        $forwardResult = $strategy->calculateForward($forwardRequest);

        self::assertSame($expectedForwardBase, $forwardResult->getBaseAmount()->getValue());
        self::assertSame($expectedFee, $forwardResult->getFeeAmount()->getValue());
        self::assertSame($expectedTotal, $forwardResult->getTotalAmount()->getValue());
        self::assertSame(CalculationDirection::FORWARD, $forwardResult->getDirection());

        $backwardRequest = CalculationRequest::backward(
            $strategy->getName(),
            Amount::fromString($expectedTotal, $currency)
        );
        $backwardResult = $strategy->calculateBackward($backwardRequest);

        self::assertSame($expectedBackwardBase, $backwardResult->getBaseAmount()->getValue());
        self::assertSame($expectedFee, $backwardResult->getFeeAmount()->getValue());
        self::assertSame($expectedTotal, $backwardResult->getTotalAmount()->getValue());
        self::assertSame(CalculationDirection::BACKWARD, $backwardResult->getDirection());
    }
}
