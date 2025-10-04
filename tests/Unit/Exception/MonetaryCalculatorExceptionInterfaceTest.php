<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Exception\MonetaryCalculatorExceptionInterface;

final class MonetaryCalculatorExceptionInterfaceTest extends TestCase
{
    public function testInterfaceDefinesCorrectConstants(): void
    {
        // Core validation errors (1000-1999)
        self::assertSame(1000, MonetaryCalculatorExceptionInterface::INVALID_ARGUMENT);
        self::assertSame(1001, MonetaryCalculatorExceptionInterface::INVALID_SCALE);

        // Currency operation errors (2000-2999)
        self::assertSame(2000, MonetaryCalculatorExceptionInterface::CURRENCY_MISMATCH);
        self::assertSame(2001, MonetaryCalculatorExceptionInterface::CURRENCY_IDENTIFIER_EMPTY);

        // Amount processing errors (3000-3999)
        self::assertSame(3000, MonetaryCalculatorExceptionInterface::PRECISION_LOSS);
        self::assertSame(3001, MonetaryCalculatorExceptionInterface::NOT_DECIMAL_STRING);
        self::assertSame(3002, MonetaryCalculatorExceptionInterface::AMOUNT_NOT_POSITIVE);

        // Validation errors (4000-4999)
        self::assertSame(4000, MonetaryCalculatorExceptionInterface::FIELD_VALIDATION_FAILED);
        self::assertSame(4001, MonetaryCalculatorExceptionInterface::INVALID_PERCENTAGE);

        // Calculation errors (5000-5999)
        self::assertSame(5000, MonetaryCalculatorExceptionInterface::CURRENCY_OPERATION_MISMATCH);
    }

    public function testInterfaceExtendsThrowable(): void
    {
        self::assertTrue(
            interface_exists(MonetaryCalculatorExceptionInterface::class),
            'MonetaryCalculatorExceptionInterface should exist'
        );

        $reflection = new \ReflectionClass(MonetaryCalculatorExceptionInterface::class);
        $parentInterfaces = $reflection->getInterfaceNames();

        self::assertContains(\Throwable::class, $parentInterfaces);
    }
}
