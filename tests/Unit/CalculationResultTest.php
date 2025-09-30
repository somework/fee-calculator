<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Enum\CalculationDirection;

final class CalculationResultTest extends TestCase
{
    public function testResultExposesProvidedData(): void
    {
        $result = new CalculationResult('10', '2', '12', CalculationDirection::FORWARD, ['foo' => 'bar']);

        self::assertSame('10', $result->getBaseAmount());
        self::assertSame('2', $result->getFeeAmount());
        self::assertSame('12', $result->getTotalAmount());
        self::assertSame(CalculationDirection::FORWARD, $result->getDirection());
        self::assertSame(['foo' => 'bar'], $result->getContext());
    }

    public function testWithMethodsCloneResult(): void
    {
        $result = new CalculationResult('10', '1', '11', CalculationDirection::BACKWARD);
        $withAmounts = $result->withAmounts('20', '2', '22');
        $withContext = $result->withContext(['bar' => 'baz']);

        self::assertNotSame($result, $withAmounts);
        self::assertSame('20', $withAmounts->getBaseAmount());
        self::assertSame('2', $withAmounts->getFeeAmount());
        self::assertSame('22', $withAmounts->getTotalAmount());
        self::assertSame($result->getDirection(), $withAmounts->getDirection());

        self::assertNotSame($result, $withContext);
        self::assertSame(['bar' => 'baz'], $withContext->getContext());
        self::assertSame('10', $withContext->getBaseAmount());
        self::assertSame($result->getDirection(), $withContext->getDirection());
    }
}
