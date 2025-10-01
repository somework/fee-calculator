<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class CalculationResultTest extends TestCase
{
    public function testResultExposesProvidedData(): void
    {
        $currency = new Currency('USD', 2);
        $result = new CalculationResult(
            Amount::fromString('10', $currency),
            Amount::fromString('2', $currency),
            Amount::fromString('12', $currency),
            CalculationDirection::FORWARD,
            ['foo' => 'bar']
        );

        self::assertSame('USD', $result->getBaseAmount()->getCurrency()->getCode());
        self::assertSame('10.00', $result->getBaseAmount()->getValue());
        self::assertSame('2.00', $result->getFeeAmount()->getValue());
        self::assertSame('12.00', $result->getTotalAmount()->getValue());
        self::assertSame(CalculationDirection::FORWARD, $result->getDirection());
        self::assertSame(['foo' => 'bar'], $result->getContext());
    }

    public function testWithMethodsCloneResult(): void
    {
        $currency = new Currency('EUR', 2);
        $result = new CalculationResult(
            Amount::fromString('10', $currency),
            Amount::fromString('1', $currency),
            Amount::fromString('11', $currency),
            CalculationDirection::BACKWARD
        );
        $withAmounts = $result->withAmounts(
            Amount::fromString('20', $currency),
            Amount::fromString('2', $currency),
            Amount::fromString('22', $currency)
        );
        $withContext = $result->withContext(['bar' => 'baz']);

        self::assertNotSame($result, $withAmounts);
        self::assertSame('20.00', $withAmounts->getBaseAmount()->getValue());
        self::assertSame('2.00', $withAmounts->getFeeAmount()->getValue());
        self::assertSame('22.00', $withAmounts->getTotalAmount()->getValue());
        self::assertSame($result->getDirection(), $withAmounts->getDirection());

        self::assertNotSame($result, $withContext);
        self::assertSame(['bar' => 'baz'], $withContext->getContext());
        self::assertSame('10.00', $withContext->getBaseAmount()->getValue());
        self::assertSame($result->getDirection(), $withContext->getDirection());
    }
}
