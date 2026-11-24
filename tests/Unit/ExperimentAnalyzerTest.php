<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Analysis\ExperimentAnalyzer;
use AbTesting\Domain\InvalidInput;
use AbTesting\Domain\Observation;
use AbTesting\Tests\Support\TestCase;

final class ExperimentAnalyzerTest extends TestCase
{
    public function testTwoProportionTestMatchesReference(): void
    {
        $report = (new ExperimentAnalyzer())->analyze([new Observation('control', 1000, 100), new Observation('treatment', 1000, 130)], 'control');
        $comparison = $report->comparisons[0];
        $this->assertNear(0.03, $comparison->absoluteLift, 1e-12);
        $this->assertNear(0.3, (float) $comparison->relativeLift, 1e-12);
        $this->assertNear(2.102740605622114, $comparison->zScore, 1e-9);
        $this->assertRelativelyNear(0.03548845046647473, $comparison->pValue, 1e-8);
        $this->assertNear(0.0020679344393763725, $comparison->differenceLower, 1e-8);
        $this->assertNear(0.05793206556062362, $comparison->differenceUpper, 1e-8);
        $this->assertTrue($comparison->significant);
    }

    public function testWilsonIntervalMatchesReference(): void
    {
        $report = (new ExperimentAnalyzer())->analyze([new Observation('control', 1000, 100), new Observation('treatment', 1000, 130)], 'control');
        $this->assertNear(0.08290944359309571, $report->variants[0]->intervalLower, 1e-8);
        $this->assertNear(0.1201519631953484, $report->variants[0]->intervalUpper, 1e-8);
    }

    public function testWilsonBoundsAtExtremes(): void
    {
        $report = (new ExperimentAnalyzer())->analyze([new Observation('control', 50, 0), new Observation('treatment', 50, 50)], 'control');
        $this->assertSame(0.0, $report->variants[0]->intervalLower);
        $this->assertSame(1.0, $report->variants[1]->intervalUpper);
    }

    public function testZeroVarianceIsNotSignificant(): void
    {
        $report = (new ExperimentAnalyzer())->analyze([new Observation('control', 100, 0), new Observation('treatment', 100, 0)], 'control');
        $comparison = $report->comparisons[0];
        $this->assertSame(0.0, $comparison->zScore);
        $this->assertSame(1.0, $comparison->pValue);
        $this->assertSame(null, $comparison->relativeLift);
        $this->assertFalse($comparison->significant);
    }

    public function testHolmCorrectionCanRemoveSignificance(): void
    {
        $report = (new ExperimentAnalyzer())->analyze([
            new Observation('control', 1000, 100),
            new Observation('first', 1000, 130),
            new Observation('second', 1000, 131),
            new Observation('third', 1000, 129),
        ], 'control');
        foreach ($report->comparisons as $comparison) {
            $this->assertTrue($comparison->pValue < 0.05, 'raw p');
            $this->assertTrue($comparison->adjustedPValue >= 0.05, 'adjusted p');
            $this->assertFalse($comparison->significant);
        }
    }

    public function testSampleRatioMismatchDetected(): void
    {
        $report = (new ExperimentAnalyzer())->analyze([new Observation('control', 1100, 100), new Observation('treatment', 900, 90)], 'control');
        $this->assertNear(20.0, $report->sampleRatio->statistic, 1e-9);
        $this->assertRelativelyNear(7.744216431044074e-06, $report->sampleRatio->pValue, 1e-8);
        $this->assertTrue($report->sampleRatio->mismatch);
    }

    public function testSampleRatioHonorsExpectedWeights(): void
    {
        $observations = [new Observation('control', 1100, 100), new Observation('treatment', 900, 90)];
        $report = (new ExperimentAnalyzer())->analyze($observations, 'control', 0.05, ['control' => 55, 'treatment' => 45]);
        $this->assertNear(0.0, $report->sampleRatio->statistic, 1e-9);
        $this->assertFalse($report->sampleRatio->mismatch);
    }

    public function testRejectsInvalidInput(): void
    {
        $analyzer = new ExperimentAnalyzer();
        $pair = [new Observation('control', 10, 1), new Observation('treatment', 10, 2)];
        $this->assertThrows(InvalidInput::class, static fn () => $analyzer->analyze($pair, 'missing'));
        $this->assertThrows(InvalidInput::class, static fn () => $analyzer->analyze($pair, 'control', 0.0));
        $this->assertThrows(InvalidInput::class, static fn () => $analyzer->analyze($pair, 'control', 1.0));
        $this->assertThrows(InvalidInput::class, static fn () => $analyzer->analyze([$pair[0]], 'control'));
        $this->assertThrows(InvalidInput::class, static fn () => $analyzer->analyze([$pair[0], $pair[0]], 'control'));
        $this->assertThrows(InvalidInput::class, static fn () => $analyzer->analyze($pair, 'control', 0.05, ['control' => 1, 'other' => 1]));
    }
}
