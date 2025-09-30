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
use SomeWork\FeeCalculator\ValueObject\Amount;

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
        $initialAmount = $this->calculator->normalizeAmount($request->getInitialAmount());

        foreach ($request->getSteps() as $index => $step) {
            $inputAmount = $this->resolveInputAmount(
                $request,
                $step,
                $previousResult,
                $previousOutputAmount,
                $index
            );

            $inputAmount = $this->calculator->normalizeAmount($inputAmount);
            $this->assertCurrencyMatches($initialAmount, $inputAmount, $index);

            $calculationRequest = new CalculationRequest(
                $step->getStrategyName(),
                $step->getDirection(),
                $inputAmount,
                $step->getContext()
            );

            $result = $this->calculator->calculate($calculationRequest);
            $this->assertResultCurrencies($result, $initialAmount, $index);

            $stepResult = new CalculationChainStepResult($step, $inputAmount, $result);
            $stepResults[] = $stepResult;

            $previousResult = $result;
            $previousOutputAmount = $stepResult->getOutputAmount();
        }

        $finalAmount = $previousOutputAmount !== null
            ? $this->calculator->normalizeAmount($previousOutputAmount)
            : $initialAmount;

        return new CalculationChainResult($initialAmount, $finalAmount, $stepResults);
    }

    private function resolveInputAmount(
        CalculationChainRequest $chainRequest,
        CalculationChainStep $step,
        ?CalculationResult $previousResult,
        ?Amount $previousOutputAmount,
        int $index
    ): Amount {
        return match ($step->getInputSource()) {
            ChainStepInputSource::INITIAL => $chainRequest->getInitialAmount(),
            ChainStepInputSource::PREVIOUS_OUTPUT => $this->requirePreviousOutput($previousOutputAmount, $index),
            ChainStepInputSource::PREVIOUS_BASE => $this->requirePreviousResult($previousResult, $index, ChainStepInputSource::PREVIOUS_BASE)->getBaseAmount(),
            ChainStepInputSource::PREVIOUS_TOTAL => $this->requirePreviousResult($previousResult, $index, ChainStepInputSource::PREVIOUS_TOTAL)->getTotalAmount(),
            ChainStepInputSource::PREVIOUS_FEE => $this->requirePreviousResult($previousResult, $index, ChainStepInputSource::PREVIOUS_FEE)->getFeeAmount(),
        };
    }

    private function requirePreviousOutput(?Amount $output, int $index): Amount
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

    private function assertCurrencyMatches(Amount $expected, Amount $actual, int $index): void
    {
        if ($expected->getCurrency()->getCode() !== $actual->getCurrency()->getCode()) {
            throw ValidationException::mismatchedStepCurrency(
                $index + 1,
                $expected->getCurrency()->getCode(),
                $actual->getCurrency()->getCode()
            );
        }
    }

    private function assertResultCurrencies(CalculationResult $result, Amount $expected, int $index): void
    {
        $this->assertCurrencyMatches($expected, $result->getBaseAmount(), $index);
        $this->assertCurrencyMatches($expected, $result->getFeeAmount(), $index);
        $this->assertCurrencyMatches($expected, $result->getTotalAmount(), $index);
    }
}
