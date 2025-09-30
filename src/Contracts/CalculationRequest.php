<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts;

use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Exception\ValidationException;

final class CalculationRequest
{
    private string $strategyName;

    private CalculationDirection $direction;

    private string $amount;

    /** @var array<string, mixed> */
    private array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $strategyName, CalculationDirection $direction, string $amount, array $context = [])
    {
        $strategyName = trim($strategyName);
        if ($strategyName === '') {
            throw ValidationException::emptyStrategyName();
        }

        $this->strategyName = $strategyName;
        $this->direction = $direction;
        $this->amount = $this->assertNumericString($amount);
        $this->context = $context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function forward(string $strategyName, string $amount, array $context = []): self
    {
        return new self($strategyName, CalculationDirection::FORWARD, $amount, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function backward(string $strategyName, string $amount, array $context = []): self
    {
        return new self($strategyName, CalculationDirection::BACKWARD, $amount, $context);
    }

    public function getStrategyName(): string
    {
        return $this->strategyName;
    }

    public function getDirection(): CalculationDirection
    {
        return $this->direction;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function withAmount(string $amount): self
    {
        return new self($this->strategyName, $this->direction, $amount, $this->context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): self
    {
        return new self($this->strategyName, $this->direction, $this->amount, $context);
    }

    private function assertNumericString(string $amount): string
    {
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $amount)) {
            throw ValidationException::invalidAmount($amount);
        }

        return $amount;
    }
}
