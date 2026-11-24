<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Domain\InvalidInput;
use AbTesting\Statistics\NormalDistribution;
use AbTesting\Tests\Support\TestCase;

final class NormalDistributionTest extends TestCase
{
    public function testUpperTailMatchesReferenceValues(): void
    {
        $references = [[0.0, 0.5], [0.5, 0.30853753872598694], [1.96, 0.024997895148220435], [3.0, 0.0013498980316300957], [5.0, 2.8665157187919455e-07], [8.0, 6.22096057427182e-16]];
        foreach ($references as [$x, $expected]) {
            $this->assertRelativelyNear($expected, NormalDistribution::upperTail($x), 1e-9, "tail($x)");
        }
    }

    public function testCdfIsSymmetric(): void
    {
        foreach ([0.1, 1.0, 2.5, 4.0] as $x) {
            $this->assertNear(1.0, NormalDistribution::cdf($x) + NormalDistribution::cdf(-$x), 1e-14);
        }
    }

    public function testExtremeTailIsZero(): void
    {
        $this->assertSame(0.0, NormalDistribution::upperTail(40.0));
        $this->assertSame(0.0, NormalDistribution::twoSidedPValue(-40.0));
    }

    public function testQuantileMatchesReferenceValues(): void
    {
        $references = [[0.5, 0.0], [0.8, 0.8416212335729144], [0.975, 1.9599639845400536], [0.999, 3.090232306167813], [0.001, -3.090232306167813]];
        foreach ($references as [$probability, $expected]) {
            $this->assertNear($expected, NormalDistribution::quantile($probability), 1e-8, "quantile($probability)");
        }
    }

    public function testQuantileInvertsCdf(): void
    {
        foreach ([0.0001, 0.02, 0.3, 0.7, 0.98, 0.9999] as $probability) {
            $this->assertNear($probability, NormalDistribution::cdf(NormalDistribution::quantile($probability)), 1e-10);
        }
    }

    public function testQuantileIsAccurateInUpperTail(): void
    {
        $this->assertNear(7.034486910047836, NormalDistribution::quantile(0.99999999999900002), 2e-9);
        $this->assertNear(7.941444487415977, NormalDistribution::quantile(0.999999999999999), 2e-9);
    }

    public function testQuantileStaysFiniteBeyondTailCutoff(): void
    {
        $quantile = NormalDistribution::quantile(1e-320);
        $this->assertTrue(is_finite($quantile), 'finite');
        $this->assertTrue($quantile < -37.0, 'beyond cutoff');
    }

    public function testQuantileRejectsBoundaries(): void
    {
        foreach ([0.0, 1.0, -0.1, 1.1, NAN] as $probability) {
            $this->assertThrows(InvalidInput::class, static fn () => NormalDistribution::quantile($probability));
        }
    }
}
