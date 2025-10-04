<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Fee\Exception;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\AmountInterface;
use SomeWork\MonetaryCalculator\Exception\MonetaryCalculatorExceptionInterface;

class AmountNotPositiveException extends ValidationException
{
    public function __construct(
        private readonly AmountInterface $amount,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            'amount',
            $amount->getValue(),
            'Must be positive for calculation operations',
            MonetaryCalculatorExceptionInterface::AMOUNT_NOT_POSITIVE,
            $previous
        );
    }

    public function getAmount(): AmountInterface
    {
        return $this->amount;
    }
}
