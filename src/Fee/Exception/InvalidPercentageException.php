<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Exception;

use SomeWork\MonetaryCalculator\Exception\InvalidArgumentException;
use SomeWork\MonetaryCalculator\Exception\MonetaryCalculatorExceptionInterface;

class InvalidPercentageException extends InvalidArgumentException implements FeeExceptionInterface
{
    public function __construct(
        string $message = 'Invalid percentage value provided.',
        int $code = MonetaryCalculatorExceptionInterface::INVALID_PERCENTAGE,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}