<?php

namespace SomeWork\FeeCalculator\Contracts\DTO;

interface CurrencyInterface
{
    public function getIdentifier(): mixed;

    public function getPrecision(): int;
}
