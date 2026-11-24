<?php

declare(strict_types=1);

namespace AbTesting\Tests\Support;

use Closure;
use Throwable;

abstract class TestCase
{
    public int $assertions = 0;

    protected function assertSame(mixed $expected, mixed $actual, string $label = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new AssertionFailed(sprintf('%s expected %s, got %s', $label, var_export($expected, true), var_export($actual, true)));
        }
    }

    protected function assertTrue(bool $condition, string $label = 'condition'): void
    {
        $this->assertSame(true, $condition, $label);
    }

    protected function assertFalse(bool $condition, string $label = 'condition'): void
    {
        $this->assertSame(false, $condition, $label);
    }

    protected function assertNear(float $expected, float $actual, float $tolerance, string $label = ''): void
    {
        $this->assertions++;
        if (abs($expected - $actual) > $tolerance) {
            throw new AssertionFailed(sprintf('%s expected %.17g within %g, got %.17g', $label, $expected, $tolerance, $actual));
        }
    }

    protected function assertRelativelyNear(float $expected, float $actual, float $tolerance, string $label = ''): void
    {
        $this->assertNear($expected, $actual, abs($expected) * $tolerance, $label);
    }

    protected function assertThrows(string $class, Closure $action, string $label = ''): void
    {
        $this->assertions++;
        try {
            $action();
        } catch (Throwable $thrown) {
            if ($thrown instanceof $class) {
                return;
            }
            throw new AssertionFailed(sprintf('%s expected %s, got %s', $label, $class, $thrown::class));
        }
        throw new AssertionFailed(sprintf('%s expected %s, nothing was thrown', $label, $class));
    }

    protected function skip(string $reason): never
    {
        throw new SkippedTest($reason);
    }
}
