<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Domain\Experiment;
use AbTesting\Domain\InvalidInput;
use AbTesting\Domain\Observation;
use AbTesting\Domain\Variant;
use AbTesting\Tests\Support\TestCase;

final class DomainTest extends TestCase
{
    public function testExperimentComputesTotalWeight(): void
    {
        $experiment = new Experiment('checkout', [new Variant('a', 30), new Variant('b', 70)]);
        $this->assertSame(100, $experiment->totalWeight);
    }

    public function testIdentifierRejectsUnsafeValues(): void
    {
        foreach (['', '-leading', 'has space', "line\nbreak", "esc\x1b[31m", '<script>', "a'b", str_repeat('a', 65), "trailing\n"] as $name) {
            $this->assertThrows(InvalidInput::class, static fn () => new Variant($name, 1), 'identifier');
        }
    }

    public function testVariantRejectsOutOfRangeWeight(): void
    {
        $this->assertThrows(InvalidInput::class, static fn () => new Variant('a', 0));
        $this->assertThrows(InvalidInput::class, static fn () => new Variant('a', Variant::MAX_WEIGHT + 1));
    }

    public function testExperimentRejectsInvalidVariantSets(): void
    {
        $this->assertThrows(InvalidInput::class, static fn () => new Experiment('e', [new Variant('a', 1)]));
        $this->assertThrows(InvalidInput::class, static fn () => new Experiment('e', [new Variant('a', 1), new Variant('a', 2)]));
        $many = array_map(static fn (int $i): Variant => new Variant("v$i", 1), range(1, Experiment::MAX_VARIANTS + 1));
        $this->assertThrows(InvalidInput::class, static fn () => new Experiment('e', $many));
    }

    public function testObservationValidatesCounts(): void
    {
        $this->assertSame(0.25, (new Observation('a', 4, 1))->rate());
        $this->assertThrows(InvalidInput::class, static fn () => new Observation('a', 0, 0));
        $this->assertThrows(InvalidInput::class, static fn () => new Observation('a', 5, 6));
        $this->assertThrows(InvalidInput::class, static fn () => new Observation('a', 5, -1));
        $this->assertThrows(InvalidInput::class, static fn () => new Observation('a', Observation::MAX_COUNT + 1, 0));
    }
}
