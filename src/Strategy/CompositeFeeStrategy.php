<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Strategy;

use InvalidArgumentException;
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Enum\CalculationDirection;
use SomeWork\FeeCalculator\ValueObject\Amount;

final class CompositeFeeStrategy extends AbstractFeeStrategy implements FeeStrategyInterface
{
    private const DEFAULT_NAME = 'composite';

    /** @var list<FeeStrategyInterface> */
    private array $strategies;

    private string $name;

    private int $maxIterations;

    /**
     * @param list<FeeStrategyInterface> $strategies
     */
    public function __construct(array $strategies, string $name = self::DEFAULT_NAME, int $scale = 8, int $maxIterations = 50)
    {
        parent::__construct($scale);

        if ($strategies === []) {
            throw new InvalidArgumentException('CompositeFeeStrategy requires at least one strategy.');
        }

        foreach ($strategies as $strategy) {
            if (!$strategy instanceof FeeStrategyInterface) {
                throw new InvalidArgumentException('CompositeFeeStrategy expects only FeeStrategyInterface instances.');
            }
        }

        $this->strategies = array_values($strategies);
        $this->name = $name;
        $this->maxIterations = max(1, $maxIterations);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function supportsDirection(CalculationDirection $direction): bool
    {
        foreach ($this->strategies as $strategy) {
            if (!$strategy->supportsDirection($direction)) {
                return false;
            }
        }

        return true;
    }

    public function calculateForward(CalculationRequest $request): CalculationResult
    {
        $this->ensureDirectionSupported($request->getDirection(), $this->supportsDirection(CalculationDirection::FORWARD), $this->name);

        [$feeAmount, $totalAmount, $componentResults] = $this->runForward($request->getAmount(), $request->getContext());

        return $this->createForwardResult($request, $request->getAmount()->getValue(), $feeAmount->getValue(), $totalAmount->getValue(), [
            'strategy' => $this->name,
            'component_results' => $componentResults,
        ]);
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        $this->ensureDirectionSupported($request->getDirection(), $this->supportsDirection(CalculationDirection::BACKWARD), $this->name);

        [$baseAmount, $feeAmount, $totalAmount, $componentResults] = $this->solveForBaseAmount(
            $request->getAmount(),
            $request->getContext()
        );

        $resultRequest = $request->withAmount($baseAmount);

        return $this->createBackwardResult($resultRequest, $baseAmount->getValue(), $feeAmount->getValue(), $totalAmount->getValue(), [
            'strategy' => $this->name,
            'component_results' => $componentResults,
        ]);
    }

    /**
     * @param array<string, mixed> $requestContext
     * @return array{0: \SomeWork\FeeCalculator\ValueObject\Amount, 1: \SomeWork\FeeCalculator\ValueObject\Amount, 2: array<string, array<string, mixed>>}
     */
    private function runForward(Amount $baseAmount, array $requestContext): array
    {
        $currency = $baseAmount->getCurrency();
        $totalFee = '0';
        $componentResults = [];

        foreach ($this->strategies as $strategy) {
            $childContext = $this->resolveComponentContext($strategy->getName(), $requestContext);
            $childRequest = CalculationRequest::forward($strategy->getName(), $baseAmount, $childContext);
            $childResult = $strategy->calculateForward($childRequest);
            $totalFee = $this->add($totalFee, $childResult->getFeeAmount()->getValue());
            $componentResults[$strategy->getName()] = [
                'base_amount' => $childResult->getBaseAmount()->getValue(),
                'fee_amount' => $childResult->getFeeAmount()->getValue(),
                'total_amount' => $childResult->getTotalAmount()->getValue(),
                'context' => $childResult->getContext(),
            ];
        }

        $totalAmount = $this->add($baseAmount->getValue(), $totalFee);

        return [
            Amount::fromString($totalFee, $currency),
            Amount::fromString($totalAmount, $currency),
            $componentResults,
        ];
    }

    /**
     * @param array<string, mixed> $requestContext
     * @return array{0: \SomeWork\FeeCalculator\ValueObject\Amount, 1: \SomeWork\FeeCalculator\ValueObject\Amount, 2: \SomeWork\FeeCalculator\ValueObject\Amount, 3: array<string, array<string, mixed>>}
     */
    private function solveForBaseAmount(Amount $targetTotal, array $requestContext): array
    {
        $currency = $targetTotal->getCurrency();
        [, $minimalTotal] = $this->runForward(Amount::fromString('0', $currency), $requestContext);
        if ($this->compare($targetTotal->getValue(), $minimalTotal->getValue()) < 0) {
            throw new InvalidArgumentException('Total amount is lower than the minimal possible composite total.');
        }

        $lower = '0';
        $upper = $targetTotal->getValue();
        $bestBase = $lower;
        $bestDifference = $this->absolute($this->subtract($targetTotal->getValue(), $minimalTotal->getValue()));

        for ($iteration = 0; $iteration < $this->maxIterations; $iteration++) {
            $mid = $this->divide($this->add($lower, $upper), '2');
            [$fee, $total, $components] = $this->runForward(Amount::fromString($mid, $currency), $requestContext);
            $difference = $this->absolute($this->subtract($total->getValue(), $targetTotal->getValue()));

            if ($iteration === 0 || $this->compare($difference, $bestDifference) < 0) {
                $bestDifference = $difference;
                $bestBase = $mid;
            }

            if ($this->compare($difference, $this->tolerance()) <= 0) {
                break;
            }

            if ($this->compare($total->getValue(), $targetTotal->getValue()) > 0) {
                $upper = $mid;
            } else {
                $lower = $mid;
            }
        }

        $bestBase = $this->normalize(bcadd($bestBase, '0', $this->getScale()));
        [$finalFee, $finalTotal, $finalComponents] = $this->runForward(Amount::fromString($bestBase, $currency), $requestContext);

        return [
            Amount::fromString($bestBase, $currency),
            $finalFee,
            $finalTotal,
            $finalComponents,
        ];
    }

    /**
     * @param array<string, mixed> $requestContext
     * @return array<string, mixed>
     */
    private function resolveComponentContext(string $strategyName, array $requestContext): array
    {
        $baseContext = $requestContext;
        unset($baseContext['components'], $baseContext['shared']);

        $sharedContext = $requestContext['shared'] ?? [];
        if (!is_array($sharedContext)) {
            throw new InvalidArgumentException('Composite shared context must be an array.');
        }

        $componentSpecific = [];
        if (isset($requestContext['components'])) {
            if (!is_array($requestContext['components'])) {
                throw new InvalidArgumentException('Composite components context must be an array keyed by strategy name.');
            }

            if (isset($requestContext['components'][$strategyName])) {
                $componentSpecific = $requestContext['components'][$strategyName];
                if (!is_array($componentSpecific)) {
                    throw new InvalidArgumentException(sprintf('Component context for "%s" must be an array.', $strategyName));
                }
            }
        }

        return array_replace($baseContext, $sharedContext, $componentSpecific);
    }

    private function tolerance(): string
    {
        $scale = $this->getScale();
        if ($scale <= 0) {
            return '1';
        }

        $denominator = '1' . str_repeat('0', $scale);

        return $this->divide('1', $denominator);
    }
}
