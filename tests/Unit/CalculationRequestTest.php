<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Exception\ValidationException;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class CalculationRequestTest extends TestCase
{
    public function testForwardFactoryCreatesRequest(): void
    {
        $currency = new Currency('USD', 2);
        $request = CalculationRequest::forward('foo', Amount::fromString('10.123', $currency), ['bar' => 'baz']);

        self::assertSame('foo', $request->getStrategyName());
        self::assertSame('10.12', $request->getAmount()->getValue());
        self::assertSame(CalculationDirection::FORWARD, $request->getDirection());
        self::assertSame(['bar' => 'baz'], $request->getContext());
    }

    public function testBackwardFactoryCreatesRequest(): void
    {
        $currency = new Currency('EUR', 2);
        $request = CalculationRequest::backward('bar', Amount::fromString('99', $currency));

        self::assertSame('bar', $request->getStrategyName());
        self::assertSame('99.00', $request->getAmount()->getValue());
        self::assertSame(CalculationDirection::BACKWARD, $request->getDirection());
        self::assertSame([], $request->getContext());
    }

    public function testEmptyStrategyNameIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The strategy name must not be empty.');

        $currency = new Currency('USD', 2);
        CalculationRequest::forward('   ', Amount::fromString('10', $currency));
    }

    public function testWithAmountClonesRequestAndValidates(): void
    {
        $currency = new Currency('USD', 2);
        $original = CalculationRequest::forward('foo', Amount::fromString('10', $currency));
        $updated = $original->withAmount(Amount::fromString('15.5', $currency));

        self::assertNotSame($original, $updated);
        self::assertSame('15.50', $updated->getAmount()->getValue());
        self::assertSame($original->getContext(), $updated->getContext());
    }

    public function testWithContextClonesRequest(): void
    {
        $currency = new Currency('USD', 2);
        $original = CalculationRequest::backward('bar', Amount::fromString('20', $currency));
        $updated = $original->withContext(['foo' => 'bar']);

        self::assertNotSame($original, $updated);
        self::assertSame(['foo' => 'bar'], $updated->getContext());
        self::assertSame('20.00', $updated->getAmount()->getValue());
        self::assertSame($original->getDirection(), $updated->getDirection());
    }
}
