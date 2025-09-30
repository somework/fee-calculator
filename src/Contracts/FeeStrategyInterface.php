<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts;

use SomeWork\FeeCalculator\Enum\CalculationDirection;

interface FeeStrategyInterface
{
    public function getName(): string;

    public function supportsDirection(CalculationDirection $direction): bool;

    public function calculateForward(CalculationRequest $request): CalculationResult;

    public function calculateBackward(CalculationRequest $request): CalculationResult;
}
