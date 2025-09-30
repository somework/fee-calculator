<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Exception;

use SomeWork\FeeCalculator\Enum\CalculationDirection;

class UnsupportedCalculationDirectionException extends FeeCalculatorException
{
    public static function forStrategy(string $strategyName, CalculationDirection $direction): self
    {
        return new self(sprintf('Strategy "%s" does not support "%s" calculations.', $strategyName, $direction->value));
    }
}
