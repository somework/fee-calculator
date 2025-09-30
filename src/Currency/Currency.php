<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Currency;

use InvalidArgumentException;

/**
 * @psalm-immutable
 */
final class Currency
{
    private readonly string $code;

    private readonly int $precision;

    public function __construct(string $code, int $precision)
    {
        $code = strtoupper($code);

        if ($code === '') {
            throw new InvalidArgumentException('Currency code cannot be empty.');
        }

        if ($precision < 0) {
            throw new InvalidArgumentException('Currency precision cannot be negative.');
        }

        $this->code = $code;
        $this->precision = $precision;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }
}
