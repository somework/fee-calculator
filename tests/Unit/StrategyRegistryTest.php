<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Tests\Unit;

use ArrayIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Exception\StrategyNotFoundException;
use SomeWork\FeeCalculator\Registry\StrategyRegistry;

final class StrategyRegistryTest extends TestCase
{
    public function testConstructingWithIterableRegistersStrategies(): void
    {
        $strategy = $this->createStrategyMock('foo');

        $registry = new StrategyRegistry(new ArrayIterator([$strategy]));

        self::assertTrue($registry->has('foo'));
        self::assertSame(['foo' => $strategy], $registry->all());
    }

    public function testRegisterAddsStrategy(): void
    {
        $strategy = $this->createStrategyMock('bar');

        $registry = new StrategyRegistry();
        $registry->register($strategy);

        self::assertTrue($registry->has('bar'));
        self::assertSame($strategy, $registry->get('bar'));
    }

    public function testGetThrowsWhenStrategyMissing(): void
    {
        $registry = new StrategyRegistry();

        $this->expectException(StrategyNotFoundException::class);
        $this->expectExceptionMessage('No fee strategy registered with the name "missing".');

        $registry->get('missing');
    }

    /**
     * @return FeeStrategyInterface&MockObject
     */
    private function createStrategyMock(string $name)
    {
        $strategy = $this->createMock(FeeStrategyInterface::class);
        $strategy->method('getName')->willReturn($name);

        return $strategy;
    }
}
