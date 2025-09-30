<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\ValueObject;

use InvalidArgumentException;

final class AmountNormalizer
{
    public static function normalize(string $value, int $scale): string
    {
        $numeric = self::sanitizeNumeric($value);

        return self::formatScale($numeric, $scale);
    }

    public static function enforceScale(string $value, int $scale): void
    {
        $numeric = self::sanitizeNumeric($value);

        $normalized = self::formatScale($numeric, $scale);

        $comparisonScale = max($scale, self::countDecimals($numeric));

        if (bccomp($normalized, $numeric, $comparisonScale) !== 0) {
            throw new InvalidArgumentException(sprintf(
                'Value "%s" cannot be represented with scale of %d decimal places without losing precision.',
                $value,
                $scale,
            ));
        }
    }

    private static function assertNumeric(string $value): void
    {
        if (preg_match('/^-?(?:\d+)(?:\.\d+)?$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('Value "%s" is not a valid decimal string.', $value));
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
            throw new InvalidArgumentException('Scale must be a positive integer.');
        }

        return bcadd($value, '0', $scale);
    }

    private static function countDecimals(string $value): int
    {
        $decimalPosition = strpos($value, '.');

        if ($decimalPosition === false) {
            return 0;
        }

        $decimalPart = substr($value, $decimalPosition + 1);
        $trimmedDecimalPart = rtrim($decimalPart, '0');

        return $trimmedDecimalPart === '' ? 0 : strlen($trimmedDecimalPart);
    }
}
