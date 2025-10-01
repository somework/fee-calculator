<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Strategy;

use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Enum\CalculationDirection;

final class WiseTransferFeeStrategy extends AbstractFeeStrategy implements FeeStrategyInterface
{
    private const DEFAULT_NAME = 'wise.transfer';

    private string $name;

    private string $defaultVariablePercentage;

    private string $defaultFixedFee;

    public function __construct(
        string $name = self::DEFAULT_NAME,
        string $defaultVariablePercentage = '0.0065',
        string $defaultFixedFee = '0.31',
        int $scale = 8
    ) {
        parent::__construct($scale);
        $this->name = $name;
        $this->defaultVariablePercentage = $defaultVariablePercentage;
        $this->defaultFixedFee = $defaultFixedFee;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function supportsDirection(CalculationDirection $direction): bool
    {
        return true;
    }

    public function calculateForward(CalculationRequest $request): CalculationResult
    {
        [$percentageRate, $fixedFee, $meta] = $this->resolveFees($request);
        $baseAmount = $request->getAmount()->getValue();
        $percentageFee = $this->multiply($baseAmount, $percentageRate);
        $feeAmount = $this->add($percentageFee, $fixedFee);
        $totalAmount = $this->add($baseAmount, $feeAmount);

        return $this->createForwardResult($request, $baseAmount, $feeAmount, $totalAmount, $meta);
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        [$percentageRate, $fixedFee, $meta] = $this->resolveFees($request);
        $totalAmount = $request->getAmount()->getValue();
        $denominator = $this->add('1', $percentageRate);
        $adjustedTotal = $this->subtract($totalAmount, $fixedFee);
        $baseAmount = $this->divide($adjustedTotal, $denominator);
        $percentageFee = $this->multiply($baseAmount, $percentageRate);
        $feeAmount = $this->add($percentageFee, $fixedFee);

        return $this->createBackwardResult($request, $baseAmount, $feeAmount, $totalAmount, $meta);
    }

    /**
     * @return array{0: string, 1: string, 2: array<string, mixed>}
     */
    private function resolveFees(CalculationRequest $request): array
    {
        $context = $request->getContext();

        $percentageRate = isset($context['variable_percentage'])
            ? $this->castNumericString($context['variable_percentage'], 'variable_percentage')
            : $this->defaultVariablePercentage;

        $fixedFee = isset($context['fixed_fee'])
            ? $this->castNumericString($context['fixed_fee'], 'fixed_fee')
            : $this->defaultFixedFee;

        if (isset($context['additional_percentage'])) {
            $additionalPercentage = $this->castNumericString($context['additional_percentage'], 'additional_percentage');
            $percentageRate = $this->add($percentageRate, $additionalPercentage);
        }

        if (isset($context['additional_fixed_fee'])) {
            $additionalFixed = $this->castNumericString($context['additional_fixed_fee'], 'additional_fixed_fee');
            $fixedFee = $this->add($fixedFee, $additionalFixed);
        }

        $components = [
            'strategy' => $this->name,
            'variable_percentage' => $percentageRate,
            'fixed_fee' => $fixedFee,
            'documentation' => 'https://wise.com/help/articles/2932695/fees-and-pricing',
        ];

        return [$percentageRate, $fixedFee, $components];
    }
}
