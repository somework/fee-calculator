<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator;

use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Exception\UnsupportedCalculationDirectionException;
use SomeWork\FeeCalculator\Exception\ValidationException;
use SomeWork\FeeCalculator\Registry\StrategyRegistry;

final class FeeCalculator
{
    private StrategyRegistry $registry;

    private int $scale;

    public function __construct(StrategyRegistry $registry, int $scale = 2)
    {
        if ($scale < 0) {
            throw ValidationException::invalidScale($scale);
        }

        $this->registry = $registry;
        $this->scale = $scale;
    }

    public function calculate(CalculationRequest $request): CalculationResult
    {
        $strategy = $this->registry->get($request->getStrategyName());

        $direction = $request->getDirection();
        if (!$strategy->supportsDirection($direction)) {
            throw UnsupportedCalculationDirectionException::forStrategy($strategy->getName(), $direction);
        }

        $normalizedRequest = $request->withAmount($this->normalize($request->getAmount()));

        $result = match ($direction) {
            CalculationDirection::FORWARD => $strategy->calculateForward($normalizedRequest),
            CalculationDirection::BACKWARD => $strategy->calculateBackward($normalizedRequest),
        };

        return $result->withAmounts(
            $this->normalize($result->getBaseAmount()),
            $this->normalize($result->getFeeAmount()),
            $this->normalize($result->getTotalAmount())
        );
    }

    public function normalizeAmount(string $value): string
    {
        return $this->normalize($value);
    }

    private function normalize(string $value): string
    {
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            throw ValidationException::invalidAmount($value);
        }

        return bcadd($value, '0', $this->scale);
    }
}
