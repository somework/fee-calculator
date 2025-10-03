<?php

namespace SomeWork\FeeCalculator\Contracts\DTO;

interface FeeInterface
{
    public function getPercent(): string;

    public function getFixed(): ?AmountInterface;
}
