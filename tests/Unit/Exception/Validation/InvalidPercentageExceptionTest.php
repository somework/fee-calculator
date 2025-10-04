<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Exception\Validation;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Fee\Exception\InvalidPercentageException;

final class InvalidPercentageExceptionTest extends TestCase
{
    public function testItCreatesExceptionWithCorrectMessage(): void
    {
        $exception = new InvalidPercentageException('150.0');

        self::assertSame('150.0', $exception->getMessage());
    }

    public function testItHasCorrectCode(): void
    {
        $exception = new InvalidPercentageException('150.0');

        self::assertSame(4001, $exception->getCode()); // INVALID_PERCENTAGE
    }
}
