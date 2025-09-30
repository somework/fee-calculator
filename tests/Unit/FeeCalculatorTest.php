<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use Closure;
use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Exception\UnsupportedCalculationDirectionException;
use SomeWork\FeeCalculator\Exception\ValidationException;
use SomeWork\FeeCalculator\FeeCalculator;
use SomeWork\FeeCalculator\Registry\StrategyRegistry;

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

                return new CalculationResult(
                    '10.129',
                    '0.505',
                    '10.634',
                    CalculationDirection::FORWARD
                );
            },
            backward: fn (CalculationRequest $request): CalculationResult => throw new \LogicException('Should not be called'),
        );

        $registry = new StrategyRegistry([$strategy]);
        $calculator = new FeeCalculator($registry, 2);

        $result = $calculator->calculate(CalculationRequest::forward('percentage', '10.125'));

        self::assertInstanceOf(CalculationRequest::class, $receivedRequest);
        self::assertSame('10.12', $receivedRequest->getAmount(), 'Request amount should be normalized.');

        self::assertSame('10.12', $result->getBaseAmount());
        self::assertSame('0.50', $result->getFeeAmount());
        self::assertSame('10.63', $result->getTotalAmount());
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
                '1',
                bcadd($request->getAmount(), '1', 4),
                CalculationDirection::BACKWARD
            )
        );

        $registry = new StrategyRegistry([$strategy]);
        $calculator = new FeeCalculator($registry, 3);

        $result = $calculator->calculate(CalculationRequest::backward('flat', '20.5'));

        self::assertSame('20.500', $result->getBaseAmount());
        self::assertSame('1.000', $result->getFeeAmount());
        self::assertSame('21.500', $result->getTotalAmount());
        self::assertSame(CalculationDirection::BACKWARD, $result->getDirection());
    }

    public function testThrowsWhenStrategyDoesNotSupportDirection(): void
    {
        $strategy = $this->createStrategy(
            'unsupported',
            supportsForward: false,
            supportsBackward: true,
            forward: fn (CalculationRequest $request): CalculationResult => throw new \LogicException('Should not be called'),
            backward: fn (CalculationRequest $request): CalculationResult => new CalculationResult('0', '0', '0', CalculationDirection::BACKWARD)
        );

        $registry = new StrategyRegistry([$strategy]);
        $calculator = new FeeCalculator($registry);

        $this->expectException(UnsupportedCalculationDirectionException::class);
        $this->expectExceptionMessage('Strategy "unsupported" does not support "forward" calculations.');

        $calculator->calculate(CalculationRequest::forward('unsupported', '10'));
    }

    public function testThrowsWhenStrategyReturnsInvalidAmounts(): void
    {
        $strategy = $this->createStrategy(
            'broken',
            supportsForward: true,
            supportsBackward: true,
            forward: fn (CalculationRequest $request): CalculationResult => new CalculationResult(
                'ten',
                '1',
                '11',
                CalculationDirection::FORWARD
            ),
            backward: fn (CalculationRequest $request): CalculationResult => new CalculationResult('0', '0', '0', CalculationDirection::BACKWARD)
        );

        $registry = new StrategyRegistry([$strategy]);
        $calculator = new FeeCalculator($registry);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The provided amount "ten" is not a valid numeric string.');

        $calculator->calculate(CalculationRequest::forward('broken', '10'));
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
