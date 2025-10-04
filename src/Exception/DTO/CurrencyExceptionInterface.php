<?php

namespace SomeWork\MonetaryCalculator\Exception\DTO;

use SomeWork\MonetaryCalculator\Exception\MonetaryCalculatorExceptionInterface;

interface CurrencyExceptionInterface extends MonetaryCalculatorExceptionInterface
{
    public const IDENTIFIER_EMPTY = self::CURRENCY_IDENTIFIER_EMPTY;
}