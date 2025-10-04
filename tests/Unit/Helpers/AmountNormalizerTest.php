<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Exception\Helper\LosePrecisionException;
use SomeWork\MonetaryCalculator\Helpers\AmountNormalizer;

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
        try {
            AmountNormalizer::enforceScale('1.234', 2);
            $this->fail('Expected LosePrecisionException to be thrown');
        } catch (LosePrecisionException $e) {
            self::assertSame('1.234', $e->getValue());
            self::assertSame(3, $e->getScale()); // comparisonScale = max(2, 3) = 3
        }
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
