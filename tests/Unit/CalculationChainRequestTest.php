<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainRequest;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStep;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Enum\ChainStepInputSource;
use SomeWork\FeeCalculator\Exception\ValidationException;

final class CalculationChainRequestTest extends TestCase
{
    public function testConstructsRequest(): void
    {
        $request = new CalculationChainRequest(
            '100.5',
            new CalculationChainStep(
                'conversion',
                'percentage',
                CalculationDirection::FORWARD,
                ChainStepInputSource::INITIAL
            ),
            new CalculationChainStep(
                'withdrawal',
                'flat',
                CalculationDirection::FORWARD,
                ChainStepInputSource::PREVIOUS_OUTPUT
            )
        );

        self::assertSame('100.5', $request->getInitialAmount());
        self::assertCount(2, $request->getSteps());
    }

    public function testRejectsEmptyStepList(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The calculation chain must contain at least one step.');

        new CalculationChainRequest('10', ...[]);
    }

    public function testRejectsInvalidInitialAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The provided amount "ten" is not a valid numeric string.');

        new CalculationChainRequest('ten', new CalculationChainStep(
            'first',
            'strategy',
            CalculationDirection::FORWARD,
            ChainStepInputSource::INITIAL
        ));
    }

    public function testFirstStepMustUseInitialSource(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The first step must use the "INITIAL" input source, "PREVIOUS_OUTPUT" given.');

        new CalculationChainRequest('10', new CalculationChainStep(
            'first',
            'strategy',
            CalculationDirection::FORWARD,
            ChainStepInputSource::PREVIOUS_OUTPUT
        ));
    }

    public function testSubsequentStepCannotUseInitialSource(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only the first step may use the "INITIAL" input source; step #2 configured "INITIAL".');

        new CalculationChainRequest(
            '10',
            new CalculationChainStep(
                'first',
                'strategy',
                CalculationDirection::FORWARD,
                ChainStepInputSource::INITIAL
            ),
            new CalculationChainStep(
                'second',
                'strategy',
                CalculationDirection::FORWARD,
                ChainStepInputSource::INITIAL
            )
        );
    }
}
