<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Validator;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\DTO\Amount;
use SomeWork\MonetaryCalculator\Core\DTO\Currency;
use SomeWork\MonetaryCalculator\Fee\DTO\Fee;
use SomeWork\MonetaryCalculator\Fee\Exception\AmountNotPositiveException;
use SomeWork\MonetaryCalculator\Fee\Exception\InvalidPercentageException;
use SomeWork\MonetaryCalculator\Fee\Validator\FeeValidator;

final class FeeValidatorTest extends TestCase
{
    private Currency $usd;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usd = new Currency('USD', 2);
    }

    public function testValidatesAmountNotNegative(): void
    {
        $positiveAmount = new Amount('100.00', $this->usd);
        $zeroAmount = new Amount('0.00', $this->usd);

        // Should not throw exceptions
        FeeValidator::validateAmountNotNegative($positiveAmount);
        FeeValidator::validateAmountNotNegative($zeroAmount);

        $this->addToAssertionCount(2);
    }

    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(AmountNotPositiveException::class);

        $negativeAmount = new Amount('-100.00', $this->usd);
        FeeValidator::validateAmountNotNegative($negativeAmount);
    }

    public function testValidatesFeeStructure(): void
    {
        $validFee = new Fee('0.1');
        $feeWithFixed = new Fee('0.05', new Amount('1.00', $this->usd));

        // Should not throw exceptions
        FeeValidator::validateFeeStructure($validFee);
        FeeValidator::validateFeeStructure($feeWithFixed);

        $this->addToAssertionCount(2);
    }

    public function testRejectsNegativeFeePercentage(): void
    {
        $this->expectException(InvalidPercentageException::class);

        $negativeFee = new Fee('-1');
        FeeValidator::validateFeeStructure($negativeFee);
    }

    public function testRejectsFeePercentageOver100(): void
    {
        $this->expectException(InvalidPercentageException::class);

        $highFee = new Fee('2.0');
        FeeValidator::validateFeeStructure($highFee);
    }

    public function testValidatesCalculationAmount(): void
    {
        $positiveAmount = new Amount('100.00', $this->usd);

        // Should not throw exception
        FeeValidator::validateCalculationAmount($positiveAmount);

        $this->addToAssertionCount(1);
    }

    public function testRejectsZeroCalculationAmount(): void
    {
        $this->expectException(AmountNotPositiveException::class);

        $zeroAmount = new Amount('0.00', $this->usd);
        FeeValidator::validateCalculationAmount($zeroAmount);
    }

    public function testRejectsNegativeCalculationAmount(): void
    {
        $this->expectException(AmountNotPositiveException::class);

        $negativeAmount = new Amount('-100.00', $this->usd);
        FeeValidator::validateCalculationAmount($negativeAmount);
    }
}
