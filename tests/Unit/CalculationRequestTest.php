<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Exception\ValidationException;

final class CalculationRequestTest extends TestCase
{
    public function testForwardFactoryCreatesRequest(): void
    {
        $request = CalculationRequest::forward('foo', '10.123', ['bar' => 'baz']);

        self::assertSame('foo', $request->getStrategyName());
        self::assertSame('10.123', $request->getAmount());
        self::assertSame(CalculationDirection::FORWARD, $request->getDirection());
        self::assertSame(['bar' => 'baz'], $request->getContext());
    }

    public function testBackwardFactoryCreatesRequest(): void
    {
        $request = CalculationRequest::backward('bar', '99');

        self::assertSame('bar', $request->getStrategyName());
        self::assertSame('99', $request->getAmount());
        self::assertSame(CalculationDirection::BACKWARD, $request->getDirection());
        self::assertSame([], $request->getContext());
    }

    public function testEmptyStrategyNameIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The strategy name must not be empty.');

        CalculationRequest::forward('   ', '10');
    }

    public function testInvalidAmountIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The provided amount "ten" is not a valid numeric string.');

        CalculationRequest::forward('foo', 'ten');
    }

    public function testWithAmountClonesRequestAndValidates(): void
    {
        $original = CalculationRequest::forward('foo', '10');
        $updated = $original->withAmount('15.5');

        self::assertNotSame($original, $updated);
        self::assertSame('15.5', $updated->getAmount());
        self::assertSame($original->getContext(), $updated->getContext());

        $this->expectException(ValidationException::class);
        $original->withAmount('invalid');
    }

    public function testWithContextClonesRequest(): void
    {
        $original = CalculationRequest::backward('bar', '20');
        $updated = $original->withContext(['foo' => 'bar']);

        self::assertNotSame($original, $updated);
        self::assertSame(['foo' => 'bar'], $updated->getContext());
        self::assertSame('20', $updated->getAmount());
        self::assertSame($original->getDirection(), $updated->getDirection());
    }
}
