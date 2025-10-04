<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Exception\Helper;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Exception\Helper\NormalizerException;

final class NormalizerExceptionTest extends TestCase
{
    public function testItStoresValueAndReason(): void
    {
        $exception = new NormalizerException('123.456', 'Test reason');

        self::assertSame('123.456', $exception->getValue());
        self::assertSame('Test reason', $exception->getReason());
    }

    public function testItHasCorrectMessage(): void
    {
        $exception = new NormalizerException('123.456', 'Test reason');

        $expectedMessage = 'Amount normalization failed for value "123.456": Test reason';
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    public function testItHasCorrectCode(): void
    {
        $exception = new NormalizerException('123.456', 'Test reason', 2001);

        self::assertSame(2001, $exception->getCode());
    }

    public function testItDefaultsToInvalidArgumentCode(): void
    {
        $exception = new NormalizerException('123.456', 'Test reason');

        self::assertSame(1000, $exception->getCode());
    }
}
