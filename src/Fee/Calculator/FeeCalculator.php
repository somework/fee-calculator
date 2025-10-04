<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Calculator;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\AmountInterface;
use SomeWork\MonetaryCalculator\Core\DTO\Amount;
use SomeWork\MonetaryCalculator\Core\Exception\CurrencyOperationException;
use SomeWork\MonetaryCalculator\Core\Math;
use SomeWork\MonetaryCalculator\Enum\CalculationDirection;
use SomeWork\MonetaryCalculator\Fee\Contracts\Calculator\FeeCalculatorInterface;
use SomeWork\MonetaryCalculator\Fee\Contracts\DTO\FeeInterface;
use SomeWork\MonetaryCalculator\Fee\Validator\FeeValidator;

/**
 * Core calculator for fee operations with support for forward and backward calculations.
 *
 * Handles percentage-based fees, fixed amount fees, and combined fee structures
 * with precise decimal arithmetic using the Math class for high-precision operations.
 * Internal calculations use default scale for maximum precision, while final results
 * are rounded to currency-specific scales. Percentages are expected in decimal format
 * (e.g., "0.155" for 15.5%).
 */
class FeeCalculator implements FeeCalculatorInterface
{
    /**
     * Validates input parameters for fee calculations.
     */
    private function validateInputs(AmountInterface $amount, FeeInterface $fee): void
    {
        FeeValidator::validateFeeStructure($fee);
        FeeValidator::validateCalculationAmount($amount);
    }

    public function calculate(
        AmountInterface $amount,
        FeeInterface $fee,
        CalculationDirection $direction = CalculationDirection::FORWARD
    ): AmountInterface {
        $this->validateInputs($amount, $fee);

        return $direction->isForward()
            ? $this->calculateForward($amount, $fee)
            : $this->calculateBackward($amount, $fee);
    }

    public function calculateForward(AmountInterface $amount, FeeInterface $fee): AmountInterface
    {
        $this->validateInputs($amount, $fee);

        $percentAmount = $this->calculatePercentAmount($amount, $fee);

        if ($fee->getFixed()) {
            $totalFee = $this->addAmounts($percentAmount, $fee->getFixed());
            return $this->addAmounts($amount, $totalFee);
        }

        return $this->addAmounts($amount, $percentAmount);
    }

    public function calculateBackward(AmountInterface $amount, FeeInterface $fee): AmountInterface
    {
        $this->validateInputs($amount, $fee);

        if (!$fee->getFixed()) {
            $percentAmount = $this->calculatePercentAmount($amount, $fee);
            return $this->subtractAmounts($amount, $percentAmount);
        }

        // For backward calculation with fixed amount, we need to solve:
        // amount = original + (original * percent/100) + fixed
        // Let O be original amount, F be fixed amount, P be percent
        // amount = O + (O * P/100) + F
        // amount - F = O * (1 + P/100)
        // O = (amount - F) / (1 + P/100)

        $subtotal = $this->subtractAmounts($amount, $fee->getFixed());

        // Percentage is in decimal format (e.g., "0.155" for 15.5%)
        $multiplier = Math::calculateBackwardMultiplier($fee->getPercent());

        $originalAmount = Math::multiply(
            $subtotal->getValue(),
            $multiplier,
            $amount->getCurrency()->getScale()
        );

        return new Amount($originalAmount, $amount->getCurrency());
    }

    private function calculatePercentAmount(AmountInterface $amount, FeeInterface $fee): AmountInterface
    {
        // Percentage is in decimal format (e.g., "0.155" for 15.5%)
        $percentValue = Math::applyPercentage(
            $amount->getValue(),
            $fee->getPercent(),
            $amount->getCurrency()->getScale()
        );

        return new Amount($percentValue, $amount->getCurrency());
    }

    private function addAmounts(AmountInterface $amount1, AmountInterface $amount2): AmountInterface
    {
        if (!$amount1->getCurrency()->equals($amount2->getCurrency())) {
            throw new CurrencyOperationException($amount1->getCurrency(), $amount2->getCurrency(), 'addition');
        }

        $sum = Math::add(
            $amount1->getValue(),
            $amount2->getValue(),
            $amount1->getCurrency()->getScale()
        );

        return new Amount($sum, $amount1->getCurrency());
    }

    private function subtractAmounts(AmountInterface $amount1, AmountInterface $amount2): AmountInterface
    {
        if (!$amount1->getCurrency()->equals($amount2->getCurrency())) {
            throw new CurrencyOperationException($amount1->getCurrency(), $amount2->getCurrency(), 'subtraction');
        }

        $difference = Math::subtract(
            $amount1->getValue(),
            $amount2->getValue(),
            $amount1->getCurrency()->getScale()
        );

        return new Amount($difference, $amount1->getCurrency());
    }
}
