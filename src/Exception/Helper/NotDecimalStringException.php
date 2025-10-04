<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Exception\Helper;

class NotDecimalStringException extends NormalizerException
{
    public function __construct(
        private readonly string $value,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $value,
            'Is not a valid decimal string',
            static::NOT_DECIMAL_STRING,
            $previous
        );
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
