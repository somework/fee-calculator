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
     * @return iterable<string, array{
     *     baseInput: string,
     *     expectedForwardBase: string,
     *     expectedBackwardBase: string,
     *     expectedFee: string,
     *     expectedTotal: string
     * }>
     */
    public static function provideCalculations(): iterable
    {
        yield 'typical amount' => ['100', '100.00', '100.00', '3.20', '103.20'];
        yield 'zero amount corner case' => ['0', '0.00', '0.00', '0.30', '0.30'];
        yield 'high precision amount' => ['1234.56', '1234.56', '1234.55', '36.10', '1270.66'];
    }

    /**
     * @dataProvider provideCalculations
     */
    public function testBidirectionalCalculation(
        string $baseInput,
        string $expectedForwardBase,
        string $expectedBackwardBase,
        string $expectedFee,
        string $expectedTotal
    ): void {
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
