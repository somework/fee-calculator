<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts\Chain;

use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\ChainStepInputSource;
use SomeWork\FeeCalculator\Exception\ValidationException;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class CalculationChainRequest
{
    private Amount $initialAmount;

    /** @var list<CalculationChainStep> */
    private array $steps;

    public function __construct(Amount $initialAmount, CalculationChainStep ...$steps)
    {
        $this->initialAmount = $initialAmount;

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

    public function getInitialAmount(): Amount
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

    public static function fromString(string $initialAmount, Currency $currency, CalculationChainStep ...$steps): self
    {
        try {
            return new self(Amount::fromString($initialAmount, $currency), ...$steps);
        } catch (\InvalidArgumentException) {
            throw ValidationException::invalidAmount($initialAmount);
        }
    }
}
