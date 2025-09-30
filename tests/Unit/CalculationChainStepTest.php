<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStep;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Enum\ChainResultSelection;
use SomeWork\FeeCalculator\Enum\ChainStepInputSource;
use SomeWork\FeeCalculator\Exception\ValidationException;

final class CalculationChainStepTest extends TestCase
{
    public function testConstructsStepWithNormalizedValues(): void
    {
        $step = new CalculationChainStep(
            '  convert  ',
            '  percentage  ',
            CalculationDirection::FORWARD,
            ChainStepInputSource::INITIAL,
            ChainResultSelection::TOTAL,
            ['currency' => 'USD']
        );

        self::assertSame('convert', $step->getIdentifier());
        self::assertSame('percentage', $step->getStrategyName());
        self::assertSame(CalculationDirection::FORWARD, $step->getDirection());
        self::assertSame(ChainStepInputSource::INITIAL, $step->getInputSource());
        self::assertSame(ChainResultSelection::TOTAL, $step->getOutputSelection());
        self::assertSame(['currency' => 'USD'], $step->getContext());
    }

    public function testWithContextProducesNewInstance(): void
    {
        $original = new CalculationChainStep(
            'withdrawal',
            'flat',
            CalculationDirection::BACKWARD,
            ChainStepInputSource::PREVIOUS_TOTAL,
            ChainResultSelection::BASE
        );

        $clone = $original->withContext(['fee' => '1.5']);

        self::assertNotSame($original, $clone);
        self::assertSame(['fee' => '1.5'], $clone->getContext());
        self::assertSame([], $original->getContext());
    }

    public function testEmptyIdentifierThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The step identifier must not be empty.');

        new CalculationChainStep(
            '   ',
            'strategy',
            CalculationDirection::FORWARD,
            ChainStepInputSource::INITIAL
        );
    }

    public function testEmptyStrategyNameThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The strategy name must not be empty.');

        new CalculationChainStep(
            'step',
            '   ',
            CalculationDirection::FORWARD,
            ChainStepInputSource::INITIAL
        );
    }
}
