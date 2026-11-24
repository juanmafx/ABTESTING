<?php

declare(strict_types=1);

namespace AbTesting\Analysis;

use AbTesting\Domain\InvalidInput;
use AbTesting\Domain\Observation;
use AbTesting\Statistics\NormalDistribution;

final class SampleSizeCalculator
{
    public function perVariant(float $baseline, float $minimumDetectableEffect, float $alpha = 0.05, float $power = 0.8): int
    {
        $target = $baseline + $minimumDetectableEffect;
        if (!($baseline > 0.0 && $baseline < 1.0) || !($target > 0.0 && $target < 1.0) || $minimumDetectableEffect === 0.0) {
            throw new InvalidInput('Baseline and baseline plus effect must be in (0, 1), and the effect must be non-zero.');
        }
        if (!($alpha > 0.0 && $alpha < 1.0) || !($power > 0.0 && $power < 1.0)) {
            throw new InvalidInput('Alpha and power must be strictly between 0 and 1.');
        }
        $zAlpha = NormalDistribution::quantile(1.0 - $alpha / 2.0);
        $zPower = NormalDistribution::quantile($power);
        $average = ($baseline + $target) / 2.0;
        $null = $zAlpha * sqrt(2.0 * $average * (1.0 - $average));
        $alternative = $zPower * sqrt($baseline * (1.0 - $baseline) + $target * (1.0 - $target));
        $numerator = $null + $alternative;
        if ($numerator <= 0.0) {
            throw new InvalidInput('Power is too low for the chosen alpha.');
        }
        $effectSquared = $minimumDetectableEffect ** 2;
        $required = $effectSquared > 0.0 ? $numerator ** 2 / $effectSquared : INF;
        if (!is_finite($required) || $required > Observation::MAX_COUNT) {
            throw new InvalidInput('Required sample size exceeds the supported range.');
        }
        return (int) ceil($required);
    }
}
