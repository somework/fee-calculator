<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts\Chain;

final class CalculationChainResult
{
    private string $initialAmount;

    private string $finalAmount;

    /** @var list<CalculationChainStepResult> */
    private array $steps;

    /**
     * @param list<CalculationChainStepResult> $steps
     */
    public function __construct(string $initialAmount, string $finalAmount, array $steps)
    {
        $this->initialAmount = $initialAmount;
        $this->finalAmount = $finalAmount;
        $this->steps = $steps;
    }

    public function getInitialAmount(): string
    {
        return $this->initialAmount;
    }

    public function getFinalAmount(): string
    {
        return $this->finalAmount;
    }

    /**
     * @return list<CalculationChainStepResult>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getLastStepResult(): ?CalculationChainStepResult
    {
        if ($this->steps === []) {
            return null;
        }

        return $this->steps[array_key_last($this->steps)];
    }
}
