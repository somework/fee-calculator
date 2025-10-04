<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Core\DTO;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\CurrencyInterface;
use SomeWork\MonetaryCalculator\Exception\DTO\IdentifierEmptyException;
use SomeWork\MonetaryCalculator\Core\Exception\InvalidScaleException;

class Currency implements CurrencyInterface
{
    public function __construct(
        protected mixed $identifier,
        protected int   $scale
    ) {
        if (!$this->identifier) {
            throw new IdentifierEmptyException();
        }

        if ($this->scale < 0) {
            throw new InvalidScaleException($this->scale);
        }
    }

    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function equals(CurrencyInterface $currency): bool
    {
        return $this->getIdentifier() === $currency->getIdentifier();
    }
}
