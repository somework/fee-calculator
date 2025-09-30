<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Strategy;

use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Enum\CalculationDirection;

final class StripeInternationalSurchargeStrategy extends AbstractFeeStrategy implements FeeStrategyInterface
{
    private const DEFAULT_NAME = 'stripe.international_surcharge';

    private string $name;

    private string $percentageRate;

    public function __construct(string $name = self::DEFAULT_NAME, string $percentageRate = '0.015', int $scale = 8)
    {
        parent::__construct($scale);
        $this->name = $name;
        $this->percentageRate = $percentageRate;
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
        $baseAmount = $request->getAmount()->getValue();
        $feeAmount = $this->multiply($baseAmount, $this->percentageRate);
        $totalAmount = $this->add($baseAmount, $feeAmount);

        return $this->createForwardResult($request, $baseAmount, $feeAmount, $totalAmount, [
            'strategy' => $this->name,
            'percentage_rate' => $this->percentageRate,
            'documentation' => 'https://stripe.com/pricing',
            'description' => 'Applies Stripe international card surcharge.',
        ]);
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        $totalAmount = $request->getAmount()->getValue();
        $denominator = $this->add('1', $this->percentageRate);
        $baseAmount = $this->divide($totalAmount, $denominator);
        $feeAmount = $this->subtract($totalAmount, $baseAmount);

        return $this->createBackwardResult($request, $baseAmount, $feeAmount, $totalAmount, [
            'strategy' => $this->name,
            'percentage_rate' => $this->percentageRate,
            'documentation' => 'https://stripe.com/pricing',
            'description' => 'Applies Stripe international card surcharge.',
        ]);
    }
}
