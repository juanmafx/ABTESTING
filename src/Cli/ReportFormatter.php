<?php

declare(strict_types=1);

namespace AbTesting\Cli;

use AbTesting\Analysis\AnalysisReport;
use AbTesting\Analysis\Comparison;
use AbTesting\Analysis\VariantSummary;

final class ReportFormatter
{
    public function toJson(AnalysisReport $report): string
    {
        return json_encode([
            'control' => $report->control,
            'alpha' => $report->alpha,
            'sampleRatio' => [
                'statistic' => $report->sampleRatio->statistic,
                'pValue' => $report->sampleRatio->pValue,
                'mismatch' => $report->sampleRatio->mismatch,
            ],
            'variants' => $report->variants,
            'comparisons' => $report->comparisons,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . PHP_EOL;
    }

    public function toText(AnalysisReport $report): string
    {
        $confidence = (1.0 - $report->alpha) * 100.0;
        $lines = [sprintf('Control: %s    Alpha: %s', $report->control, $this->number($report->alpha))];
        $lines[] = sprintf(
            'Sample ratio check: %s (chi-square %.4f, p = %s)',
            $report->sampleRatio->mismatch ? 'MISMATCH, do not trust the comparisons below' : 'ok',
            $report->sampleRatio->statistic,
            $this->number($report->sampleRatio->pValue),
        );
        $lines[] = '';
        $lines[] = sprintf('Variants (%.4g%% Wilson interval):', $confidence);
        foreach ($report->variants as $variant) {
            $lines[] = $this->variantLine($variant);
        }
        $lines[] = '';
        $lines[] = sprintf('Comparisons against control (%.4g%% interval, Holm-adjusted p):', $confidence);
        foreach ($report->comparisons as $comparison) {
            $lines[] = $this->comparisonLine($comparison);
        }
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function variantLine(VariantSummary $variant): string
    {
        return sprintf(
            '  %s: %d/%d = %.4f%% [%.4f%%, %.4f%%]',
            $variant->variant,
            $variant->conversions,
            $variant->exposures,
            $variant->rate * 100.0,
            $variant->intervalLower * 100.0,
            $variant->intervalUpper * 100.0,
        );
    }

    private function comparisonLine(Comparison $comparison): string
    {
        return sprintf(
            '  %s: lift %+.4f pts [%+.4f, %+.4f], relative %s, z = %.4f, p = %s, adjusted p = %s, %s',
            $comparison->variant,
            $comparison->absoluteLift * 100.0,
            $comparison->differenceLower * 100.0,
            $comparison->differenceUpper * 100.0,
            $comparison->relativeLift === null ? 'n/a' : sprintf('%+.2f%%', $comparison->relativeLift * 100.0),
            $comparison->zScore,
            $this->number($comparison->pValue),
            $this->number($comparison->adjustedPValue),
            $comparison->significant ? 'significant' : 'not significant',
        );
    }

    private function number(float $value): string
    {
        return sprintf('%.4g', $value);
    }
}
