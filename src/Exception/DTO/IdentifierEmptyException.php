<?php

namespace SomeWork\MonetaryCalculator\Exception\DTO;

use SomeWork\MonetaryCalculator\Exception\InvalidArgumentException;

class IdentifierEmptyException extends InvalidArgumentException implements CurrencyExceptionInterface
{
    public function __construct(
        ?\Throwable $previous = null
    ) {
        parent::__construct('Currency identifier cannot be empty.', self::IDENTIFIER_EMPTY, $previous);
    }
}
