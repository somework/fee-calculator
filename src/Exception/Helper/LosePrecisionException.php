<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Exception\Helper;

class LosePrecisionException extends NormalizerException
{
    public function __construct(
        private string $value,
        private int $scale,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $value,
            sprintf('Cannot be represented with scale of %d decimal places without losing precision', $scale),
            static::PRECISION_LOSS,
            $previous
        );
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getScale(): int
    {
        return $this->scale;
    }
}
