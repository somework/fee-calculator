<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculator\Exception;

/**
 * Base interface for all monetary calculator exceptions.
 *
 * Provides standardized error codes for different types of errors that can occur
 * during monetary calculations, validation, and amount processing.
 */
interface MonetaryCalculatorExceptionInterface extends \Throwable
{
    // Core validation errors (1000-1999)
    public const INVALID_ARGUMENT = 1000;
    public const INVALID_SCALE = 1001;

    // Currency operation errors (2000-2999)
    public const CURRENCY_MISMATCH = 2000;
    public const CURRENCY_IDENTIFIER_EMPTY = 2001;

    // Amount processing errors (3000-3999)
    public const PRECISION_LOSS = 3000;
    public const NOT_DECIMAL_STRING = 3001;
    public const AMOUNT_NOT_POSITIVE = 3002;

    // Validation errors (4000-4999)
    public const FIELD_VALIDATION_FAILED = 4000;
    public const INVALID_PERCENTAGE = 4001;

    // Calculation errors (5000-5999)
    public const CURRENCY_OPERATION_MISMATCH = 5000;
}
