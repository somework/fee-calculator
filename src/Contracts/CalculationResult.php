<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts;

use SomeWork\FeeCalculator\Enum\CalculationDirection;

final class CalculationResult
{
    private string $baseAmount;

    private string $feeAmount;

    private string $totalAmount;

    private CalculationDirection $direction;

    /** @var array<string, mixed> */
    private array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $baseAmount,
        string $feeAmount,
        string $totalAmount,
        CalculationDirection $direction,
        array $context = []
    ) {
        $this->baseAmount = $baseAmount;
        $this->feeAmount = $feeAmount;
        $this->totalAmount = $totalAmount;
        $this->direction = $direction;
        $this->context = $context;
    }

    public function getBaseAmount(): string
    {
        return $this->baseAmount;
    }

    public function getFeeAmount(): string
    {
        return $this->feeAmount;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getDirection(): CalculationDirection
    {
        return $this->direction;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function withAmounts(string $baseAmount, string $feeAmount, string $totalAmount): self
    {
        return new self($baseAmount, $feeAmount, $totalAmount, $this->direction, $this->context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): self
    {
        return new self($this->baseAmount, $this->feeAmount, $this->totalAmount, $this->direction, $context);
    }
}
