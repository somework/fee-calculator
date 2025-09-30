<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts\Chain;

use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Enum\ChainResultSelection;
use SomeWork\FeeCalculator\Enum\ChainStepInputSource;
use SomeWork\FeeCalculator\Exception\ValidationException;

final class CalculationChainStep
{
    private string $identifier;

    private string $strategyName;

    private CalculationDirection $direction;

    private ChainStepInputSource $inputSource;

    private ChainResultSelection $outputSelection;

    /** @var array<string, mixed> */
    private array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $identifier,
        string $strategyName,
        CalculationDirection $direction,
        ChainStepInputSource $inputSource = ChainStepInputSource::PREVIOUS_OUTPUT,
        ChainResultSelection $outputSelection = ChainResultSelection::TOTAL,
        array $context = []
    ) {
        $identifier = trim($identifier);
        if ($identifier === '') {
            throw ValidationException::emptyChainStepIdentifier();
        }

        $strategyName = trim($strategyName);
        if ($strategyName === '') {
            throw ValidationException::emptyStrategyName();
        }

        $this->identifier = $identifier;
        $this->strategyName = $strategyName;
        $this->direction = $direction;
        $this->inputSource = $inputSource;
        $this->outputSelection = $outputSelection;
        $this->context = $context;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getStrategyName(): string
    {
        return $this->strategyName;
    }

    public function getDirection(): CalculationDirection
    {
        return $this->direction;
    }

    public function getInputSource(): ChainStepInputSource
    {
        return $this->inputSource;
    }

    public function getOutputSelection(): ChainResultSelection
    {
        return $this->outputSelection;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): self
    {
        return new self(
            $this->identifier,
            $this->strategyName,
            $this->direction,
            $this->inputSource,
            $this->outputSelection,
            $context
        );
    }
}
