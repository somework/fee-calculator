<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Exception;

use InvalidArgumentException;

class ValidationException extends InvalidArgumentException
{
    public static function emptyStrategyName(): self
    {
        return new self('The strategy name must not be empty.');
    }

    public static function invalidAmount(string $amount): self
    {
        return new self(sprintf('The provided amount "%s" is not a valid numeric string.', $amount));
    }

    public static function invalidScale(int $scale): self
    {
        return new self(sprintf('The scale "%d" must be greater than or equal to zero.', $scale));
    }

    public static function emptyCalculationChain(): self
    {
        return new self('The calculation chain must contain at least one step.');
    }

    public static function emptyChainStepIdentifier(): self
    {
        return new self('The step identifier must not be empty.');
    }

    public static function invalidFirstStepInputSource(string $source): self
    {
        return new self(sprintf('The first step must use the "INITIAL" input source, "%s" given.', $source));
    }

    public static function invalidSubsequentStepInputSource(int $position, string $source): self
    {
        return new self(sprintf('Only the first step may use the "INITIAL" input source; step #%d configured "%s".', $position, $source));
    }

    public static function missingPreviousStepResult(int $position, string $source): self
    {
        return new self(sprintf('Step #%d expects a previous result for source "%s", but none is available.', $position, $source));
    }

    public static function missingPreviousStepOutput(int $position): self
    {
        return new self(sprintf('Step #%d expects an output value from the previous step, but none is available.', $position));
    }
}
