<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\FeeCalculator;
use SomeWork\FeeCalculator\Registry\StrategyRegistry;

final class FeeCalculatorIntegrationTest extends TestCase
{
    public function testForwardAndBackwardCalculationsWithPercentageStrategy(): void
    {
        $strategy = new PercentageFeeStrategy();
        $calculator = new FeeCalculator(new StrategyRegistry([$strategy]), 4);

        $forwardRequest = CalculationRequest::forward('percentage', '100', ['rate' => '0.05']);
        $forwardResult = $calculator->calculate($forwardRequest);

        self::assertSame('100.0000', $forwardResult->getBaseAmount());
        self::assertSame('5.0000', $forwardResult->getFeeAmount());
        self::assertSame('105.0000', $forwardResult->getTotalAmount());
        self::assertSame(['rate' => '0.05'], $forwardResult->getContext());

        $backwardRequest = CalculationRequest::backward('percentage', '210', ['rate' => '0.20']);
        $backwardResult = $calculator->calculate($backwardRequest);

        self::assertSame('175.0000', $backwardResult->getBaseAmount());
        self::assertSame('35.0000', $backwardResult->getFeeAmount());
        self::assertSame('210.0000', $backwardResult->getTotalAmount());
        self::assertSame(['rate' => '0.20'], $backwardResult->getContext());
    }
}

final class PercentageFeeStrategy implements FeeStrategyInterface
{
    public function getName(): string
    {
        return 'percentage';
    }

    public function supportsDirection(CalculationDirection $direction): bool
    {
        return true;
    }

    public function calculateForward(CalculationRequest $request): CalculationResult
    {
        $rate = $this->getRate($request);
        $base = $request->getAmount();
        $fee = bcmul($base, $rate, 6);
        $total = bcadd($base, $fee, 6);

        return new CalculationResult($base, $fee, $total, CalculationDirection::FORWARD, ['rate' => $rate]);
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        $rate = $this->getRate($request);
        $total = $request->getAmount();
        $divider = bcadd('1', $rate, 6);
        $base = bcdiv($total, $divider, 6);
        $fee = bcsub($total, $base, 6);

        return new CalculationResult($base, $fee, $total, CalculationDirection::BACKWARD, ['rate' => $rate]);
    }

    private function getRate(CalculationRequest $request): string
    {
        /** @var array<string, mixed> $context */
        $context = $request->getContext();

        if (!array_key_exists('rate', $context)) {
            throw new \InvalidArgumentException('Rate must be provided.');
        }

        $value = $context['rate'];

        if (!is_string($value)) {
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException('Rate must be numeric.');
            }

            $value = (string) $value;
        }

        return $value;
    }
}
