<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Domain\InvalidInput;
use AbTesting\Statistics\ChiSquareDistribution;
use AbTesting\Tests\Support\TestCase;

final class ChiSquareDistributionTest extends TestCase
{
    public function testUpperTailMatchesClosedForms(): void
    {
        $references = [
            [0.5, 0.4795001221869535, 0.7788007830714049, 0.9188914116546758, 0.9735009788392561],
            [3.84, 0.0500435212487051, 0.14660696213035015, 0.2792676171186102, 0.4280923294206224],
            [10.0, 0.0015654022580025488, 0.006737946999085467, 0.018566135463043237, 0.0404276819945128],
        ];
        foreach ($references as [$statistic, $one, $two, $three, $four]) {
            $this->assertRelativelyNear($one, ChiSquareDistribution::upperTail($statistic, 1), 1e-9, 'df1');
            $this->assertRelativelyNear($two, ChiSquareDistribution::upperTail($statistic, 2), 1e-9, 'df2');
            $this->assertRelativelyNear($three, ChiSquareDistribution::upperTail($statistic, 3), 1e-9, 'df3');
            $this->assertRelativelyNear($four, ChiSquareDistribution::upperTail($statistic, 4), 1e-9, 'df4');
        }
    }

    public function testZeroStatisticHasTailOne(): void
    {
        $this->assertSame(1.0, ChiSquareDistribution::upperTail(0.0, 5));
    }

    public function testTailIncreasesWithDegreesOfFreedom(): void
    {
        $previous = 0.0;
        for ($df = 1; $df <= 19; $df++) {
            $tail = ChiSquareDistribution::upperTail(12.0, $df);
            $this->assertTrue($tail > $previous, "df $df");
            $previous = $tail;
        }
    }

    public function testRejectsInvalidArguments(): void
    {
        $this->assertThrows(InvalidInput::class, static fn () => ChiSquareDistribution::upperTail(1.0, 0));
        $this->assertThrows(InvalidInput::class, static fn () => ChiSquareDistribution::upperTail(1.0, 65));
        $this->assertThrows(InvalidInput::class, static fn () => ChiSquareDistribution::upperTail(-1.0, 1));
    }
}
