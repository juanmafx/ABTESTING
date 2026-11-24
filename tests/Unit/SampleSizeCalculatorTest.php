<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Analysis\SampleSizeCalculator;
use AbTesting\Domain\InvalidInput;
use AbTesting\Tests\Support\TestCase;

final class SampleSizeCalculatorTest extends TestCase
{
    public function testMatchesReference(): void
    {
        $this->assertSame(3841, (new SampleSizeCalculator())->perVariant(0.1, 0.02));
    }

    public function testSmallerEffectNeedsMoreUnits(): void
    {
        $calculator = new SampleSizeCalculator();
        $this->assertTrue($calculator->perVariant(0.1, 0.01) > $calculator->perVariant(0.1, 0.02));
    }

    public function testHigherPowerNeedsMoreUnits(): void
    {
        $calculator = new SampleSizeCalculator();
        $this->assertTrue($calculator->perVariant(0.1, 0.02, 0.05, 0.9) > $calculator->perVariant(0.1, 0.02, 0.05, 0.8));
    }

    public function testRejectsInvalidParameters(): void
    {
        $calculator = new SampleSizeCalculator();
        foreach ([[0.0, 0.1, 0.05, 0.8], [1.0, 0.1, 0.05, 0.8], [0.5, 0.0, 0.05, 0.8], [0.9, 0.2, 0.05, 0.8], [0.1, -0.2, 0.05, 0.8], [0.1, 0.02, 0.0, 0.8], [0.1, 0.02, 0.05, 1.0]] as [$baseline, $effect, $alpha, $power]) {
            $this->assertThrows(InvalidInput::class, static fn () => $calculator->perVariant($baseline, $effect, $alpha, $power));
        }
    }
}
