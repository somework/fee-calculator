<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts\Chain;

use SomeWork\FeeCalculator\Enum\ChainStepInputSource;
use SomeWork\FeeCalculator\Exception\ValidationException;

final class CalculationChainRequest
{
    private string $initialAmount;

    /** @var list<CalculationChainStep> */
    private array $steps;

    public function __construct(string $initialAmount, CalculationChainStep ...$steps)
    {
        $this->initialAmount = $this->assertNumericString($initialAmount);

        if ($steps === []) {
            throw ValidationException::emptyCalculationChain();
        }

        $steps = array_values($steps);

        /** @var list<CalculationChainStep> $steps */

        foreach ($steps as $index => $step) {
            $inputSource = $step->getInputSource();

            if ($index === 0 && $inputSource !== ChainStepInputSource::INITIAL) {
                throw ValidationException::invalidFirstStepInputSource($inputSource->name);
            }

            if ($index > 0 && $inputSource === ChainStepInputSource::INITIAL) {
                throw ValidationException::invalidSubsequentStepInputSource($index + 1, $inputSource->name);
            }
        }

        $this->steps = $steps;
    }

    public function getInitialAmount(): string
    {
        return $this->initialAmount;
    }

    /**
     * @return list<CalculationChainStep>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    private function assertNumericString(string $amount): string
    {
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $amount)) {
            throw ValidationException::invalidAmount($amount);
        }

        return $amount;
    }
}
