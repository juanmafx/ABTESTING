<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Assignment\VariantAssigner;
use AbTesting\Domain\Experiment;
use AbTesting\Domain\InvalidInput;
use AbTesting\Domain\Variant;
use AbTesting\Statistics\ChiSquareDistribution;
use AbTesting\Tests\Support\TestCase;

final class VariantAssignerTest extends TestCase
{
    public function testBucketMatchesIndependentSha256Vectors(): void
    {
        $assigner = new VariantAssigner();
        foreach ([['e', 'u1', 1618], ['checkout', 'user-1', 6718], ['checkout', 'user-2', 9557], ['pricing', 'user-1', 9419]] as [$key, $unit, $bucket]) {
            $this->assertSame($bucket, $assigner->bucket($key, $unit), "$key/$unit");
        }
    }

    public function testAssignmentIsDeterministic(): void
    {
        $experiment = new Experiment('checkout', [new Variant('control', 1), new Variant('treatment', 1)]);
        $assigner = new VariantAssigner();
        for ($i = 0; $i < 200; $i++) {
            $this->assertSame($assigner->assign($experiment, "unit-$i")->variant, $assigner->assign($experiment, "unit-$i")->variant);
        }
    }

    public function testLengthPrefixPreventsBoundaryCollisions(): void
    {
        $assigner = new VariantAssigner();
        $this->assertFalse($assigner->bucket('ab', 'c') === $assigner->bucket('a', 'bc'), 'boundary');
    }

    public function testAllocationMatchesWeights(): void
    {
        $experiment = new Experiment('allocation', [new Variant('a', 10), new Variant('b', 30), new Variant('c', 60)]);
        $assigner = new VariantAssigner();
        $counts = ['a' => 0, 'b' => 0, 'c' => 0];
        $total = 60000;
        for ($i = 0; $i < $total; $i++) {
            $counts[$assigner->assign($experiment, "unit-$i")->variant]++;
        }
        $statistic = 0.0;
        foreach (['a' => 0.1, 'b' => 0.3, 'c' => 0.6] as $name => $share) {
            $expected = $total * $share;
            $statistic += ($counts[$name] - $expected) ** 2 / $expected;
        }
        $this->assertTrue(ChiSquareDistribution::upperTail($statistic, 2) > 0.001, 'allocation fits weights');
    }

    public function testExperimentsAreIndependent(): void
    {
        $assigner = new VariantAssigner();
        $first = new Experiment('first', [new Variant('a', 1), new Variant('b', 1)]);
        $second = new Experiment('second', [new Variant('a', 1), new Variant('b', 1)]);
        $agreements = 0;
        $total = 20000;
        for ($i = 0; $i < $total; $i++) {
            $agreements += $assigner->assign($first, "unit-$i")->variant === $assigner->assign($second, "unit-$i")->variant ? 1 : 0;
        }
        $this->assertNear(0.5, $agreements / $total, 0.02, 'agreement');
    }

    public function testBucketStaysInRange(): void
    {
        $assigner = new VariantAssigner();
        for ($i = 0; $i < 5000; $i++) {
            $bucket = $assigner->bucket('range', "unit-$i");
            $this->assertTrue($bucket >= 0 && $bucket < VariantAssigner::BUCKETS);
        }
    }

    public function testRejectsVariantTooSmallToReceiveTraffic(): void
    {
        $experiment = new Experiment('tiny', [new Variant('control', 1_000_000), new Variant('treatment', 1)]);
        $assigner = new VariantAssigner();
        $this->assertThrows(InvalidInput::class, static fn () => $assigner->assign($experiment, 'unit'));
    }

    public function testSmallestSupportedShareReceivesTraffic(): void
    {
        $experiment = new Experiment('smallest', [new Variant('small', 1), new Variant('large', 9_999)]);
        $assigner = new VariantAssigner();
        $found = false;
        for ($i = 0; $i < 200_000 && !$found; $i++) {
            $found = $assigner->assign($experiment, "unit-$i")->variant === 'small';
        }
        $this->assertTrue($found, 'smallest variant is reachable');
    }

    public function testRejectsInvalidUnitIdentifiers(): void
    {
        $assigner = new VariantAssigner();
        $this->assertThrows(InvalidInput::class, static fn () => $assigner->bucket('e', ''));
        $this->assertThrows(InvalidInput::class, static fn () => $assigner->bucket('e', str_repeat('x', VariantAssigner::MAX_UNIT_BYTES + 1)));
    }
}
