<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Registry;

use SomeWork\FeeCalculator\Contracts\FeeStrategyInterface;
use SomeWork\FeeCalculator\Exception\StrategyNotFoundException;

final class StrategyRegistry
{
    /** @var array<string, FeeStrategyInterface> */
    private array $strategies = [];

    /**
     * @param iterable<FeeStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies = [])
    {
        foreach ($strategies as $strategy) {
            $this->register($strategy);
        }
    }

    public function register(FeeStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->strategies);
    }

    public function get(string $name): FeeStrategyInterface
    {
        if (!$this->has($name)) {
            throw StrategyNotFoundException::named($name);
        }

        return $this->strategies[$name];
    }

    /**
     * @return array<string, FeeStrategyInterface>
     */
    public function all(): array
    {
        return $this->strategies;
    }
}
