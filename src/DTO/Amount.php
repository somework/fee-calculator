<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\DTO;

use SomeWork\FeeCalculator\Contracts\DTO\AmountInterface;
use SomeWork\FeeCalculator\Contracts\DTO\CurrencyInterface;
use SomeWork\FeeCalculator\Helpers\AmountNormalizer;

class Amount implements AmountInterface
{
    public function __construct(
        protected string $value,
        protected CurrencyInterface $currency
    ) {
        $this->value = AmountNormalizer::normalize($value, $currency->getPrecision());
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCurrency(): CurrencyInterface
    {
        return $this->currency;
    }

    public function equals(AmountInterface $amount): bool
    {
        if ($this->getCurrency()->getIdentifier() !== $amount->getCurrency()->getIdentifier()) {
            return false;
        }

        return 0 === bccomp(
            $this->getValue(),
            $amount->getValue(),
            $this->getCurrency()->getPrecision(),
        );
    }
}
