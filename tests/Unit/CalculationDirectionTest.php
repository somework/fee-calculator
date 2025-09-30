<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Enum\CalculationDirection;

final class CalculationDirectionTest extends TestCase
{
    public function testDirectionHelpers(): void
    {
        self::assertTrue(CalculationDirection::FORWARD->isForward());
        self::assertFalse(CalculationDirection::FORWARD->isBackward());
        self::assertTrue(CalculationDirection::BACKWARD->isBackward());
        self::assertFalse(CalculationDirection::BACKWARD->isForward());
    }
}
