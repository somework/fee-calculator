<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Strategy;

use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Contracts\CalculationResult;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Enum\CalculationDirection;

final class AdyenInterchangePlusPlusStrategy extends AbstractFeeStrategy implements FeeStrategyInterface
{
    private const string DEFAULT_NAME = 'adyen.interchange_plus_plus';

    private string $name;

    public function __construct(string $name = self::DEFAULT_NAME, int $scale = 8)
    {
        parent::__construct($scale);
        $this->name = $name;
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
        [$percentageRate, $fixedFee, $meta] = $this->resolveComponents($request);
        $baseAmount = $request->getAmount();
        $percentageFee = $this->multiply($baseAmount, $percentageRate);
        $feeAmount = $this->add($percentageFee, $fixedFee);
        $totalAmount = $this->add($baseAmount, $feeAmount);

        return $this->createForwardResult($request, $baseAmount, $feeAmount, $totalAmount, $meta);
    }

    public function calculateBackward(CalculationRequest $request): CalculationResult
    {
        [$percentageRate, $fixedFee, $meta] = $this->resolveComponents($request);
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
    private function resolveComponents(CalculationRequest $request): array
    {
        $context = $request->getContext();

        $interchangePercentage = isset($context['interchange_percentage'])
            ? $this->castNumericString($context['interchange_percentage'], 'interchange_percentage')
            : '0';
        $interchangeFixed = isset($context['interchange_fixed'])
            ? $this->castNumericString($context['interchange_fixed'], 'interchange_fixed')
            : '0';

        $schemePercentage = isset($context['scheme_percentage'])
            ? $this->castNumericString($context['scheme_percentage'], 'scheme_percentage')
            : '0';
        $schemeFixed = isset($context['scheme_fixed'])
            ? $this->castNumericString($context['scheme_fixed'], 'scheme_fixed')
            : '0';

        $markupPercentage = isset($context['markup_percentage'])
            ? $this->castNumericString($context['markup_percentage'], 'markup_percentage')
            : '0';
        $markupFixed = isset($context['markup_fixed'])
            ? $this->castNumericString($context['markup_fixed'], 'markup_fixed')
            : '0';

        $effectivePercentage = $this->add($interchangePercentage, $schemePercentage);
        $effectivePercentage = $this->add($effectivePercentage, $markupPercentage);

        $effectiveFixed = $this->add($interchangeFixed, $schemeFixed);
        $effectiveFixed = $this->add($effectiveFixed, $markupFixed);

        $components = [
            'strategy' => $this->name,
            'interchange_percentage' => $interchangePercentage,
            'interchange_fixed' => $interchangeFixed,
            'scheme_percentage' => $schemePercentage,
            'scheme_fixed' => $schemeFixed,
            'markup_percentage' => $markupPercentage,
            'markup_fixed' => $markupFixed,
            'documentation' => 'https://www.adyen.com/pricing',
        ];

        return [$effectivePercentage, $effectiveFixed, $components];
    }
}
