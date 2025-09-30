<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Exception;

class StrategyNotFoundException extends FeeCalculatorException
{
    public static function named(string $name): self
    {
        return new self(sprintf('No fee strategy registered with the name "%s".', $name));
    }
}
