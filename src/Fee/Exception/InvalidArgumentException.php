<?php

namespace SomeWork\MonetaryCalculator\Fee\Exception;

use SomeWork\MonetaryCalculator\Exception\InvalidArgumentException as BaseInvalidArgumentException;

class InvalidArgumentException extends BaseInvalidArgumentException implements FeeExceptionInterface
{
}
