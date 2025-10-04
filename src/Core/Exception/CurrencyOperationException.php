<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Core\Exception;

use SomeWork\MonetaryCalculator\Core\Contracts\DTO\CurrencyInterface;
use SomeWork\MonetaryCalculator\Exception\InvalidArgumentException;
use SomeWork\MonetaryCalculator\Helpers\IdentifierNormalizer;

class CurrencyOperationException extends InvalidArgumentException
{
    public function __construct(
        private readonly CurrencyInterface $currency1,
        private readonly CurrencyInterface $currency2,
        private readonly string            $operation,
        ?\Throwable                        $previous = null
    ) {
        $message = sprintf(
            'Cannot perform %s operation between different currencies: %s and %s',
            $operation,
            IdentifierNormalizer::normalize($currency1->getIdentifier()),
            IdentifierNormalizer::normalize($currency2->getIdentifier())
        );

        parent::__construct($message, self::CURRENCY_MISMATCH, $previous);
    }

    public function getCurrency1(): CurrencyInterface
    {
        return $this->currency1;
    }

    public function getCurrency2(): CurrencyInterface
    {
        return $this->currency2;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }
}
