<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainRequest;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStep;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Enum\ChainStepInputSource;
use SomeWork\FeeCalculator\Exception\ValidationException;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class CalculationChainRequestTest extends TestCase
{
    public function testConstructsRequest(): void
    {
        $currency = new Currency('USD', 2);
        $request = new CalculationChainRequest(
            Amount::fromString('100.5', $currency),
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

        self::assertSame('100.50', $request->getInitialAmount()->getValue());
        self::assertCount(2, $request->getSteps());
    }

    public function testRejectsEmptyStepList(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The calculation chain must contain at least one step.');

        new CalculationChainRequest(Amount::fromString('10', new Currency('USD', 2)), ...[]);
    }

    public function testRejectsInvalidInitialAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The provided amount "ten" is not a valid numeric string.');

        CalculationChainRequest::fromString('ten', new Currency('USD', 2), new CalculationChainStep(
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

        new CalculationChainRequest(Amount::fromString('10', new Currency('USD', 2)), new CalculationChainStep(
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
            Amount::fromString('10', new Currency('USD', 2)),
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
