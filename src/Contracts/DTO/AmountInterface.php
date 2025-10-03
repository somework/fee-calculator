<?php

namespace SomeWork\FeeCalculator\Contracts\DTO;

use SomeWork\FeeCalculator\DTO\Amount;
use SomeWork\FeeCalculator\DTO\Currency;

interface AmountInterface
{
    public function getValue(): string;
    public function getCurrency(): CurrencyInterface;
    public function equals(AmountInterface $amount): bool;
}
