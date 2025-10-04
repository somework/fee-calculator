<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Applicator;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\AmountInterface;
use SomeWork\MonetaryCalculator\Enum\CalculationDirection;
use SomeWork\MonetaryCalculator\Fee\Contracts\Calculator\FeeCalculatorInterface;
use SomeWork\MonetaryCalculator\Fee\Contracts\DTO\FeeInterface;
use SomeWork\MonetaryCalculator\Fee\Contracts\FeeApplyInterface;

class FeeApplicator implements FeeApplyInterface
{
    private FeeCalculatorInterface $calculator;

    public function __construct(FeeCalculatorInterface $calculator)
    {
        $this->calculator = $calculator;
    }

    public function applyForward(AmountInterface $amount, FeeInterface $fee): AmountInterface
    {
        return $this->calculator->calculateForward($amount, $fee);
    }

    public function applyBackward(AmountInterface $amount, FeeInterface $fee): AmountInterface
    {
        return $this->calculator->calculateBackward($amount, $fee);
    }

    public function supportDirection(CalculationDirection $direction): bool
    {
        return true; // Supports both directions
    }
}
