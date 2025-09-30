<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainResult;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStep;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStepResult;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Enum\ChainResultSelection;
use SomeWork\FeeCalculator\Enum\ChainStepInputSource;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class CalculationChainResultTest extends TestCase
{
    public function testReturnsLastStepWhenAvailable(): void
    {
        $step = new CalculationChainStep(
            'first',
            'strategy',
            CalculationDirection::FORWARD,
            ChainStepInputSource::INITIAL,
            ChainResultSelection::TOTAL
        );

        $currency = new Currency('USD', 2);
        $result = new CalculationResult(
            Amount::fromString('10', $currency),
            Amount::fromString('1', $currency),
            Amount::fromString('11', $currency),
            CalculationDirection::FORWARD
        );
        $stepResult = new CalculationChainStepResult($step, Amount::fromString('10', $currency), $result);

        $chain = new CalculationChainResult(
            Amount::fromString('10', $currency),
            Amount::fromString('11', $currency),
            [$stepResult]
        );

        self::assertSame($stepResult, $chain->getLastStepResult());
    }

    public function testReturnsNullWhenNoSteps(): void
    {
        $currency = new Currency('USD', 2);
        $chain = new CalculationChainResult(
            Amount::fromString('10', $currency),
            Amount::fromString('10', $currency),
            []
        );

        self::assertNull($chain->getLastStepResult());
    }
}
