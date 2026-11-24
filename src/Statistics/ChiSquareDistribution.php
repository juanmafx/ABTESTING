<?php

declare(strict_types=1);

namespace AbTesting\Statistics;

use AbTesting\Domain\InvalidInput;

final class ChiSquareDistribution
{
    public const int MAX_DEGREES_OF_FREEDOM = 64;

    public static function upperTail(float $statistic, int $degreesOfFreedom): float
    {
        if ($degreesOfFreedom < 1 || $degreesOfFreedom > self::MAX_DEGREES_OF_FREEDOM) {
            throw new InvalidInput(sprintf('Degrees of freedom must be between 1 and %d.', self::MAX_DEGREES_OF_FREEDOM));
        }
        if ($statistic < 0.0) {
            throw new InvalidInput('Chi-square statistic must be non-negative.');
        }
        if ($statistic === 0.0) {
            return 1.0;
        }
        $x = $statistic / 2.0;
        $isOdd = $degreesOfFreedom % 2 === 1;
        $shape = $isOdd ? 0.5 : 1.0;
        $tail = $isOdd ? 2.0 * NormalDistribution::upperTail(sqrt($statistic)) : exp(-$x);
        $term = $isOdd ? sqrt($x) * exp(-$x) / (sqrt(M_PI) / 2.0) : $x * exp(-$x);
        $target = $degreesOfFreedom / 2.0;
        while ($shape < $target) {
            $tail += $term;
            $shape += 1.0;
            $term *= $x / $shape;
        }
        return min(1.0, $tail);
    }
}
