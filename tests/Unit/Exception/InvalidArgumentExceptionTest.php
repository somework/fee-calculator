<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Exception\InvalidArgumentException;
use SomeWork\MonetaryCalculator\Exception\MonetaryCalculatorExceptionInterface;

final class InvalidArgumentExceptionTest extends TestCase
{
    public function testItCreatesExceptionWithDefaultValues(): void
    {
        $exception = new InvalidArgumentException();

        self::assertSame('', $exception->getMessage());
        self::assertSame(1000, $exception->getCode());
        self::assertNull($exception->getPrevious());
    }

    public function testItCreatesExceptionWithCustomMessage(): void
    {
        $exception = new InvalidArgumentException('Custom message');

        self::assertSame('Custom message', $exception->getMessage());
        self::assertSame(1000, $exception->getCode());
    }

    public function testItCreatesExceptionWithCustomCode(): void
    {
        $exception = new InvalidArgumentException('Message', 2000);

        self::assertSame('Message', $exception->getMessage());
        self::assertSame(2000, $exception->getCode());
    }

    public function testItCreatesExceptionWithPreviousException(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidArgumentException('Message', 1000, $previous);

        self::assertSame($previous, $exception->getPrevious());
    }

    public function testItImplementsMonetaryCalculatorExceptionInterface(): void
    {
        $exception = new InvalidArgumentException();

        self::assertInstanceOf(MonetaryCalculatorExceptionInterface::class, $exception);
    }
}
