<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\StripeStandardCardStrategy;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class StripeStandardCardStrategyTest extends TestCase
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
            'expectedFee' => '3.20',
            'expectedTotal' => '103.20',
        ]];
        yield 'zero amount corner case' => [[
            'baseInput' => '0',
            'expectedForwardBase' => '0.00',
            'expectedBackwardBase' => '0.00',
            'expectedFee' => '0.30',
            'expectedTotal' => '0.30',
        ]];
        yield 'high precision amount' => [[
            'baseInput' => '1234.56',
            'expectedForwardBase' => '1234.56',
            'expectedBackwardBase' => '1234.55',
            'expectedFee' => '36.10',
            'expectedTotal' => '1270.66',
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

        $strategy = new StripeStandardCardStrategy();
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
