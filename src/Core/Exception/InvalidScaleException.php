<?php

namespace SomeWork\MonetaryCalculator\Core\Exception;

use SomeWork\MonetaryCalculator\Exception\InvalidArgumentException;
use SomeWork\MonetaryCalculator\Exception\MonetaryCalculatorExceptionInterface;

class InvalidScaleException extends InvalidArgumentException
{
    public function __construct(
        private readonly int $scale,
        ?\Throwable          $previous = null
    ) {
        $message = sprintf(
            'Scale %d is invalid. Scale must be a non-negative integer.',
            $scale
        );

        parent::__construct($message, MonetaryCalculatorExceptionInterface::INVALID_SCALE, $previous);
    }

    public function getScale(): int
    {
        return $this->scale;
    }
}