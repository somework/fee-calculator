<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Strategy;

use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Exception\UnsupportedCalculationDirectionException;
use SomeWork\FeeCalculator\ValueObject\Amount;

abstract class AbstractFeeStrategy
{
    private int $scale;

    private int $calculationScale;

    public function __construct(int $scale = 8)
    {
        $this->scale = max(0, $scale);
        $this->calculationScale = $this->scale + 4;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    protected function getCalculationScale(): int
    {
        return $this->calculationScale;
    }

    protected function normalize(string $value): string
    {
        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value === '' ? '0' : $value;
    }

    protected function add(string $left, string $right): string
    {
        return $this->normalize(bcadd($left, $right, $this->calculationScale));
    }

    protected function subtract(string $left, string $right): string
    {
        return $this->normalize(bcsub($left, $right, $this->calculationScale));
    }

    protected function multiply(string $left, string $right): string
    {
        return $this->normalize(bcmul($left, $right, $this->calculationScale));
    }

    protected function divide(string $left, string $right): string
    {
        return $this->normalize(bcdiv($left, $right, $this->calculationScale));
    }

    protected function compare(string $left, string $right): int
    {
        return bccomp($left, $right, $this->calculationScale);
    }

    protected function absolute(string $value): string
    {
        if (str_starts_with($value, '-')) {
            return substr($value, 1) ?: '0';
        }

        return $value;
    }

    protected function ensureDirectionSupported(
        CalculationDirection $direction,
        bool $isSupported,
        string $strategyName
    ): void {
        if (!$isSupported) {
            throw UnsupportedCalculationDirectionException::forStrategy($strategyName, $direction);
        }
    }

    protected function castNumericString(mixed $value, string $key): string
    {
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (!is_string($value) || !preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Context value "%s" must be a numeric string.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $componentContext
     * @return array<string, mixed>
     */
    protected function mergeComponentContext(array $context, array $componentContext): array
    {
        return array_replace($context, $componentContext);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function createForwardResult(
        CalculationRequest $request,
        string $baseAmount,
        string $feeAmount,
        string $totalAmount,
        array $context = []
    ): CalculationResult {
        $currency = $request->getAmount()->getCurrency();

        return new CalculationResult(
            Amount::fromString($this->normalize($baseAmount), $currency),
            Amount::fromString($this->normalize($feeAmount), $currency),
            Amount::fromString($this->normalize($totalAmount), $currency),
            CalculationDirection::FORWARD,
            $this->mergeComponentContext($request->getContext(), $context)
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function createBackwardResult(
        CalculationRequest $request,
        string $baseAmount,
        string $feeAmount,
        string $totalAmount,
        array $context = []
    ): CalculationResult {
        $currency = $request->getAmount()->getCurrency();

        return new CalculationResult(
            Amount::fromString($this->normalize($baseAmount), $currency),
            Amount::fromString($this->normalize($feeAmount), $currency),
            Amount::fromString($this->normalize($totalAmount), $currency),
            CalculationDirection::BACKWARD,
            $this->mergeComponentContext($request->getContext(), $context)
        );
    }
}
