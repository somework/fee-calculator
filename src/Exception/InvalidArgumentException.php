<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Exception;

class InvalidArgumentException extends \InvalidArgumentException implements MonetaryCalculatorExceptionInterface
{
    public function __construct(
        string $message = "",
        int $code = MonetaryCalculatorExceptionInterface::INVALID_ARGUMENT,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
