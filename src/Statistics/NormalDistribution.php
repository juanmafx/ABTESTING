<?php

declare(strict_types=1);

namespace AbTesting\Statistics;

use AbTesting\Domain\InvalidInput;

final class NormalDistribution
{
    private const array QUANTILE_A = [-3.969683028665376e+01, 2.209460984245205e+02, -2.759285104469687e+02, 1.383577518672690e+02, -3.066479806614716e+01, 2.506628277459239e+00];
    private const array QUANTILE_B = [-5.447609879822406e+01, 1.615858368580409e+02, -1.556989798598866e+02, 6.680131188771972e+01, -1.328068155288572e+01, 1.0];
    private const array QUANTILE_C = [-7.784894002430293e-03, -3.223964580411365e-01, -2.400758277161838e+00, -2.549732539343734e+00, 4.374664141464968e+00, 2.938163982698783e+00];
    private const array QUANTILE_D = [7.784695709041462e-03, 3.224671290700398e-01, 2.445134137142996e+00, 3.754408661907416e+00, 1.0];
    private const float QUANTILE_LOW = 0.02425;
    private const float TAIL_CUTOFF = 37.0;
    private const int CONTINUED_FRACTION_TERMS = 60;
    private const array TAIL_NUMERATOR = [3.52624965998911e-02, 0.700383064443688, 6.37396220353165, 33.912866078383, 112.079291497871, 221.213596169931, 220.206867912376];
    private const array TAIL_DENOMINATOR = [8.83883476483184e-02, 1.75566716318264, 16.064177579207, 86.7807322029461, 296.564248779674, 637.333633378831, 793.826512519948, 440.413735824752];

    public static function upperTail(float $x): float
    {
        $magnitude = abs($x);
        $tail = self::tailOfMagnitude($magnitude);
        return $x >= 0.0 ? $tail : 1.0 - $tail;
    }

    public static function cdf(float $x): float
    {
        return self::upperTail(-$x);
    }

    public static function twoSidedPValue(float $z): float
    {
        return min(1.0, 2.0 * self::tailOfMagnitude(abs($z)));
    }

    public static function quantile(float $probability): float
    {
        if (!($probability > 0.0 && $probability < 1.0)) {
            throw new InvalidInput('Probability must be strictly between 0 and 1.');
        }
        if ($probability > 0.5) {
            return -self::quantile(1.0 - $probability);
        }
        $estimate = self::rationalQuantile($probability);
        if ($estimate < -self::TAIL_CUTOFF) {
            return $estimate;
        }
        $error = self::cdf($estimate) - $probability;
        $step = $error * sqrt(2.0 * M_PI) * exp($estimate * $estimate / 2.0);
        return $estimate - $step / (1.0 + $estimate * $step / 2.0);
    }

    private static function tailOfMagnitude(float $magnitude): float
    {
        if ($magnitude > self::TAIL_CUTOFF) {
            return 0.0;
        }
        $exponential = exp(-$magnitude * $magnitude / 2.0);
        if ($magnitude < 7.07106781186547) {
            return $exponential * self::horner(self::TAIL_NUMERATOR, $magnitude) / self::horner(self::TAIL_DENOMINATOR, $magnitude);
        }
        $fraction = $magnitude;
        for ($term = self::CONTINUED_FRACTION_TERMS; $term >= 1; $term--) {
            $fraction = $magnitude + $term / $fraction;
        }
        return $exponential / $fraction / sqrt(2.0 * M_PI);
    }

    private static function rationalQuantile(float $probability): float
    {
        if ($probability < self::QUANTILE_LOW) {
            $q = sqrt(-2.0 * log($probability));
            return self::horner(self::QUANTILE_C, $q) / self::horner(self::QUANTILE_D, $q);
        }
        $q = $probability - 0.5;
        $r = $q * $q;
        return self::horner(self::QUANTILE_A, $r) * $q / self::horner(self::QUANTILE_B, $r);
    }

    private static function horner(array $coefficients, float $x): float
    {
        $result = 0.0;
        foreach ($coefficients as $coefficient) {
            $result = $result * $x + $coefficient;
        }
        return $result;
    }
}
