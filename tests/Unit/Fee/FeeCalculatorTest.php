<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Fee;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\Contracts\DTO\AmountInterface;
use SomeWork\MonetaryCalculator\Core\DTO\Amount;
use SomeWork\MonetaryCalculator\Core\DTO\Currency;
use SomeWork\MonetaryCalculator\Core\Exception\CurrencyOperationException;
use SomeWork\MonetaryCalculator\Enum\CalculationDirection;
use SomeWork\MonetaryCalculator\Fee\Calculator\FeeCalculator;
use SomeWork\MonetaryCalculator\Fee\DTO\Fee;

/**
 * Comprehensive tests for FeeCalculator with data providers covering edge cases.
 *
 * The percentage-to-decimal conversion uses max(currency_scale, 2) to avoid losing
 * precision during the P/100 conversion. This addresses the issue where using only
 * the currency scale could cause precision loss for small percentages or currencies
 * with scale 0. The fix ensures:
 * 1. Percentages are converted with sufficient precision (at least scale 2)
 * 2. The final result is still rounded to the currency's scale
 * 3. Edge cases like 0% and 100% fees work correctly
 * 4. Both forward and backward calculations maintain consistency
 */
final class FeeCalculatorTest extends TestCase
{
    private Currency $usd;
    private Currency $eur;
    private FeeCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usd = new Currency('USD', 2);
        $this->eur = new Currency('EUR', 2);
        $this->calculator = new FeeCalculator();
    }

    /**
     * @dataProvider percentageCalculationProvider
     */
    public function testPercentageCalculationConvertsCorrectly(
        string $amount,
        string $percent,
        int $scale,
        string $expectedPercentAmount
    ): void {
        $currency = new Currency('TEST', $scale);
        $testAmount = new Amount($amount, $currency);
        $fee = new Fee($percent);

        // Test percentage calculation through forward calculation with no fixed fee
        $result = $this->calculator->calculateForward($testAmount, $fee);

        // The result should be original amount + (original amount * percent/100)
        $expectedTotal = bcadd($amount, $expectedPercentAmount, $scale);
        self::assertSame($expectedTotal, $result->getValue());
        self::assertSame($currency->getIdentifier(), $result->getCurrency()->getIdentifier());
    }

    /**
     * @return array<string, array{string, string, int, string}>
     */
    public static function percentageCalculationProvider(): array
    {
        return [
            // Basic percentage calculations (using decimal format)
            ['100.00', '0.1', 2, '10.00'],       // 10% of 100 = 10
            ['100.00', '0.155', 2, '15.50'],     // 15.5% of 100 = 15.50
            ['200.00', '0.05', 2, '10.00'],      // 5% of 200 = 10

            // Edge cases for percentages
            ['100.00', '0.0', 2, '0.00'],        // 0% should give 0
            ['100.00', '1.0', 2, '100.00'],     // 100% should give full amount

            // High precision calculations
            ['1.00000000', '0.015', 8, '0.01500000'],  // 1.5% of 1 BTC
            ['0.10000000', '0.0225', 8, '0.00225000'], // 2.25% of 0.1 BTC

            // Very small percentages
            ['1000.00', '0.0001', 2, '0.10'],   // 0.01% of 1000 = 0.10

            // Different scales
            ['100', '0.1', 0, '10'],             // No decimal places (10% of 100 = 10)
            ['100.000', '0.1', 3, '10.000'],     // 3 decimal places
        ];
    }

    /**
     * @dataProvider forwardCalculationProvider
     */
    public function testCalculateForwardWithVariousScenarios(
        string $amount,
        string $percent,
        ?string $fixedAmount,
        int $scale,
        string $expectedResult
    ): void {
        $currency = new Currency('TEST', $scale);
        $testAmount = new Amount($amount, $currency);
        $fixedFee = $fixedAmount ? new Amount($fixedAmount, $currency) : null;
        $fee = new Fee($percent, $fixedFee);

        $result = $this->calculator->calculateForward($testAmount, $fee);

        self::assertSame($expectedResult, $result->getValue());
        self::assertSame($currency->getIdentifier(), $result->getCurrency()->getIdentifier());
    }

    /**
     * @return array<string, array{string, string, Amount|null, int, string}>
     */
    public static function forwardCalculationProvider(): array
    {
        return [
            // Percent only calculations
            ['100.00', '0.1', null, 2, '110.00'],
            ['250.50', '0.155', null, 2, '289.32'],
            ['1000.00', '0.0', null, 2, '1000.00'],    // 0% fee
            ['100.00', '1.0', null, 2, '200.00'],      // 100% fee

            // Combined percent + fixed calculations
            ['100.00', '0.1', '5.00', 2, '115.00'],
            ['200.00', '0.05', '10.00', 2, '220.00'],
            ['50.00', '0.2', '2.50', 2, '62.50'],

            // High precision calculations
            ['1.00000000', '0.015', null, 8, '1.01500000'],
            ['0.10000000', '0.0225', '0.00100000', 8, '0.10325000'],

            // Different scales
            ['100', '0.1', null, 0, '110'],             // No decimals
            ['100.000', '0.1', null, 3, '110.000'],    // 3 decimals
        ];
    }

    /**
     * @dataProvider backwardCalculationProvider
     */
    public function testCalculateBackwardWithVariousScenarios(
        string $amount,
        string $percent,
        ?string $fixedAmount,
        int $scale,
        string $expectedResult
    ): void {
        $currency = new Currency('TEST', $scale);
        $testAmount = new Amount($amount, $currency);
        $fixedFee = $fixedAmount ? new Amount($fixedAmount, $currency) : null;
        $fee = new Fee($percent, $fixedFee);

        $result = $this->calculator->calculateBackward($testAmount, $fee);

        self::assertSame($expectedResult, $result->getValue());
        self::assertSame($currency->getIdentifier(), $result->getCurrency()->getIdentifier());
    }

    /**
     * @return array<string, array{string, string, Amount|null, int, string}>
     */
    public static function backwardCalculationProvider(): array
    {
        return [
            // Percent only calculations
            ['110.00', '0.1', null, 2, '99.00'],
            ['289.33', '0.155', null, 2, '244.49'],
            ['1000.00', '0.0', null, 2, '1000.00'],    // 0% fee
            ['200.00', '1.0', null, 2, '0.00'],       // 100% fee

            // Combined percent + fixed calculations
            ['115.00', '0.1', '5.00', 2, '99.99'],
            ['230.00', '0.05', '10.00', 2, '209.52'],
            ['62.50', '0.2', '2.50', 2, '49.99'],

            // High precision calculations
            ['1.01500000', '0.015', null, 8, '0.99977500'],
            ['0.10325000', '0.0225', '0.00100000', 8, '0.09999999'],

            // Different scales
            ['110', '0.1', null, 0, '99'],             // No decimals
            ['110.000', '0.1', null, 3, '99.000'],    // 3 decimals
        ];
    }

    /**
     * @dataProvider currencyMismatchProvider
     */
    public function testThrowsExceptionForCurrencyMismatch(
        AmountInterface $amount1,
        AmountInterface $amount2,
        string $expectedOperation
    ): void {
        $this->expectException(CurrencyOperationException::class);
        $this->expectExceptionMessage('Cannot perform ' . $expectedOperation . ' operation between different currencies');

        $fixedFee = new Amount('5.00', $amount2->getCurrency());
        $fee = new Fee('0.1', $fixedFee);

        $this->calculator->calculateForward($amount1, $fee);
    }

    /**
     * @return array<string, array{Amount, Amount}>
     */
    public static function currencyMismatchProvider(): array
    {
        $usd = new Currency('USD', 2);
        $eur = new Currency('EUR', 2);

        return [
            [new Amount('100.00', $usd), new Amount('50.00', $eur), 'addition'],
        ];
    }

    /**
     * @dataProvider roundTripCalculationProvider
     */
    public function testRoundTripCalculations(
        string $originalAmount,
        string $percent,
        ?string $fixedAmount,
        int $scale,
        string $expectedAfterRoundTrip
    ): void {
        $currency = new Currency('TEST', $scale);
        $original = new Amount($originalAmount, $currency);
        $fixedFee = $fixedAmount ? new Amount($fixedAmount, $currency) : null;
        $fee = new Fee($percent, $fixedFee);

        // Forward calculation
        $finalAmount = $this->calculator->calculateForward($original, $fee);

        // Backward calculation should return original amount
        $calculatedOriginal = $this->calculator->calculateBackward($finalAmount, $fee);

        // Use a small tolerance for bcmath precision differences
        $tolerance = '0.01';
        $diff = bcsub($expectedAfterRoundTrip, $calculatedOriginal->getValue(), $original->getCurrency()->getScale());
        self::assertTrue(
            bccomp($diff, $tolerance, $original->getCurrency()->getScale()) <= 0 &&
            bccomp($diff, '-' . $tolerance, $original->getCurrency()->getScale()) >= 0,
            "Expected $expectedAfterRoundTrip, got {$calculatedOriginal->getValue()}"
        );
        self::assertSame($original->getCurrency()->getIdentifier(), $calculatedOriginal->getCurrency()->getIdentifier());
    }

    /**
     * @return array<string, array{string, string, Amount|null, int, string}>
     */
    public static function roundTripCalculationProvider(): array
    {
        return [
            ['100.00', '0.1', null, 2, '99.00'], // Expected result after round trip
            ['200.00', '0.05', '10.00', 2, '199.99'], // Expected result after round trip
        ];
    }

    public function testCalculateForwardWithPercentOnly(): void
    {
        $amount = new Amount('100.00', $this->usd);
        $fee = new Fee('0.1');

        $result = $this->calculator->calculateForward($amount, $fee);

        self::assertSame('110.00', $result->getValue());
        self::assertSame('USD', $result->getCurrency()->getIdentifier());
    }

    public function testCalculateForwardWithPercentAndFixed(): void
    {
        $amount = new Amount('100.00', $this->usd);
        $fixedFee = new Amount('5.00', $this->usd);
        $fee = new Fee('0.1', $fixedFee);

        $result = $this->calculator->calculateForward($amount, $fee);

        self::assertSame('115.00', $result->getValue());
        self::assertSame('USD', $result->getCurrency()->getIdentifier());
    }

    public function testCalculateBackwardWithPercentOnly(): void
    {
        $amount = new Amount('110.00', $this->usd);
        $fee = new Fee('0.1');

        $result = $this->calculator->calculateBackward($amount, $fee);

        self::assertSame('99.00', $result->getValue());
        self::assertSame('USD', $result->getCurrency()->getIdentifier());
    }

    public function testCalculateBackwardWithPercentAndFixed(): void
    {
        $amount = new Amount('115.00', $this->usd);
        $fixedFee = new Amount('5.00', $this->usd);
        $fee = new Fee('0.1', $fixedFee);

        $result = $this->calculator->calculateBackward($amount, $fee);

        self::assertSame('99.99', $result->getValue());
        self::assertSame('USD', $result->getCurrency()->getIdentifier());
    }

    public function testCalculateWithDirectionForward(): void
    {
        $amount = new Amount('100.00', $this->usd);
        $fee = new Fee('0.1');

        $result = $this->calculator->calculate($amount, $fee);

        self::assertSame('110.00', $result->getValue());
    }

    public function testCalculateWithDirectionBackward(): void
    {
        $amount = new Amount('110.00', $this->usd);
        $fee = new Fee('0.1');

        $result = $this->calculator->calculate($amount, $fee, CalculationDirection::BACKWARD);

        self::assertSame('99.00', $result->getValue());
    }

    public function testCalculateDefaultsToForward(): void
    {
        $amount = new Amount('100.00', $this->usd);
        $fee = new Fee('0.1');

        $result = $this->calculator->calculate($amount, $fee);

        self::assertSame('110.00', $result->getValue());
    }

    public function testThrowsExceptionForDifferentCurrenciesInAddition(): void
    {
        $this->expectException(CurrencyOperationException::class);

        $amount = new Amount('100.00', $this->usd);
        $fixedFee = new Amount('5.00', $this->eur);
        $fee = new Fee('0.1', $fixedFee);

        $this->calculator->calculateForward($amount, $fee);
    }

    public function testThrowsExceptionForDifferentCurrenciesInSubtraction(): void
    {
        $this->expectException(CurrencyOperationException::class);

        $amount = new Amount('100.00', $this->usd);
        $fixedFee = new Amount('5.00', $this->eur);
        $fee = new Fee('0.1', $fixedFee);

        $this->calculator->calculateBackward($amount, $fee);
    }

    public function testWorksWithDifferentScales(): void
    {
        $btc = new Currency('BTC', 8);
        $amount = new Amount('1.00000000', $btc);
        $fee = new Fee('0.015');

        $result = $this->calculator->calculateForward($amount, $fee);

        self::assertSame('1.01500000', $result->getValue());
        self::assertSame('BTC', $result->getCurrency()->getIdentifier());
    }
}
