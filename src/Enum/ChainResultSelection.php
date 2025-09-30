<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Enum;

enum ChainResultSelection: string
{
    case BASE = 'base';
    case TOTAL = 'total';
    case FEE = 'fee';
}
