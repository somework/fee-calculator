<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Helpers;

use SomeWork\MonetaryCalculator\Core\Exception\InvalidScaleException;
use SomeWork\MonetaryCalculator\Exception\Helper\LosePrecisionException;
use SomeWork\MonetaryCalculator\Exception\Helper\NotDecimalStringException;

/**
 * Utility class for normalizing monetary amounts with precise decimal handling.
 *
 * This class provides methods to normalize string representations of monetary values
 * to a specific decimal scale, ensuring consistent precision for financial calculations.
 */
final class AmountNormalizer
{
    private const DECIMAL_PATTERN = '/^-?(?:\d+)(?:\.\d+)?$/';
    private const DECIMAL_SEPARATOR = '.';

    /**
     * Normalizes a monetary value to the specified decimal scale.
     *
     * @param string $value The monetary value as a string (e.g., "123.456")
     * @param int $scale The target decimal scale (e.g., 2 for cents)
     * @return string The normalized value with proper decimal places
     * @throws NotDecimalStringException If the value is not a valid decimal number
     * @throws InvalidScaleException If the scale is negative
     */
    public static function normalize(string $value, int $scale): string
    {
        $numeric = self::sanitizeNumeric($value);

        return self::formatScale($numeric, $scale);
    }

    /**
     * Validates that a value can be represented at the specified scale without precision loss.
     *
     * @param string $value The monetary value as a string
     * @param int $scale The target decimal scale
     * @throws NotDecimalStringException If the value is not a valid decimal number
     * @throws InvalidScaleException If the scale is negative
     * @throws LosePrecisionException If the value cannot be represented at the scale without loss
     */
    public static function enforceScale(string $value, int $scale): void
    {
        $numeric = self::sanitizeNumeric($value);

        $normalized = self::formatScale($numeric, $scale);

        $comparisonScale = max($scale, self::countDecimals($numeric));

        if (0 !== bccomp($normalized, $numeric, $comparisonScale)) {
            throw new LosePrecisionException($numeric, $comparisonScale);
        }
    }

    private static function assertNumeric(string $value): void
    {
        if (preg_match(self::DECIMAL_PATTERN, $value) !== 1) {
            throw new NotDecimalStringException($value);
        }
    }

    private static function sanitizeNumeric(string $value): string
    {
        $trimmed = self::trimWhitespace($value);

        self::assertNumeric($trimmed);

        return ltrim($trimmed, '+');
    }

    private static function trimWhitespace(string $value): string
    {
        return trim($value);
    }

    private static function formatScale(string $value, int $scale): string
    {
        if ($scale < 0) {
            throw new InvalidScaleException($scale);
        }

        return bcadd($value, '0', $scale);
    }

    private static function countDecimals(string $value): int
    {
        $decimalPosition = strpos($value, self::DECIMAL_SEPARATOR);

        if ($decimalPosition === false) {
            return 0;
        }

        $decimalPart = substr($value, $decimalPosition + 1);
        $trimmedDecimalPart = rtrim($decimalPart, '0');

        return '' === $trimmedDecimalPart ? 0 : strlen($trimmedDecimalPart);
    }
}
