<?php

declare(strict_types=1);

namespace AbTesting\Analysis;

final readonly class SampleRatioCheck
{
    public const float THRESHOLD = 0.001;

    public function __construct(public float $statistic, public float $pValue, public bool $mismatch)
    {
    }
}
