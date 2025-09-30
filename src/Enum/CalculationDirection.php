<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Enum;

enum CalculationDirection: string
{
    case FORWARD = 'forward';
    case BACKWARD = 'backward';

    public function isForward(): bool
    {
        return $this === self::FORWARD;
    }

    public function isBackward(): bool
    {
        return $this === self::BACKWARD;
    }
}
