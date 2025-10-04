<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Core\DTO\Currency;
use SomeWork\MonetaryCalculator\Core\Exception\InvalidScaleException;
use SomeWork\MonetaryCalculator\Exception\DTO\IdentifierEmptyException;

final class CurrencyTest extends TestCase
{
    /**
     * @dataProvider validCurrencyProvider
     */
    public function testItStoresBasicData(string $identifier, int $scale): void
    {
        $currency = new Currency($identifier, $scale);

        self::assertSame($identifier, $currency->getIdentifier());
        self::assertSame($scale, $currency->getScale());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function validCurrencyProvider(): array
    {
        return [
            'standard_currency' => ['USD', 2],
            'euro' => ['EUR', 2],
            'crypto' => ['BTC', 8],
            'zero_scale' => ['JPY', 0],
            'high_scale' => ['BHD', 3],
            'case_insensitive' => ['usd', 2],
            'with_numbers' => ['USD2', 2],
        ];
    }

    public function testItRejectsEmptyIdentifier(): void
    {
        $this->expectException(IdentifierEmptyException::class);

        new Currency('', 2);
    }

    public function testItRejectsEmptyStringIdentifier(): void
    {
        $this->expectException(IdentifierEmptyException::class);

        new Currency('', 2);
    }

    public function testItRejectsNullIdentifier(): void
    {
        $this->expectException(IdentifierEmptyException::class);

        new Currency(null, 2);
    }

    /**
     * @dataProvider invalidScaleProvider
     */
    public function testItRejectsInvalidScale(int $scale): void
    {
        $this->expectException(InvalidScaleException::class);

        new Currency('USD', $scale);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidScaleProvider(): array
    {
        return [
            'negative_scale' => [-1],
            'negative_large' => [-100],
        ];
    }

    public function testItAllowsZeroScale(): void
    {
        $currency = new Currency('JPY', 0);

        self::assertSame('JPY', $currency->getIdentifier());
        self::assertSame(0, $currency->getScale());
    }

    public function testItHandlesLargeScale(): void
    {
        $currency = new Currency('TEST', 10);

        self::assertSame('TEST', $currency->getIdentifier());
        self::assertSame(10, $currency->getScale());
    }

    public function testItNormalizesIdentifierCase(): void
    {
        $currency = new Currency('Usd', 2);

        self::assertSame('Usd', $currency->getIdentifier());
    }
}
