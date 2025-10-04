<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Exception;

use SomeWork\MonetaryCalculator\Exception\InvalidArgumentException;
use SomeWork\MonetaryCalculator\Exception\MonetaryCalculatorExceptionInterface;

class ValidationException extends InvalidArgumentException implements ValidationExceptionInterface, MonetaryCalculatorExceptionInterface
{
    public function __construct(
        private string $field,
        private mixed $value,
        private string $rule,
        int $code = self::FIELD_VALIDATION_FAILED,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Validation failed for field "%s" with value "%s": %s',
            $field,
            (string) $value,
            $rule
        );

        parent::__construct($message, $code, $previous);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getRule(): string
    {
        return $this->rule;
    }
}
