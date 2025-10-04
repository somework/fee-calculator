<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\DTO;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\AmountInterface;
use SomeWork\MonetaryCalculator\Fee\Contracts\DTO\FeeInterface;
use SomeWork\MonetaryCalculator\Fee\Validator\FeeValidator;

class Fee implements FeeInterface
{
    public function __construct(
        private readonly string           $percent,
        private readonly ?AmountInterface $fixed = null
    ) {
        FeeValidator::validatePercentage($percent);
    }

    public function getPercent(): string
    {
        return $this->percent;
    }

    public function getFixed(): ?AmountInterface
    {
        return $this->fixed;
    }

    public function hasFixedAmount(): bool
    {
        return $this->fixed !== null;
    }
}

