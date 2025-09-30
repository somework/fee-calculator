<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\FeeCalculator;
use SomeWork\FeeCalculator\Registry\StrategyRegistry;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class FeeCalculatorIntegrationTest extends TestCase
{
    public function testForwardAndBackwardCalculationsWithPercentageStrategy(): void
    {
        $strategy = new PercentageFeeStrategy();
        $calculator = new FeeCalculator(new StrategyRegistry([$strategy]), 4);

        $currency = new Currency('USD', 4);
        $forwardRequest = CalculationRequest::forward('percentage', Amount::fromString('100', $currency), ['rate' => '0.05']);
        $forwardResult = $calculator->calculate($forwardRequest);

        self::assertSame('USD', $forwardResult->getBaseAmount()->getCurrency()->getCode());
        self::assertSame('100.0000', $forwardResult->getBaseAmount()->getValue());
        self::assertSame('5.0000', $forwardResult->getFeeAmount()->getValue());
        self::assertSame('105.0000', $forwardResult->getTotalAmount()->getValue());
        self::assertSame(['rate' => '0.05'], $forwardResult->getContext());

        $backwardRequest = CalculationRequest::backward('percentage', Amount::fromString('210', $currency), ['rate' => '0.20']);
        $backwardResult = $calculator->calculate($backwardRequest);

        self::assertSame('175.0000', $backwardResult->getBaseAmount()->getValue());
        self::assertSame('35.0000', $backwardResult->getFeeAmount()->getValue());
        self::assertSame('210.0000', $backwardResult->getTotalAmount()->getValue());
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
        $base = $request->getAmount()->getValue();
        $fee = bcmul($base, $rate, 6);
        $total = bcadd($base, $fee, 6);

        $currency = $request->getAmount()->getCurrency();

        return new CalculationResult(
            Amount::fromString($base, $currency),
            Amount::fromString($fee, $currency),
            Amount::fromString($total, $currency),
            CalculationDirection::FORWARD,
            ['rate' => $rate]
        );
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        $rate = $this->getRate($request);
        $total = $request->getAmount()->getValue();
        $divider = bcadd('1', $rate, 6);
        $base = bcdiv($total, $divider, 6);
        $fee = bcsub($total, $base, 6);

        $currency = $request->getAmount()->getCurrency();

        return new CalculationResult(
            Amount::fromString($base, $currency),
            Amount::fromString($fee, $currency),
            Amount::fromString($total, $currency),
            CalculationDirection::BACKWARD,
            ['rate' => $rate]
        );
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
