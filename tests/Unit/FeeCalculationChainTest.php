<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainRequest;
use SomeWork\FeeCalculator\Contracts\Chain\CalculationChainStep;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\Enum\ChainResultSelection;
use SomeWork\FeeCalculator\Enum\ChainStepInputSource;
use InvalidArgumentException;
use SomeWork\FeeCalculator\Exception\ValidationException;
use SomeWork\FeeCalculator\FeeCalculationChain;
use SomeWork\FeeCalculator\FeeCalculator;
use SomeWork\FeeCalculator\Registry\StrategyRegistry;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;

final class FeeCalculationChainTest extends TestCase
{
    public function testCalculatesSequentialSteps(): void
    {
        $registry = new StrategyRegistry([
            new PercentageFeeStrategy(),
            new FixedFeeStrategy(),
        ]);

        $calculator = new FeeCalculator($registry, 4);
        $chain = new FeeCalculationChain($calculator);

        $request = new CalculationChainRequest(
            '100',
            new CalculationChainStep(
                'conversion',
                'percentage',
                CalculationDirection::FORWARD,
                ChainStepInputSource::INITIAL,
                ChainResultSelection::TOTAL,
                [
                    'fee_percent' => '0.015',
                ]
            ),
            new CalculationChainStep(
                'withdrawal',
                'fixed',
                CalculationDirection::FORWARD,
                ChainStepInputSource::PREVIOUS_OUTPUT,
                ChainResultSelection::TOTAL,
                [
                    'fee_fix' => '2.5',
                ]
            )
        );

        $result = $chain->calculate($request);

        self::assertSame('100.0000', $result->getInitialAmount());
        self::assertSame('104.0000', $result->getFinalAmount());

        $steps = $result->getSteps();
        self::assertCount(2, $steps);

        $first = $steps[0];
        self::assertSame('conversion', $first->getStep()->getIdentifier());
        self::assertSame('101.5000', $first->getOutputAmount());
        self::assertSame('0.015', $first->getResult()->getContext()['fee_percent']);

        $second = $steps[1];
        self::assertSame('withdrawal', $second->getStep()->getIdentifier());
        self::assertSame('104.0000', $second->getOutputAmount());
        self::assertSame('2.5000', $second->getResult()->getContext()['fee_fix']);

        self::assertSame($second, $result->getLastStepResult());
    }

    public function testThrowsWhenPreviousOutputMissing(): void
    {
        $chain = new FeeCalculationChain(new FeeCalculator(new StrategyRegistry(), 2));
        $request = $this->createBrokenChainRequest('10', new CalculationChainStep(
            'broken',
            'unused',
            CalculationDirection::FORWARD,
            ChainStepInputSource::PREVIOUS_OUTPUT
        ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Step #1 expects an output value from the previous step, but none is available.');

        $chain->calculate($request);
    }

    public function testRequirePreviousResultGuardsAgainstMissingData(): void
    {
        $chain = new FeeCalculationChain(new FeeCalculator(new StrategyRegistry(), 2));
        $request = $this->createBrokenChainRequest('10');
        $step = new CalculationChainStep(
            'second',
            'unused',
            CalculationDirection::FORWARD,
            ChainStepInputSource::PREVIOUS_TOTAL
        );

        $reflection = new ReflectionClass(FeeCalculationChain::class);
        $method = $reflection->getMethod('resolveInputAmount');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Step #2 expects a previous result for source "PREVIOUS_TOTAL", but none is available.');

        $method->invoke($chain, $request, $step, null, null, 1);
    }

    private function createBrokenChainRequest(string $initialAmount, CalculationChainStep ...$steps): CalculationChainRequest
    {
        $reflection = new ReflectionClass(CalculationChainRequest::class);
        /** @var CalculationChainRequest $request */
        $request = $reflection->newInstanceWithoutConstructor();

        $initialProperty = $reflection->getProperty('initialAmount');
        $initialProperty->setAccessible(true);
        $initialProperty->setValue($request, $initialAmount);

        $stepsProperty = $reflection->getProperty('steps');
        $stepsProperty->setAccessible(true);
        $stepsProperty->setValue($request, $steps);

        return $request;
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
        return $direction === CalculationDirection::FORWARD;
    }

    public function calculateForward(CalculationRequest $request): CalculationResult
    {
        $base = bcadd($request->getAmount(), '0', 4);
        $contextPercent = $request->getContext()['fee_percent'] ?? '0';

        if (!is_string($contextPercent)) {
            throw new InvalidArgumentException('fee_percent must be provided as a string');
        }

        $percent = $contextPercent;
        $fee = bcmul($base, $percent, 6);
        $normalizedFee = bcadd($fee, '0', 4);
        $total = bcadd($base, $normalizedFee, 4);

        return new CalculationResult(
            $base,
            $normalizedFee,
            $total,
            CalculationDirection::FORWARD,
            [
                'fee_percent' => $percent,
            ]
        );
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        throw new \LogicException('Backward calculation is not supported.');
    }
}

final class FixedFeeStrategy implements FeeStrategyInterface
{
    public function getName(): string
    {
        return 'fixed';
    }

    public function supportsDirection(CalculationDirection $direction): bool
    {
        return $direction === CalculationDirection::FORWARD;
    }

    public function calculateForward(CalculationRequest $request): CalculationResult
    {
        $base = bcadd($request->getAmount(), '0', 4);
        $contextFix = $request->getContext()['fee_fix'] ?? '0';

        if (!is_string($contextFix)) {
            throw new InvalidArgumentException('fee_fix must be provided as a string');
        }

        $feeFix = $contextFix;
        $normalizedFee = bcadd($feeFix, '0', 4);
        $total = bcadd($base, $normalizedFee, 4);

        return new CalculationResult(
            $base,
            $normalizedFee,
            $total,
            CalculationDirection::FORWARD,
            [
                'fee_fix' => $normalizedFee,
            ]
        );
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        throw new \LogicException('Backward calculation is not supported.');
    }
}
