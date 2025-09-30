<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Strategy;

use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Enum\CalculationDirection;

final class StripeStandardCardStrategy extends AbstractFeeStrategy implements FeeStrategyInterface
{
    private const string DEFAULT_NAME = 'stripe.standard_card';

    private string $name;

    private string $percentageRate;

    private string $fixedFee;

    public function __construct(string $name = self::DEFAULT_NAME, string $percentageRate = '0.029', string $fixedFee = '0.30', int $scale = 8)
    {
        parent::__construct($scale);
        $this->name = $name;
        $this->percentageRate = $percentageRate;
        $this->fixedFee = $fixedFee;
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
        $baseAmount = $request->getAmount();
        $percentageFee = $this->multiply($baseAmount, $this->percentageRate);
        $feeAmount = $this->add($percentageFee, $this->fixedFee);
        $totalAmount = $this->add($baseAmount, $feeAmount);

        return $this->createForwardResult($request, $baseAmount, $feeAmount, $totalAmount, [
            'strategy' => $this->name,
            'percentage_rate' => $this->percentageRate,
            'fixed_fee' => $this->fixedFee,
            'documentation' => 'https://stripe.com/pricing',
        ]);
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        $this->ensureDirectionSupported($request->getDirection(), $this->supportsDirection($request->getDirection()), $this->name);

        $totalAmount = $request->getAmount();
        $denominator = $this->add('1', $this->percentageRate);
        $adjustedTotal = $this->subtract($totalAmount, $this->fixedFee);
        $baseAmount = $this->divide($adjustedTotal, $denominator);
        $percentageFee = $this->multiply($baseAmount, $this->percentageRate);
        $feeAmount = $this->add($percentageFee, $this->fixedFee);

        return $this->createBackwardResult($request, $baseAmount, $feeAmount, $totalAmount, [
            'strategy' => $this->name,
            'percentage_rate' => $this->percentageRate,
            'fixed_fee' => $this->fixedFee,
            'documentation' => 'https://stripe.com/pricing',
        ]);
    }
}
