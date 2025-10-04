<?php

declare(strict_types=1);

namespace SomeWork\MonetaryCalculatorTests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use SomeWork\MonetaryCalculator\Helpers\IdentifierNormalizer;

final class IdentifierNormalizerTest extends TestCase
{
    public function testNormalizesString(): void
    {
        $result = IdentifierNormalizer::normalize('test');
        self::assertSame('test', $result);
    }

    public function testNormalizesInteger(): void
    {
        $result = IdentifierNormalizer::normalize(123);
        self::assertSame('123', $result);
    }

    public function testNormalizesObjectWithToString(): void
    {
        $obj = new class {
            public function __toString(): string
            {
                return 'custom_string';
            }
        };

        $result = IdentifierNormalizer::normalize($obj);
        self::assertSame('custom_string', $result);
    }

    public function testNormalizesObjectWithoutToString(): void
    {
        $obj = new \stdClass();

        $result = IdentifierNormalizer::normalize($obj);
        self::assertStringMatchesFormat('stdClass@%d', $result);
    }

    public function testNormalizesNull(): void
    {
        $result = IdentifierNormalizer::normalize(null);
        self::assertSame('null', $result);
    }

    public function testNormalizesResource(): void
    {
        $resource = fopen('php://memory', 'r');
        $result = IdentifierNormalizer::normalize($resource);
        self::assertStringMatchesFormat('resource@%d', $result);

        fclose($resource);
    }

    public function testThrowsExceptionForUnsupportedType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported identifier type: array');

        IdentifierNormalizer::normalize([]);
    }

    public function testNormalizesVariousStringTypes(): void
    {
        $testCases = [
            'simple' => 'simple',
            'with spaces' => 'with spaces',
            'with-dashes' => 'with-dashes',
            'with_underscores' => 'with_underscores',
            'UPPERCASE' => 'UPPERCASE',
            'lowercase' => 'lowercase',
            '123' => '123',
            '0' => '0',
        ];

        foreach ($testCases as $input => $expected) {
            $result = IdentifierNormalizer::normalize($input);
            self::assertSame($expected, $result, "Failed for input: $input");
        }
    }
}
