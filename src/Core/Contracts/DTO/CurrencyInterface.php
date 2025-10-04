<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Core\Contracts\DTO;

interface CurrencyInterface
{
    public function getIdentifier(): mixed;

    public function getScale(): int;

    public function equals(CurrencyInterface $currency): bool;
}
