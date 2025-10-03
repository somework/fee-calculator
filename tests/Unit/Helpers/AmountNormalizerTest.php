<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculatorTests\Unit\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Helpers\AmountNormalizer;

final class AmountNormalizerTest extends TestCase
{
    public function testItNormalizesWithScale(): void
    {
        self::assertSame('10.50', AmountNormalizer::normalize('10.5', 2));
    }

    public function testItTrimsExcessiveScale(): void
    {
        self::assertSame('1.23', AmountNormalizer::normalize('01.2300', 2));
    }

    public function testItHandlesInsignificantZeros(): void
    {
        self::assertSame('0.00', AmountNormalizer::normalize('00.000010000', 2));
    }

    public function testEnforceScaleRejectsPrecisionLoss(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AmountNormalizer::enforceScale('1.234', 2);
    }

    public function testEnforceScalePassesRepresentableValue(): void
    {
        AmountNormalizer::enforceScale('00.1000', 2);

        $this->addToAssertionCount(1);
    }

    public function testEnforceScaleTreatsTrailingZerosAsInsignificant(): void
    {
        AmountNormalizer::enforceScale('1.2300', 2);

        $this->addToAssertionCount(1);
    }
}
