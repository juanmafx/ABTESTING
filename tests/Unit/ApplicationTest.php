<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Cli\Application;
use AbTesting\Tests\Support\TestCase;

final class ApplicationTest extends TestCase
{
    private function execute(array $arguments): array
    {
        $output = fopen('php://memory', 'w+b');
        $errors = fopen('php://memory', 'w+b');
        $code = (new Application($output, $errors))->run($arguments);
        rewind($output);
        rewind($errors);
        $result = [$code, (string) stream_get_contents($output), (string) stream_get_contents($errors)];
        fclose($output);
        fclose($errors);
        return $result;
    }

    private function csv(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'abtest');
        file_put_contents($path, "variant,exposures,conversions\ncontrol,1000,100\ntreatment,1000,130\n");
        return $path;
    }

    public function testAssignPrintsVariantWithoutEchoingUnit(): void
    {
        [$code, $output] = $this->execute(['assign', '--experiment', 'e', '--variants', 'a:50,b:50', '--unit', 'u1']);
        $this->assertSame(0, $code);
        $this->assertSame(['experiment' => 'e', 'variant' => 'a', 'bucket' => 1618], json_decode($output, true));
        $this->assertFalse(str_contains($output, 'u1'), 'unit identifier is not echoed');
    }

    public function testAnalyzeJson(): void
    {
        $path = $this->csv();
        [$code, $output] = $this->execute(['analyze', '--input', $path, '--control', 'control', '--format=json']);
        unlink($path);
        $decoded = json_decode($output, true);
        $this->assertSame(0, $code);
        $this->assertSame('control', $decoded['control']);
        $this->assertSame(true, $decoded['comparisons'][0]['significant']);
        $this->assertSame(false, $decoded['sampleRatio']['mismatch']);
    }

    public function testAnalyzeText(): void
    {
        $path = $this->csv();
        [$code, $output] = $this->execute(['analyze', '--input', $path, '--control', 'control']);
        unlink($path);
        $this->assertSame(0, $code);
        $this->assertTrue(str_contains($output, 'Sample ratio check: ok'));
        $this->assertTrue(str_contains($output, 'treatment: lift +3.0000 pts'));
    }

    public function testSampleSize(): void
    {
        [$code, $output] = $this->execute(['sample-size', '--baseline', '0.1', '--mde', '0.02']);
        $this->assertSame(0, $code);
        $this->assertSame(['perVariant' => 3841], json_decode($output, true));
    }

    public function testVersionAndHelp(): void
    {
        $this->assertSame(Application::VERSION . PHP_EOL, $this->execute(['--version'])[1]);
        $this->assertTrue(str_contains($this->execute([])[1], 'Usage:'));
    }

    public function testInvalidInvocationsExitWithUsageCode(): void
    {
        $invocations = [
            ['unknown'],
            ['assign'],
            ['assign', '--experiment'],
            ['assign', 'positional'],
            ['assign', '--bogus', 'x'],
            ['assign', '--unit', 'a', '--unit', 'b'],
            ['assign', '--experiment', 'e', '--variants', 'a:0,b:1', '--unit', 'u'],
            ['assign', '--experiment', 'e', '--variants', 'malformed', '--unit', 'u'],
            ['analyze', '--input', '/nonexistent', '--control', 'a'],
            ['sample-size', '--baseline', 'abc', '--mde', '0.1'],
            ['sample-size', '--baseline', 'INF', '--mde', '0.1'],
        ];
        foreach ($invocations as $arguments) {
            [$code, $output, $errors] = $this->execute($arguments);
            $this->assertSame(Application::EXIT_INVALID, $code, implode(' ', $arguments));
            $this->assertSame('', $output);
            $this->assertTrue(str_starts_with($errors, 'Error: '));
        }
    }

    public function testErrorsDoNotReflectRawInput(): void
    {
        [, , $errors] = $this->execute(['assign', "--\x1b[31mpayload", 'x']);
        $this->assertFalse(str_contains($errors, "\x1b"), 'no control characters');
    }

    public function testRejectsDuplicateExpectedWeights(): void
    {
        $path = $this->csv();
        [$code] = $this->execute(['analyze', '--input', $path, '--control', 'control', '--variants', 'control:1,control:2,treatment:1']);
        unlink($path);
        $this->assertSame(Application::EXIT_INVALID, $code);
    }

    public function testNumericVariantNamesAreSupported(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'abtest');
        file_put_contents($path, "variant,exposures,conversions\n1,1000,100\n2,1000,130\n");
        [$code, $output] = $this->execute(['analyze', '--input', $path, '--control', '1', '--variants', '1:50,2:50', '--format', 'json']);
        unlink($path);
        $this->assertSame(0, $code);
        $this->assertSame('2', json_decode($output, true)['comparisons'][0]['variant']);
    }

    public function testRejectsInvalidFormat(): void
    {
        $path = $this->csv();
        [$code] = $this->execute(['analyze', '--input', $path, '--control', 'control', '--format', 'xml']);
        unlink($path);
        $this->assertSame(Application::EXIT_INVALID, $code);
    }
}
