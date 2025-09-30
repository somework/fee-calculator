<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator;

use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Exception\UnsupportedCalculationDirectionException;
use SomeWork\FeeCalculator\Exception\ValidationException;
use SomeWork\FeeCalculator\Registry\StrategyRegistry;
use SomeWork\FeeCalculator\ValueObject\Amount;
use SomeWork\FeeCalculator\ValueObject\AmountNormalizer;

final class FeeCalculator
{
    private StrategyRegistry $registry;

    private int $legacyScale;

    public function __construct(StrategyRegistry $registry, int $scale = 2)
    {
        if ($scale < 0) {
            throw ValidationException::invalidScale($scale);
        }

        $this->registry = $registry;
        $this->legacyScale = $scale;
    }

    public function calculate(CalculationRequest $request): CalculationResult
    {
        $strategy = $this->registry->get($request->getStrategyName());

        $direction = $request->getDirection();
        if (!$strategy->supportsDirection($direction)) {
            throw UnsupportedCalculationDirectionException::forStrategy($strategy->getName(), $direction);
        }

        $normalizedRequest = $request->withAmount($this->normalizeAmount($request->getAmount()));

        $result = match ($direction) {
            CalculationDirection::FORWARD => $strategy->calculateForward($normalizedRequest),
            CalculationDirection::BACKWARD => $strategy->calculateBackward($normalizedRequest),
        };

        return $result->withAmounts(
            $this->normalizeAmount($result->getBaseAmount()),
            $this->normalizeAmount($result->getFeeAmount()),
            $this->normalizeAmount($result->getTotalAmount())
        );
    }

    public function normalizeAmount(Amount $amount): Amount
    {
        $currency = $amount->getCurrency();
        $normalizedValue = AmountNormalizer::normalize($amount->getValue(), $currency->getPrecision());

        if ($normalizedValue === $amount->getValue()) {
            return $amount;
        }

        return Amount::fromString($normalizedValue, $currency);
    }

    /**
     * @deprecated Use {@see normalizeAmount()} with an {@see Amount} instance instead.
     */
    public function normalizeLegacyAmount(string $value, ?int $scale = null): string
    {
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            throw ValidationException::invalidAmount($value);
        }

        $scaleToUse = $scale ?? $this->legacyScale;

        return bcadd($value, '0', $scaleToUse);
    }
}
