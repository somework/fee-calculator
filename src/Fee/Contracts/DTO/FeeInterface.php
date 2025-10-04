<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Contracts\DTO;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\AmountInterface;

interface FeeInterface
{
    public function getPercent(): string;

    public function getFixed(): ?AmountInterface;
    public function hasFixedAmount(): bool;
}
