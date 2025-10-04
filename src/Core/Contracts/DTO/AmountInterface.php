<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Core\Contracts\DTO;

use SomeWork\MonetaryCalculator\Core\DTO\Amount;
use SomeWork\MonetaryCalculator\Core\DTO\Currency;

interface AmountInterface
{
    public function getValue(): string;
    public function getCurrency(): CurrencyInterface;
    public function equals(AmountInterface $amount): bool;
}
