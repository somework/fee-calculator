<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\DTO;

use InvalidArgumentException;
use SomeWork\FeeCalculator\Contracts\DTO\CurrencyInterface;

class Currency implements CurrencyInterface
{
    public function __construct(
        protected mixed $identifier,
        protected int $precision
    ) {
        if (!$identifier) {
            throw new InvalidArgumentException('Currency identifier cannot be empty.');
        }

        if ($precision < 0) {
            throw new InvalidArgumentException('Currency precision cannot be negative.');
        }
    }

    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }
}
