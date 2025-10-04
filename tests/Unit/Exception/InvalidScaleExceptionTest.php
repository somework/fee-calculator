<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\Exception\InvalidScaleException;

final class InvalidScaleExceptionTest extends TestCase
{
    public function testItStoresScale(): void
    {
        $exception = new InvalidScaleException(-1);

        self::assertSame(-1, $exception->getScale());
        self::assertSame('Scale -1 is invalid. Scale must be a non-negative integer.', $exception->getMessage());
    }

    public function testItStoresScaleWithPreviousException(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidScaleException(5, $previous);

        self::assertSame(5, $exception->getScale());
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testItHasCorrectCode(): void
    {
        $exception = new InvalidScaleException(0);

        self::assertSame(1001, $exception->getCode());
    }

    public function testItHandlesZeroScale(): void
    {
        $exception = new InvalidScaleException(0);

        self::assertSame(0, $exception->getScale());
        self::assertSame('Scale 0 is invalid. Scale must be a non-negative integer.', $exception->getMessage());
    }

    public function testItHandlesLargeScale(): void
    {
        $exception = new InvalidScaleException(100);

        self::assertSame(100, $exception->getScale());
        self::assertSame('Scale 100 is invalid. Scale must be a non-negative integer.', $exception->getMessage());
    }
}
