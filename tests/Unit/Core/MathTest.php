<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Core;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\Math;

/**
 * Tests for the Math class that handles high-precision mathematical operations
 * for fee calculations with a default scale of 20.
 */
final class MathTest extends TestCase
{
    public function testApplyPercentageWithDefaultScale(): void
    {
        // Test 15.5% of 100 = 15.50
        $result = Math::applyPercentage('100', '0.155');
        self::assertSame('15.50000000000000000000', $result);
    }

    public function testApplyPercentageWithCustomScale(): void
    {
        $result = Math::applyPercentage('100', '0.155', 2);
        self::assertSame('15.50', $result);
    }

    public function testApplyPercentageWithScaleZero(): void
    {
        $result = Math::applyPercentage('100', '0.155', 0);
        self::assertSame('15', $result);
    }

    public function testApplyPercentageEdgeCases(): void
    {
        // Test 0%
        $result = Math::applyPercentage('100', '0');
        self::assertSame('0.00000000000000000000', $result);

        // Test 100%
        $result = Math::applyPercentage('100', '1');
        self::assertSame('100.00000000000000000000', $result);

        // Test small percentage
        $result = Math::applyPercentage('1000', '0.0001');
        self::assertSame('0.10000000000000000000', $result);
    }

    public function testCalculatePercentageAmount(): void
    {
        // Test basic percentage calculation (15.5% of 100 = 15.50)
        $result = Math::calculatePercentageAmount('100.00', '0.155', 2);
        self::assertSame('15.50', $result);

        // Test with different scales
        $result = Math::calculatePercentageAmount('100.00', '0.155', 8);
        self::assertSame('15.50000000', $result);
    }

    public function testOnePlusPercentage(): void
    {
        // Test 1 + 0.155 = 1.155
        $result = Math::onePlusPercentage('0.155');
        self::assertSame('1.15500000000000000000', $result);

        // Test with custom scale
        $result = Math::onePlusPercentage('0.155', 2);
        self::assertSame('1.15', $result);
    }

    public function testCalculateBackwardMultiplier(): void
    {
        // For 15.5% fee, multiplier should be 1 / (1 + 0.155) = 1 / 1.155
        $result = Math::calculateBackwardMultiplier('0.155');
        self::assertSame('0.86580086580086580086', $result);

        // Test with custom scale
        $result = Math::calculateBackwardMultiplier('0.155', 2);
        self::assertSame('0.86', $result);
    }

    public function testAdd(): void
    {
        $result = Math::add('100.50', '25.75');
        self::assertSame('126.25000000000000000000', $result);

        $result = Math::add('100.50', '25.75', 2);
        self::assertSame('126.25', $result);
    }

    public function testSubtract(): void
    {
        $result = Math::subtract('100.50', '25.75');
        self::assertSame('74.75000000000000000000', $result);

        $result = Math::subtract('100.50', '25.75', 2);
        self::assertSame('74.75', $result);
    }

    public function testMultiply(): void
    {
        $result = Math::multiply('100.50', '1.155');
        self::assertSame('116.07750000000000000000', $result);

        $result = Math::multiply('100.50', '1.155', 2);
        self::assertSame('116.07', $result);
    }

    public function testDivide(): void
    {
        $result = Math::divide('100.50', '1.155');
        self::assertSame('87.01298701298701298701', $result);

        $result = Math::divide('100.50', '1.155', 2);
        self::assertSame('87.01', $result);
    }

    public function testGetDefaultScale(): void
    {
        self::assertSame(20, Math::DEFAULT_SCALE);
    }

    public function testConsistentPrecisionAcrossOperations(): void
    {
        // Test that operations maintain precision consistency
        $baseAmount = '100.123456789';
        $percentage = '0.15987654321'; // 15.987654321%

        // Apply percentage directly (no conversion needed)
        $percentAmount = Math::applyPercentage($baseAmount, $percentage, 10);
        self::assertSame('16.0073921656', $percentAmount);

        // Test one plus percentage
        $onePlusPercent = Math::onePlusPercentage($percentage, 10);
        self::assertSame('1.1598765432', $onePlusPercent);

        // Test backward multiplier
        $multiplier = Math::calculateBackwardMultiplier($percentage, 10);
        self::assertSame('0.8621607237', $multiplier);
    }

    /**
     * @dataProvider operationProvider
     */
    public function testAllOperationsMaintainPrecision(string $operation, mixed $args, string $expected): void
    {
        if (!is_array($args)) {
            throw new \InvalidArgumentException("Args must be an array");
        }

        $result = match ($operation) {
            'add' => Math::add($args[0], $args[1], $args[2] ?? Math::DEFAULT_SCALE),
            'subtract' => Math::subtract($args[0], $args[1], $args[2] ?? Math::DEFAULT_SCALE),
            'multiply' => Math::multiply($args[0], $args[1], $args[2] ?? Math::DEFAULT_SCALE),
            'divide' => Math::divide($args[0], $args[1], $args[2] ?? Math::DEFAULT_SCALE),
            'applyPercentage' => Math::applyPercentage($args[0], $args[1], $args[2] ?? Math::DEFAULT_SCALE),
            'onePlusPercentage' => Math::onePlusPercentage($args[0], $args[1] ?? Math::DEFAULT_SCALE),
            'calculateBackwardMultiplier' => Math::calculateBackwardMultiplier($args[0], $args[1] ?? Math::DEFAULT_SCALE),
            default => throw new \InvalidArgumentException("Unknown operation: $operation"),
        };

        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public static function operationProvider(): array
    {
        return [
            'add_basic' => ['add', ['100.50', '25.75', 2], '126.25'],
            'add_high_precision' => ['add', ['100.123456789', '25.987654321', 10], '126.1111111100'],
            'subtract_basic' => ['subtract', ['100.50', '25.75', 2], '74.75'],
            'multiply_basic' => ['multiply', ['100.50', '1.155', 2], '116.07'],
            'divide_basic' => ['divide', ['100.50', '1.155', 2], '87.01'],
            'applyPercentage' => ['applyPercentage', ['100', '0.155', 2], '15.50'],
            'onePlusPercentage' => ['onePlusPercentage', ['0.155', 2], '1.15'],
            'calculateBackwardMultiplier' => ['calculateBackwardMultiplier', ['0.155', 2], '0.86'],
        ];
    }

    public function testDefaultScaleIsUsedWhenNotSpecified(): void
    {
        // Test that default scale is used when not specified
        $result1 = Math::add('1.123456789', '2.987654321');
        $result2 = Math::add('1.123456789', '2.987654321');

        self::assertSame($result1, $result2);
        self::assertStringContainsString('4.111111110', $result1);
    }
}
