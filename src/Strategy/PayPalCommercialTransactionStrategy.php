<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Strategy;

use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Enum\CalculationDirection;

final class PayPalCommercialTransactionStrategy extends AbstractFeeStrategy implements FeeStrategyInterface
{
    private const string DEFAULT_NAME = 'paypal.commercial_transaction';

    private string $name;

    private string $basePercentageRate;

    private string $fixedFee;

    private string $defaultCrossBorderPercentage;

    public function __construct(
        string $name = self::DEFAULT_NAME,
        string $basePercentageRate = '0.0349',
        string $fixedFee = '0.49',
        string $defaultCrossBorderPercentage = '0.015',
        int $scale = 8
    ) {
        parent::__construct($scale);
        $this->name = $name;
        $this->basePercentageRate = $basePercentageRate;
        $this->fixedFee = $fixedFee;
        $this->defaultCrossBorderPercentage = $defaultCrossBorderPercentage;
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
        [$percentageRate, $fixedFee, $meta] = $this->resolveEffectiveRates($request);
        $baseAmount = $request->getAmount();
        $percentageFee = $this->multiply($baseAmount, $percentageRate);
        $feeAmount = $this->add($percentageFee, $fixedFee);
        $totalAmount = $this->add($baseAmount, $feeAmount);

        return $this->createForwardResult($request, $baseAmount, $feeAmount, $totalAmount, $meta);
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        [$percentageRate, $fixedFee, $meta] = $this->resolveEffectiveRates($request);
        $totalAmount = $request->getAmount();
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
    private function resolveEffectiveRates(CalculationRequest $request): array
    {
        $context = $request->getContext();
        $effectivePercentage = $this->basePercentageRate;
        $components = [
            'base_percentage_rate' => $this->basePercentageRate,
        ];

        if (($context['cross_border'] ?? false) === true) {
            $effectivePercentage = $this->add($effectivePercentage, $this->defaultCrossBorderPercentage);
            $components['cross_border_percentage'] = $this->defaultCrossBorderPercentage;
        }

        if (isset($context['additional_percentage'])) {
            $additionalPercentage = $this->castNumericString($context['additional_percentage'], 'additional_percentage');
            $effectivePercentage = $this->add($effectivePercentage, $additionalPercentage);
            $components['additional_percentage'] = $additionalPercentage;
        }

        if (isset($context['currency_conversion_percentage'])) {
            $conversionPercentage = $this->castNumericString($context['currency_conversion_percentage'], 'currency_conversion_percentage');
            $effectivePercentage = $this->add($effectivePercentage, $conversionPercentage);
            $components['currency_conversion_percentage'] = $conversionPercentage;
        }

        $effectiveFixed = $this->fixedFee;
        $components['fixed_fee'] = $this->fixedFee;

        if (isset($context['additional_fixed_fee'])) {
            $additionalFixed = $this->castNumericString($context['additional_fixed_fee'], 'additional_fixed_fee');
            $effectiveFixed = $this->add($effectiveFixed, $additionalFixed);
            $components['additional_fixed_fee'] = $additionalFixed;
        }

        $components['strategy'] = $this->name;
        $components['documentation'] = 'https://www.paypal.com/us/webapps/mpp/merchant-fees';

        return [$effectivePercentage, $effectiveFixed, $components];
    }
}
