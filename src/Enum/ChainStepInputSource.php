<?php

declare(strict_types=1);

namespace SomeWork\FeeCalculator\Enum;

enum ChainStepInputSource: string
{
    case INITIAL = 'initial';
    case PREVIOUS_OUTPUT = 'previous_output';
    case PREVIOUS_BASE = 'previous_base';
    case PREVIOUS_TOTAL = 'previous_total';
    case PREVIOUS_FEE = 'previous_fee';
}
