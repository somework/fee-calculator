<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Validator;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\AmountInterface;
use SomeWork\MonetaryCalculator\Fee\Contracts\DTO\FeeInterface;
use SomeWork\MonetaryCalculator\Fee\Exception\AmountNotPositiveException;
use SomeWork\MonetaryCalculator\Fee\Exception\InvalidPercentageException;

final class FeeValidator
{
    public static function validatePercentage(string $percent): void
    {
        if (!preg_match('/^-?(?:\d+)(?:\.\d+)?$/', $percent) || bccomp($percent, '0') < 0) {
            throw new InvalidPercentageException($percent);
        }

        // Percentage should be in decimal format and <= 1 (100%)
        if (bccomp($percent, '1') > 0) {
            throw new InvalidPercentageException($percent);
        }
    }

    public static function validateAmountNotNegative(AmountInterface $amount): void
    {
        if (bccomp($amount->getValue(), '0', $amount->getCurrency()->getScale()) < 0) {
            throw new AmountNotPositiveException($amount);
        }
    }

    public static function validateFeeStructure(FeeInterface $fee): void
    {
        self::validatePercentage($fee->getPercent());

        if ($fee->hasFixedAmount()) {
            self::validateAmountNotNegative($fee->getFixed());
        }
    }

    public static function validateCalculationAmount(AmountInterface $amount): void
    {
        if (bccomp($amount->getValue(), '0', $amount->getCurrency()->getScale()) <= 0) {
            throw new AmountNotPositiveException($amount);
        }
    }
}

