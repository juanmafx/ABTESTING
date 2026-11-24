<?php

declare(strict_types=1);

namespace AbTesting\Analysis;

final readonly class Comparison
{
    public function __construct(
        public string $variant,
        public float $absoluteLift,
        public ?float $relativeLift,
        public float $differenceLower,
        public float $differenceUpper,
        public float $zScore,
        public float $pValue,
        public float $adjustedPValue,
        public bool $significant,
    ) {
    }
}
