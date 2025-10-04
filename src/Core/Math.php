<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Core;

/**
 * Mathematical operations for monetary calculations with high precision support.
 *
 * Provides methods for percentage calculations and amount operations with
 * configurable precision scales. Uses Math::DEFAULT_SCALE (20) as default for internal calculations
 * to maintain maximum precision, while allowing final results to be rounded
 * to currency-specific scales.
 *
 * Key features:
 * - Percentages are expected in decimal format (e.g., "0.155" for 15.5%)
 * - Internal calculations use scale 20 for maximum precision
 * - Final results are rounded to the appropriate currency scale
 * - Handles edge cases like scale 0 currencies and very small percentages
 * - Simplified API - no conversion needed between formats
 */
class Math
{
    public const DEFAULT_SCALE = 20;

    /**
     * Apply percentage to amount (percentage should be in decimal format, e.g., "0.155" for 15.5%).
     *
     * @param string $amount The base amount value
     * @param string $percentage The percentage in decimal format (e.g., "0.155" for 15.5%)
     * @param int $scale The scale for the calculation (default: 20)
     * @return string The calculated percentage amount
     */
    public static function applyPercentage(string $amount, string $percentage, int $scale = Math::DEFAULT_SCALE): string
    {
        return bcmul($amount, $percentage, $scale);
    }

    /**
     * Calculate percentage amount from base amount.
     *
     * @param string $baseAmount The base amount value
     * @param string $percentage The percentage in decimal format (e.g., "0.155" for 15.5%)
     * @param int $resultScale The scale for the result (default: 20)
     * @return string The calculated percentage amount
     */
    public static function calculatePercentageAmount(
        string $baseAmount,
        string $percentage,
        int $resultScale = Math::DEFAULT_SCALE
    ): string {
        return self::applyPercentage($baseAmount, $percentage, $resultScale);
    }

    /**
     * Calculate one plus percentage (1 + P) for backward calculations.
     *
     * @param string $percentage The percentage in decimal format (e.g., "0.155" for 15.5%)
     * @param int $scale The scale for the calculation (default: 20)
     * @return string The result of 1 + P
     */
    public static function onePlusPercentage(string $percentage, int $scale = Math::DEFAULT_SCALE): string
    {
        return bcadd('1', $percentage, $scale);
    }

    /**
     * Calculate multiplier for backward fee calculations (1 / (1 + P)).
     *
     * @param string $percentage The percentage in decimal format (e.g., "0.155" for 15.5%)
     * @param int $scale The scale for the calculation (default: 20)
     * @return string The multiplier value
     */
    public static function calculateBackwardMultiplier(string $percentage, int $scale = Math::DEFAULT_SCALE): string
    {
        $onePlusPercent = self::onePlusPercentage($percentage, $scale);
        return bcdiv('1', $onePlusPercent, $scale);
    }

    /**
     * Add two amounts with specified scale.
     *
     * @param string $amount1 First amount
     * @param string $amount2 Second amount
     * @param int $scale The scale for the addition (default: 20)
     * @return string The sum
     */
    public static function add(string $amount1, string $amount2, int $scale = Math::DEFAULT_SCALE): string
    {
        return bcadd($amount1, $amount2, $scale);
    }

    /**
     * Subtract two amounts with specified scale.
     *
     * @param string $amount1 First amount (minuend)
     * @param string $amount2 Second amount (subtrahend)
     * @param int $scale The scale for the subtraction (default: 20)
     * @return string The difference
     */
    public static function subtract(string $amount1, string $amount2, int $scale = Math::DEFAULT_SCALE): string
    {
        return bcsub($amount1, $amount2, $scale);
    }

    /**
     * Multiply two amounts with specified scale.
     *
     * @param string $amount1 First amount
     * @param string $amount2 Second amount
     * @param int $scale The scale for the multiplication (default: 20)
     * @return string The product
     */
    public static function multiply(string $amount1, string $amount2, int $scale = Math::DEFAULT_SCALE): string
    {
        return bcmul($amount1, $amount2, $scale);
    }

    /**
     * Divide two amounts with specified scale.
     *
     * @param string $amount1 Dividend
     * @param string $amount2 Divisor
     * @param int $scale The scale for the division (default: 20)
     * @return string The quotient
     */
    public static function divide(string $amount1, string $amount2, int $scale = Math::DEFAULT_SCALE): string
    {
        return bcdiv($amount1, $amount2, $scale);
    }

    /**
     * Get the default scale used by this Math class.
     */
    public static function getDefaultScale(): int
    {
        return self::DEFAULT_SCALE;
    }
}
