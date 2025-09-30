<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit\Strategy;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Strategy\CompositeFeeStrategy;
use SomeWork\FeeCalculator\Strategy\StripeInternationalSurchargeStrategy;
use SomeWork\FeeCalculator\Strategy\StripeStandardCardStrategy;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class CompositeFeeStrategyTest extends TestCase
{
    /**
     * @return iterable<string, array{
     *     baseInput: string,
     *     expectedBase: string,
     *     expectedFee: string,
     *     expectedTotal: string,
     *     expectedComponents: array<string, array{base: string, fee: string, total: string}>
     * }>
     */
    public static function provideCalculations(): iterable
    {
        yield 'typical amount' => [
            '100',
            '100.00',
            '4.70',
            '104.70',
            [
                'stripe.standard_card' => ['base' => '100.00', 'fee' => '3.20', 'total' => '103.20'],
                'stripe.international_surcharge' => ['base' => '100.00', 'fee' => '1.50', 'total' => '101.50'],
            ],
        ];

        yield 'zero amount corner case' => [
            '0',
            '0.00',
            '0.30',
            '0.30',
            [
                'stripe.standard_card' => ['base' => '0.00', 'fee' => '0.30', 'total' => '0.30'],
                'stripe.international_surcharge' => ['base' => '0.00', 'fee' => '0.00', 'total' => '0.00'],
            ],
        ];

        yield 'fractional amount' => [
            '12.34',
            '12.34',
            '0.83',
            '13.17',
            [
                'stripe.standard_card' => ['base' => '12.34', 'fee' => '0.65', 'total' => '12.99'],
                'stripe.international_surcharge' => ['base' => '12.34', 'fee' => '0.18', 'total' => '12.52'],
            ],
        ];
    }

    /**
     * @dataProvider provideCalculations
     * @param array<string, array{base: string, fee: string, total: string}> $expectedComponents
     */
    public function testBidirectionalCalculation(
        string $baseInput,
        string $expectedBase,
        string $expectedFee,
        string $expectedTotal,
        array $expectedComponents
    ): void {
        $strategy = new CompositeFeeStrategy([
            new StripeStandardCardStrategy(),
            new StripeInternationalSurchargeStrategy(),
        ], 'stripe.bundle');

        $currency = new Currency('USD', 2);

        $forwardRequest = CalculationRequest::forward('stripe.bundle', Amount::fromString($baseInput, $currency));
        $forwardResult = $strategy->calculateForward($forwardRequest);

        self::assertSame($expectedBase, $forwardResult->getBaseAmount()->getValue());
        self::assertSame($expectedFee, $forwardResult->getFeeAmount()->getValue());
        self::assertSame($expectedTotal, $forwardResult->getTotalAmount()->getValue());
        self::assertSame(CalculationDirection::FORWARD, $forwardResult->getDirection());

        $forwardComponents = $forwardResult->getContext()['component_results'] ?? null;
        self::assertIsArray($forwardComponents);
        self::assertCount(count($expectedComponents), $forwardComponents);
        foreach ($expectedComponents as $component => $values) {
            self::assertArrayHasKey($component, $forwardComponents);
            self::assertSame($values['base'], $forwardComponents[$component]['base_amount'] ?? null);
            self::assertSame($values['fee'], $forwardComponents[$component]['fee_amount'] ?? null);
            self::assertSame($values['total'], $forwardComponents[$component]['total_amount'] ?? null);
        }

        $backwardRequest = CalculationRequest::backward('stripe.bundle', Amount::fromString($expectedTotal, $currency));
        $backwardResult = $strategy->calculateBackward($backwardRequest);

        self::assertSame($expectedBase, $backwardResult->getBaseAmount()->getValue());
        self::assertSame($expectedFee, $backwardResult->getFeeAmount()->getValue());
        self::assertSame($expectedTotal, $backwardResult->getTotalAmount()->getValue());
        self::assertSame(CalculationDirection::BACKWARD, $backwardResult->getDirection());

        $backwardComponents = $backwardResult->getContext()['component_results'] ?? null;
        self::assertIsArray($backwardComponents);
        self::assertCount(count($expectedComponents), $backwardComponents);
        foreach ($expectedComponents as $component => $values) {
            self::assertArrayHasKey($component, $backwardComponents);
            self::assertSame($values['base'], $backwardComponents[$component]['base_amount'] ?? null);
            self::assertSame($values['fee'], $backwardComponents[$component]['fee_amount'] ?? null);
            self::assertSame($values['total'], $backwardComponents[$component]['total_amount'] ?? null);
        }
    }

    public function testBackwardThrowsWhenBelowMinimalTotal(): void
    {
        $strategy = new CompositeFeeStrategy([
            new StripeStandardCardStrategy(),
            new StripeInternationalSurchargeStrategy(),
        ], 'stripe.bundle');

        $currency = new Currency('USD', 2);
        $request = CalculationRequest::backward('stripe.bundle', Amount::fromString('0.1', $currency));

        $this->expectException(InvalidArgumentException::class);
        $strategy->calculateBackward($request);
    }
}
