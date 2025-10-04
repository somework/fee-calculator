<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Exception\Helper;

use SomeWork\MonetaryCalculator\Exception\InvalidArgumentException;

class NormalizerException extends InvalidArgumentException implements AmountNormalizerExceptionInterface
{
    public function __construct(
        private readonly string $value,
        private readonly string $reason,
        int $code = self::INVALID_ARGUMENT,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Amount normalization failed for value "%s": %s',
            $value,
            $reason
        );

        parent::__construct($message, $code, $previous);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
