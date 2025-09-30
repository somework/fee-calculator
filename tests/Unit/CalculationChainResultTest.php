<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainResult;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStep;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStepResult;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Enum\ChainResultSelection;
use SomeWork\FeeCalculator\Enum\ChainStepInputSource;

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

        $result = new CalculationResult('10', '1', '11', CalculationDirection::FORWARD);
        $stepResult = new CalculationChainStepResult($step, '10', $result);

        $chain = new CalculationChainResult('10', '11', [$stepResult]);

        self::assertSame($stepResult, $chain->getLastStepResult());
    }

    public function testReturnsNullWhenNoSteps(): void
    {
        $chain = new CalculationChainResult('10', '10', []);

        self::assertNull($chain->getLastStepResult());
    }
}
