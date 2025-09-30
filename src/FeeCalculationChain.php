<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator;

use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainRequest;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainResult;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStep;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStepResult;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Enum\ChainStepInputSource;
use SomeWork\FeeCalculator\Exception\ValidationException;

final class FeeCalculationChain
{
    private FeeCalculator $calculator;

    public function __construct(FeeCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function calculate(CalculationChainRequest $request): CalculationChainResult
    {
        $stepResults = [];
        $previousResult = null;
        $previousOutputAmount = null;

        foreach ($request->getSteps() as $index => $step) {
            $inputAmount = $this->resolveInputAmount($request, $step, $previousResult, $previousOutputAmount, $index);

            $calculationRequest = new CalculationRequest(
                $step->getStrategyName(),
                $step->getDirection(),
                $inputAmount,
                $step->getContext()
            );

            $result = $this->calculator->calculate($calculationRequest);

            $stepResult = new CalculationChainStepResult($step, $inputAmount, $result);
            $stepResults[] = $stepResult;

            $previousResult = $result;
            $previousOutputAmount = $stepResult->getOutputAmount();
        }

        $initialAmount = $this->calculator->normalizeAmount($request->getInitialAmount());
        $finalAmount = $previousOutputAmount ?? $initialAmount;

        return new CalculationChainResult($initialAmount, $finalAmount, $stepResults);
    }

    private function resolveInputAmount(
        CalculationChainRequest $chainRequest,
        CalculationChainStep $step,
        ?CalculationResult $previousResult,
        ?string $previousOutputAmount,
        int $index
    ): string {
        return match ($step->getInputSource()) {
            ChainStepInputSource::INITIAL => $chainRequest->getInitialAmount(),
            ChainStepInputSource::PREVIOUS_OUTPUT => $this->requirePreviousOutput($previousOutputAmount, $index),
            ChainStepInputSource::PREVIOUS_BASE => $this->requirePreviousResult($previousResult, $index, ChainStepInputSource::PREVIOUS_BASE)->getBaseAmount(),
            ChainStepInputSource::PREVIOUS_TOTAL => $this->requirePreviousResult($previousResult, $index, ChainStepInputSource::PREVIOUS_TOTAL)->getTotalAmount(),
            ChainStepInputSource::PREVIOUS_FEE => $this->requirePreviousResult($previousResult, $index, ChainStepInputSource::PREVIOUS_FEE)->getFeeAmount(),
        };
    }

    private function requirePreviousOutput(?string $output, int $index): string
    {
        if ($output === null) {
            throw ValidationException::missingPreviousStepOutput($index + 1);
        }

        return $output;
    }

    private function requirePreviousResult(?CalculationResult $result, int $index, ChainStepInputSource $source): CalculationResult
    {
        if ($result === null) {
            throw ValidationException::missingPreviousStepResult($index + 1, $source->name);
        }

        return $result;
    }
}
