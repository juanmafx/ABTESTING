<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Analysis\HolmCorrection;
use AbTesting\Tests\Support\TestCase;

final class HolmCorrectionTest extends TestCase
{
    public function testAdjustsAndPreservesInputOrder(): void
    {
        $adjusted = HolmCorrection::adjust([0.04, 0.01, 0.03]);
        $this->assertNear(0.06, $adjusted[0], 1e-12);
        $this->assertNear(0.03, $adjusted[1], 1e-12);
        $this->assertNear(0.06, $adjusted[2], 1e-12);
    }

    public function testEnforcesMonotonicity(): void
    {
        $adjusted = HolmCorrection::adjust([0.01, 0.011, 0.5]);
        $this->assertNear(0.03, $adjusted[0], 1e-12);
        $this->assertNear(0.03, $adjusted[1], 1e-12);
        $this->assertNear(0.5, $adjusted[2], 1e-12);
    }

    public function testCapsAtOne(): void
    {
        $this->assertSame([1.0, 1.0], HolmCorrection::adjust([0.9, 0.8]));
    }

    public function testSingleValueIsUnchanged(): void
    {
        $this->assertSame([0.2], HolmCorrection::adjust([0.2]));
    }
}
