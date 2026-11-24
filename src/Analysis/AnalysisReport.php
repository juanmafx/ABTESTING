<?php

declare(strict_types=1);

namespace AbTesting\Analysis;

final readonly class AnalysisReport
{
    public function __construct(
        public string $control,
        public float $alpha,
        public SampleRatioCheck $sampleRatio,
        public array $variants,
        public array $comparisons,
    ) {
    }
}
