<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Exception\DTO;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Exception\DTO\CurrencyExceptionInterface;
use SomeWork\MonetaryCalculator\Exception\DTO\IdentifierEmptyException;

final class IdentifierEmptyExceptionTest extends TestCase
{
    public function testItCreatesExceptionWithCorrectMessage(): void
    {
        $exception = new IdentifierEmptyException();

        self::assertSame('Currency identifier cannot be empty.', $exception->getMessage());
    }

    public function testItCreatesExceptionWithPreviousException(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new IdentifierEmptyException($previous);

        self::assertSame($previous, $exception->getPrevious());
    }

    public function testItHasCorrectCode(): void
    {
        $exception = new IdentifierEmptyException();

        self::assertSame(2001, $exception->getCode());
    }

    public function testItImplementsCurrencyExceptionInterface(): void
    {
        $exception = new IdentifierEmptyException();

        self::assertInstanceOf(CurrencyExceptionInterface::class, $exception);
    }
}
