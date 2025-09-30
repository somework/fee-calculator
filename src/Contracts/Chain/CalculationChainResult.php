<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts\Chain;

use SomeWork\FeeCalculator\ValueObject\Amount;

final class CalculationChainResult
{
    private Amount $initialAmount;

    private Amount $finalAmount;

    /** @var list<CalculationChainStepResult> */
    private array $steps;

    /**
     * @param list<CalculationChainStepResult> $steps
     */
    public function __construct(Amount $initialAmount, Amount $finalAmount, array $steps)
    {
        $this->initialAmount = $initialAmount;
        $this->finalAmount = $finalAmount;
        $this->steps = $steps;
    }

    public function getInitialAmount(): Amount
    {
        return $this->initialAmount;
    }

    public function getFinalAmount(): Amount
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
