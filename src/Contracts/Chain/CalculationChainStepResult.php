<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts\Chain;

use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Enum\ChainResultSelection;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class CalculationChainStepResult
{
    private CalculationChainStep $step;

    private Amount $inputAmount;

    private CalculationResult $result;

    public function __construct(CalculationChainStep $step, Amount $inputAmount, CalculationResult $result)
    {
        $this->step = $step;
        $this->inputAmount = $inputAmount;
        $this->result = $result;
    }

    public function getStep(): CalculationChainStep
    {
        return $this->step;
    }

    public function getInputAmount(): Amount
    {
        return $this->inputAmount;
    }

    public function getResult(): CalculationResult
    {
        return $this->result;
    }

    public function getOutputAmount(): Amount
    {
        return match ($this->step->getOutputSelection()) {
            ChainResultSelection::BASE => $this->result->getBaseAmount(),
            ChainResultSelection::TOTAL => $this->result->getTotalAmount(),
            ChainResultSelection::FEE => $this->result->getFeeAmount(),
        };
    }
}
