<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Enum\CalculationDirection;

final class CalculationDirectionTest extends TestCase
{
    public function testForwardDirection(): void
    {
        $direction = CalculationDirection::FORWARD;

        self::assertSame('forward', $direction->value);
        self::assertTrue($direction->isForward());
        self::assertFalse($direction->isBackward());
    }

    public function testBackwardDirection(): void
    {
        $direction = CalculationDirection::BACKWARD;

        self::assertSame('backward', $direction->value);
        self::assertFalse($direction->isForward());
        self::assertTrue($direction->isBackward());
    }

    public function testEnumCasesExist(): void
    {
        $cases = CalculationDirection::cases();

        self::assertCount(2, $cases);
        self::assertContains(CalculationDirection::FORWARD, $cases);
        self::assertContains(CalculationDirection::BACKWARD, $cases);
    }

    public function testEnumValues(): void
    {
        self::assertSame('forward', CalculationDirection::FORWARD->value);
        self::assertSame('backward', CalculationDirection::BACKWARD->value);
    }
}
