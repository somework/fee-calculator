<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\ValueObject;

use SomeWork\FeeCalculator\Currency\Currency;

final class Amount
{
    private readonly Currency $currency;

    private readonly string $value;

    public function __construct(Currency $currency, string $value)
    {
        $this->currency = $currency;
        $this->value = AmountNormalizer::normalize($value, $currency->getPrecision());
    }

    public static function fromString(string $value, Currency $currency): self
    {
        return new self($currency, $value);
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $amount): bool
    {
        if ($this->currency->getCode() !== $amount->currency->getCode()) {
            return false;
        }

        return bccomp(
            $this->value,
            $amount->value,
            $this->currency->getPrecision(),
        ) === 0;
    }
}
