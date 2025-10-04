<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Calculator\Exception;

use SomeWork\MonetaryCalculator\Exception\MonetaryCalculatorExceptionInterface;

interface CalculationExceptionInterface extends MonetaryCalculatorExceptionInterface
{
    public const CURRENCY_OPERATION_MISMATCH = 5000;
}
