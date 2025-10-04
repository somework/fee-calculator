<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Core\Contracts\DTO;

interface AmountInterface
{
    public function getValue(): string;
    public function getCurrency(): CurrencyInterface;
    public function equals(AmountInterface $amount): bool;
}
