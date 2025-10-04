<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Exception;

use SomeWork\MonetaryCalculator\Exception\InvalidArgumentException;

class ValidationException extends InvalidArgumentException implements ValidationExceptionInterface
{
    public function __construct(
        private readonly string $field,
        private readonly mixed $value,
        private readonly string $rule,
        int $code = self::FIELD_VALIDATION_FAILED,
        ?\Throwable $previous = null
    ) {
        $valueString = match (true) {
            $value === null => 'null',
            is_scalar($value) => (string) $value,
            is_array($value) => json_encode($value),
            is_object($value) => get_class($value),
            default => 'unknown'
        };
        $message = sprintf(
            'Validation failed for field "%s" with value "%s": %s',
            $field,
            $valueString,
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
