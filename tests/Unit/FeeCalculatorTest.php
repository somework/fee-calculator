<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use Closure;
use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Exception\UnsupportedCalculationDirectionException;
use SomeWork\FeeCalculator\Exception\ValidationException;
use SomeWork\FeeCalculator\FeeCalculator;
use SomeWork\FeeCalculator\Registry\StrategyRegistry;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class FeeCalculatorTest extends TestCase
{
    public function testForwardCalculationNormalizesAmounts(): void
    {
        $receivedRequest = null;
        $strategy = $this->createStrategy(
            'percentage',
            supportsForward: true,
            supportsBackward: true,
            forward: function (CalculationRequest $request) use (&$receivedRequest): CalculationResult {
                $receivedRequest = $request;

                $currency = $request->getAmount()->getCurrency();

                return new CalculationResult(
                    Amount::fromString('10.129', $currency),
                    Amount::fromString('0.505', $currency),
                    Amount::fromString('10.634', $currency),
                    CalculationDirection::FORWARD
                );
            },
            backward: fn (CalculationRequest $request): CalculationResult => throw new \LogicException('Should not be called'),
        );

        $registry = new StrategyRegistry([$strategy]);
        $calculator = new FeeCalculator($registry, 2);

        $currency = new Currency('USD', 2);
        $result = $calculator->calculate(CalculationRequest::forward('percentage', Amount::fromString('10.125', $currency)));

        self::assertInstanceOf(CalculationRequest::class, $receivedRequest);
        self::assertSame('10.12', $receivedRequest->getAmount()->getValue(), 'Request amount should be normalized.');

        self::assertSame('10.12', $result->getBaseAmount()->getValue());
        self::assertSame('0.50', $result->getFeeAmount()->getValue());
        self::assertSame('10.63', $result->getTotalAmount()->getValue());
    }

    public function testBackwardCalculationPathIsUsed(): void
    {
        $strategy = $this->createStrategy(
            'flat',
            supportsForward: true,
            supportsBackward: true,
            forward: fn (CalculationRequest $request): CalculationResult => throw new \LogicException('Should not be called'),
            backward: fn (CalculationRequest $request): CalculationResult => new CalculationResult(
                $request->getAmount(),
                Amount::fromString('1', $request->getAmount()->getCurrency()),
                Amount::fromString(
                    bcadd($request->getAmount()->getValue(), '1', 4),
                    $request->getAmount()->getCurrency()
                ),
                CalculationDirection::BACKWARD
            )
        );

        $registry = new StrategyRegistry([$strategy]);
        $calculator = new FeeCalculator($registry, 3);

        $currency = new Currency('USD', 3);
        $result = $calculator->calculate(CalculationRequest::backward('flat', Amount::fromString('20.5', $currency)));

        self::assertSame('20.500', $result->getBaseAmount()->getValue());
        self::assertSame('1.000', $result->getFeeAmount()->getValue());
        self::assertSame('21.500', $result->getTotalAmount()->getValue());
        self::assertSame(CalculationDirection::BACKWARD, $result->getDirection());
    }

    public function testThrowsWhenStrategyDoesNotSupportDirection(): void
    {
        $strategy = $this->createStrategy(
            'unsupported',
            supportsForward: false,
            supportsBackward: true,
            forward: fn (CalculationRequest $request): CalculationResult => throw new \LogicException('Should not be called'),
            backward: fn (CalculationRequest $request): CalculationResult => new CalculationResult(
                Amount::fromString('0', $request->getAmount()->getCurrency()),
                Amount::fromString('0', $request->getAmount()->getCurrency()),
                Amount::fromString('0', $request->getAmount()->getCurrency()),
                CalculationDirection::BACKWARD
            )
        );

        $registry = new StrategyRegistry([$strategy]);
        $calculator = new FeeCalculator($registry);

        $this->expectException(UnsupportedCalculationDirectionException::class);
        $this->expectExceptionMessage('Strategy "unsupported" does not support "forward" calculations.');

        $calculator->calculate(CalculationRequest::forward('unsupported', Amount::fromString('10', new Currency('USD', 2))));
    }

    public function testScaleMustBeNonNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The scale "-1" must be greater than or equal to zero.');

        new FeeCalculator(new StrategyRegistry(), -1);
    }

    private function createStrategy(
        string $name,
        bool $supportsForward,
        bool $supportsBackward,
        Closure $forward,
        Closure $backward
    ): FeeStrategyInterface {
        return new class($name, $supportsForward, $supportsBackward, $forward, $backward) implements FeeStrategyInterface {
            public function __construct(
                private readonly string $name,
                private readonly bool $supportsForward,
                private readonly bool $supportsBackward,
                private readonly Closure $forward,
                private readonly Closure $backward
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function supportsDirection(CalculationDirection $direction): bool
            {
                return match ($direction) {
                    CalculationDirection::FORWARD => $this->supportsForward,
                    CalculationDirection::BACKWARD => $this->supportsBackward,
                };
            }

            public function calculateForward(CalculationRequest $request): CalculationResult
            {
                return ($this->forward)($request);
            }

            public function calculateBackward(CalculationRequest $request): CalculationResult
            {
                return ($this->backward)($request);
            }
        };
    }
}
