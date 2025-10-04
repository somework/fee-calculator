<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Contracts;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\AmountInterface;
use SomeWork\MonetaryCalculator\Enum\CalculationDirection;
use SomeWork\MonetaryCalculator\Fee\Contracts\DTO\FeeInterface;

interface FeeApplyInterface
{
    public function applyForward(AmountInterface $amount, FeeInterface $fee): AmountInterface;

    public function applyBackward(AmountInterface $amount, FeeInterface $fee): AmountInterface;

    public function supportDirection(CalculationDirection $direction): bool;
}
