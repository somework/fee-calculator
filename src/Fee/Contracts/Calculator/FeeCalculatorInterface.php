<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Contracts\Calculator;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\AmountInterface;
use SomeWork\MonetaryCalculator\Enum\CalculationDirection;
use SomeWork\MonetaryCalculator\Fee\Contracts\DTO\FeeInterface;

interface FeeCalculatorInterface
{
    public function calculate(
        AmountInterface $amount,
        FeeInterface $fee,
        CalculationDirection $direction = CalculationDirection::FORWARD
    ): AmountInterface;

    public function calculateForward(AmountInterface $amount, FeeInterface $fee): AmountInterface;

    public function calculateBackward(AmountInterface $amount, FeeInterface $fee): AmountInterface;
}
