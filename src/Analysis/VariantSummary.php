<?php

declare(strict_types=1);

namespace AbTesting\Analysis;

final readonly class VariantSummary
{
    public function __construct(
        public string $variant,
        public int $exposures,
        public int $conversions,
        public float $rate,
        public float $intervalLower,
        public float $intervalUpper,
    ) {
    }
}
