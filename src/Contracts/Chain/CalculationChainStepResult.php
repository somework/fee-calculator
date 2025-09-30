<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts\Chain;

use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Enum\ChainResultSelection;

final class CalculationChainStepResult
{
    private CalculationChainStep $step;

    private string $inputAmount;

    private CalculationResult $result;

    public function __construct(CalculationChainStep $step, string $inputAmount, CalculationResult $result)
    {
        $this->step = $step;
        $this->inputAmount = $inputAmount;
        $this->result = $result;
    }

    public function getStep(): CalculationChainStep
    {
        return $this->step;
    }

    public function getInputAmount(): string
    {
        return $this->inputAmount;
    }

    public function getResult(): CalculationResult
    {
        return $this->result;
    }

    public function getOutputAmount(): string
    {
        return match ($this->step->getOutputSelection()) {
            ChainResultSelection::BASE => $this->result->getBaseAmount(),
            ChainResultSelection::TOTAL => $this->result->getTotalAmount(),
            ChainResultSelection::FEE => $this->result->getFeeAmount(),
        };
    }
}
