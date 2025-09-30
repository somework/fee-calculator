<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Contracts;

use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Exception\ValidationException;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class CalculationResult
{
    private Amount $baseAmount;

    private Amount $feeAmount;

    private Amount $totalAmount;

    private CalculationDirection $direction;

    /** @var array<string, mixed> */
    private array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        Amount $baseAmount,
        Amount $feeAmount,
        Amount $totalAmount,
        CalculationDirection $direction,
        array $context = []
    ) {
        $this->baseAmount = $baseAmount;
        $this->feeAmount = $feeAmount;
        $this->totalAmount = $totalAmount;
        $this->direction = $direction;
        $this->context = $context;
    }

    public function getBaseAmount(): Amount
    {
        return $this->baseAmount;
    }

    public function getFeeAmount(): Amount
    {
        return $this->feeAmount;
    }

    public function getTotalAmount(): Amount
    {
        return $this->totalAmount;
    }

    public function getDirection(): CalculationDirection
    {
        return $this->direction;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function withAmounts(Amount $baseAmount, Amount $feeAmount, Amount $totalAmount): self
    {
        return new self($baseAmount, $feeAmount, $totalAmount, $this->direction, $this->context);
    }

    public function withAmountStrings(string $baseAmount, string $feeAmount, string $totalAmount, Currency $currency): self
    {
        return $this->withAmounts(
            self::stringToAmount($baseAmount, $currency),
            self::stringToAmount($feeAmount, $currency),
            self::stringToAmount($totalAmount, $currency)
        );
    }

    private static function stringToAmount(string $value, Currency $currency): Amount
    {
        try {
            return Amount::fromString($value, $currency);
        } catch (\InvalidArgumentException) {
            throw ValidationException::invalidAmount($value);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): self
    {
        return new self($this->baseAmount, $this->feeAmount, $this->totalAmount, $this->direction, $context);
    }
}
