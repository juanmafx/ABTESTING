<?php

declare(strict_types=1);

namespace AbTesting\Analysis;

use AbTesting\Domain\Experiment;
use AbTesting\Domain\InvalidInput;
use AbTesting\Domain\Observation;
use AbTesting\Statistics\ChiSquareDistribution;
use AbTesting\Statistics\NormalDistribution;

final class ExperimentAnalyzer
{
    public function analyze(array $observations, string $control, float $alpha = 0.05, ?array $expectedWeights = null): AnalysisReport
    {
        if (!($alpha > 0.0 && $alpha < 1.0)) {
            throw new InvalidInput('Alpha must be strictly between 0 and 1.');
        }
        $byVariant = $this->indexObservations($observations);
        if (!isset($byVariant[$control])) {
            throw new InvalidInput('Control variant is missing from the observations.');
        }
        $weights = $this->resolveWeights($byVariant, $expectedWeights);
        $critical = NormalDistribution::quantile(1.0 - $alpha / 2.0);

        $summaries = [];
        foreach ($observations as $observation) {
            [$lower, $upper] = $this->wilsonInterval($observation, $critical);
            $summaries[] = new VariantSummary($observation->variant, $observation->exposures, $observation->conversions, $observation->rate(), $lower, $upper);
        }

        $treatments = array_values(array_filter($observations, static fn (Observation $item): bool => $item->variant !== $control));
        $tests = array_map(fn (Observation $item): array => $this->compare($byVariant[$control], $item, $critical), $treatments);
        $adjusted = HolmCorrection::adjust(array_map(static fn (array $test): float => $test['pValue'], $tests));

        $comparisons = [];
        foreach ($treatments as $index => $treatment) {
            $test = $tests[$index];
            $comparisons[] = new Comparison(
                $treatment->variant,
                $test['absoluteLift'],
                $test['relativeLift'],
                $test['lower'],
                $test['upper'],
                $test['z'],
                $test['pValue'],
                $adjusted[$index],
                $adjusted[$index] < $alpha,
            );
        }

        return new AnalysisReport($control, $alpha, $this->checkSampleRatio($observations, $weights), $summaries, $comparisons);
    }

    private function indexObservations(array $observations): array
    {
        $count = count($observations);
        if ($count < 2 || $count > Experiment::MAX_VARIANTS) {
            throw new InvalidInput(sprintf('Analysis needs between 2 and %d variants.', Experiment::MAX_VARIANTS));
        }
        $indexed = [];
        foreach ($observations as $observation) {
            if (isset($indexed[$observation->variant])) {
                throw new InvalidInput('Each variant may appear only once.');
            }
            $indexed[$observation->variant] = $observation;
        }
        return $indexed;
    }

    private function resolveWeights(array $byVariant, ?array $expectedWeights): array
    {
        if ($expectedWeights === null) {
            return array_map(static fn (Observation $item): int => 1, $byVariant);
        }
        $observed = array_map('strval', array_keys($byVariant));
        $expected = array_map('strval', array_keys($expectedWeights));
        sort($observed);
        sort($expected);
        if ($observed !== $expected) {
            throw new InvalidInput('Expected weights must name exactly the observed variants.');
        }
        return $expectedWeights;
    }

    private function checkSampleRatio(array $observations, array $weights): SampleRatioCheck
    {
        $totalExposures = array_sum(array_map(static fn (Observation $item): int => $item->exposures, $observations));
        $totalWeight = array_sum($weights);
        $statistic = 0.0;
        foreach ($observations as $observation) {
            $expected = $totalExposures * $weights[$observation->variant] / $totalWeight;
            $statistic += ($observation->exposures - $expected) ** 2 / $expected;
        }
        $pValue = ChiSquareDistribution::upperTail($statistic, count($observations) - 1);
        return new SampleRatioCheck($statistic, $pValue, $pValue < SampleRatioCheck::THRESHOLD);
    }

    private function wilsonInterval(Observation $observation, float $critical): array
    {
        $exposures = (float) $observation->exposures;
        $rate = $observation->rate();
        $criticalSquared = $critical * $critical;
        $denominator = 1.0 + $criticalSquared / $exposures;
        $center = ($rate + $criticalSquared / (2.0 * $exposures)) / $denominator;
        $margin = $critical * sqrt($rate * (1.0 - $rate) / $exposures + $criticalSquared / (4.0 * $exposures * $exposures)) / $denominator;
        $lower = $observation->conversions === 0 ? 0.0 : max(0.0, $center - $margin);
        $upper = $observation->conversions === $observation->exposures ? 1.0 : min(1.0, $center + $margin);
        return [$lower, $upper];
    }

    private function compare(Observation $control, Observation $treatment, float $critical): array
    {
        $controlRate = $control->rate();
        $treatmentRate = $treatment->rate();
        $difference = $treatmentRate - $controlRate;
        $pooled = ($control->conversions + $treatment->conversions) / ($control->exposures + $treatment->exposures);
        $pooledVariance = $pooled * (1.0 - $pooled) * (1.0 / $control->exposures + 1.0 / $treatment->exposures);
        $z = $pooledVariance > 0.0 ? $difference / sqrt($pooledVariance) : 0.0;
        $standardError = sqrt($controlRate * (1.0 - $controlRate) / $control->exposures + $treatmentRate * (1.0 - $treatmentRate) / $treatment->exposures);
        return [
            'absoluteLift' => $difference,
            'relativeLift' => $controlRate > 0.0 ? $difference / $controlRate : null,
            'lower' => $difference - $critical * $standardError,
            'upper' => $difference + $critical * $standardError,
            'z' => $z,
            'pValue' => NormalDistribution::twoSidedPValue($z),
        ];
    }
}
