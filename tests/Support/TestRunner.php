<?php

declare(strict_types=1);

namespace AbTesting\Tests\Support;

use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class TestRunner
{
    private array $results = [];

    public function __construct(private readonly string $testDirectory)
    {
    }

    public function run(): bool
    {
        foreach ($this->discoverClasses() as $class) {
            $this->runClass($class);
        }
        foreach ($this->results as $result) {
            if ($result['status'] === 'failed') {
                fwrite(STDOUT, sprintf("FAILED %s::%s\n  %s\n", $result['class'], $result['name'], $result['message']));
            }
            if ($result['status'] === 'skipped') {
                fwrite(STDOUT, sprintf("SKIPPED %s::%s (%s)\n", $result['class'], $result['name'], $result['message']));
            }
        }
        $totals = $this->totals();
        fwrite(STDOUT, sprintf("Tests: %d, Passed: %d, Failed: %d, Skipped: %d, Assertions: %d\n", $totals['tests'], $totals['passed'], $totals['failed'], $totals['skipped'], $totals['assertions']));
        return $totals['failed'] === 0 && $totals['tests'] > 0;
    }

    public function writeReports(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create report directory.');
        }
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        file_put_contents($directory . '/junit.xml', $this->junit($timestamp));
        file_put_contents($directory . '/summary.md', $this->summary($timestamp));
    }

    private function discoverClasses(): array
    {
        $classes = [];
        foreach (glob($this->testDirectory . '/*Test.php') ?: [] as $file) {
            $classes[] = 'AbTesting\\Tests\\Unit\\' . basename($file, '.php');
        }
        sort($classes);
        return $classes;
    }

    private function runClass(string $class): void
    {
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!str_starts_with($method->getName(), 'test')) {
                continue;
            }
            $instance = $reflection->newInstance();
            $start = hrtime(true);
            $status = 'passed';
            $message = '';
            try {
                $method->invoke($instance);
                if ($instance->assertions === 0) {
                    $status = 'failed';
                    $message = 'Test performed no assertions.';
                }
            } catch (SkippedTest $skipped) {
                $status = 'skipped';
                $message = $skipped->getMessage();
            } catch (Throwable $failure) {
                $status = 'failed';
                $message = $failure::class . ': ' . $failure->getMessage();
            }
            $this->results[] = [
                'class' => $reflection->getShortName(),
                'name' => $method->getName(),
                'status' => $status,
                'message' => $message,
                'assertions' => $instance->assertions,
                'seconds' => (hrtime(true) - $start) / 1e9,
            ];
        }
    }

    private function totals(): array
    {
        $statuses = array_column($this->results, 'status');
        return [
            'tests' => count($this->results),
            'passed' => count(array_keys($statuses, 'passed', true)),
            'failed' => count(array_keys($statuses, 'failed', true)),
            'skipped' => count(array_keys($statuses, 'skipped', true)),
            'assertions' => array_sum(array_column($this->results, 'assertions')),
            'seconds' => array_sum(array_column($this->results, 'seconds')),
        ];
    }

    private function junit(string $timestamp): string
    {
        $totals = $this->totals();
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = sprintf('<testsuite name="abtesting" tests="%d" failures="%d" errors="0" skipped="%d" assertions="%d" time="%.6f" timestamp="%s">', $totals['tests'], $totals['failed'], $totals['skipped'], $totals['assertions'], $totals['seconds'], $timestamp);
        foreach ($this->results as $result) {
            $open = sprintf('  <testcase classname="%s" name="%s" assertions="%d" time="%.6f"', $this->xml($result['class']), $this->xml($result['name']), $result['assertions'], $result['seconds']);
            $lines[] = match ($result['status']) {
                'failed' => $open . '><failure message="' . $this->xml($result['message']) . '"/></testcase>',
                'skipped' => $open . '><skipped message="' . $this->xml($result['message']) . '"/></testcase>',
                default => $open . '/>',
            };
        }
        $lines[] = '</testsuite>';
        return implode("\n", $lines) . "\n";
    }

    private function summary(string $timestamp): string
    {
        $totals = $this->totals();
        $lines = [
            '# Test Report',
            '',
            sprintf('- Generated: %s', $timestamp),
            sprintf('- Runtime: PHP %s on %s', PHP_VERSION, PHP_OS_FAMILY),
            '- Command: `php tests/run.php --report-dir reports/tests`',
            sprintf('- Tests: %d', $totals['tests']),
            sprintf('- Passed: %d', $totals['passed']),
            sprintf('- Failed: %d', $totals['failed']),
            sprintf('- Skipped: %d', $totals['skipped']),
            sprintf('- Assertions: %d', $totals['assertions']),
            '',
            '| Suite | Test | Result |',
            '| --- | --- | --- |',
        ];
        foreach ($this->results as $result) {
            $lines[] = sprintf('| %s | %s | %s |', $result['class'], $result['name'], $result['status']);
        }
        return implode("\n", $lines) . "\n";
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
