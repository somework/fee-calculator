<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\DTO\Amount;
use SomeWork\MonetaryCalculator\Core\DTO\Currency;

final class AmountTest extends TestCase
{
    private Currency $usd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usd = new Currency('USD', 2);
    }

    /**
     * @dataProvider normalizationProvider
     */
    public function testItNormalizesValue(string $input, string $expected): void
    {
        $amount = new Amount($input, $this->usd);

        self::assertSame($expected, $amount->getValue());
    }

    /**
     * @return array<string, string>
     */
    public static function normalizationProvider(): array
    {
        return [
            'integer' => ['1', '1.00'],
            'decimal' => ['1.5', '1.50'],
            'already_formatted' => ['1.50', '1.50'],
            'zero' => ['0', '0.00'],
            'negative' => ['-1.5', '-1.50'],
            'large_number' => ['123.456', '123.45'],
            'very_small' => ['0.001', '0.00'],
        ];
    }

    public function testItHandlesValuesWithAdditionalZeros(): void
    {
        $amount = new Amount('00.000010000', $this->usd);

        self::assertSame('0.00', $amount->getValue());
    }

    /**
     * @dataProvider equalityProvider
     */
    public function testEqualityRequiresSameCurrencyAndValue(Amount $amount1, Amount $amount2, bool $expected): void
    {
        self::assertSame($expected, $amount1->equals($amount2));
    }

    /**
     * @return array<string, array{Amount, Amount, bool}>
     */
    public static function equalityProvider(): array
    {
        $usd = new Currency('USD', 2);

        return [
            'same_currency_same_value' => [new Amount('1.00', $usd), new Amount('1.00', $usd), true],
            'same_currency_different_value' => [new Amount('1.00', $usd), new Amount('2.00', $usd), false],
            'same_currency_equivalent_values' => [new Amount('1.00', $usd), new Amount('1.000', $usd), true],
            'zero_values' => [new Amount('0.00', $usd), new Amount('0', $usd), true],
            'negative_values' => [new Amount('-1.00', $usd), new Amount('-1.000', $usd), true],
        ];
    }

    public function testGetCurrencyReturnsCorrectCurrency(): void
    {
        $amount = new Amount('100.00', $this->usd);

        self::assertSame($this->usd, $amount->getCurrency());
        self::assertSame('USD', $amount->getCurrency()->getIdentifier());
        self::assertSame(2, $amount->getCurrency()->getScale());
    }

    public function testGetValueReturnsCorrectValue(): void
    {
        $amount = new Amount('123.456', $this->usd);

        self::assertSame('123.45', $amount->getValue());
    }
}
