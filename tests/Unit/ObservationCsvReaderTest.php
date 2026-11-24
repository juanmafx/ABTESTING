<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Cli\ObservationCsvReader;
use AbTesting\Domain\InvalidInput;
use AbTesting\Tests\Support\TestCase;

final class ObservationCsvReaderTest extends TestCase
{
    private function withFile(string $content, callable $action): void
    {
        $path = tempnam(sys_get_temp_dir(), 'abtest');
        file_put_contents($path, $content);
        try {
            $action($path);
        } finally {
            unlink($path);
        }
    }

    public function testReadsValidFile(): void
    {
        $this->withFile("variant,exposures,conversions\ncontrol,100,10\n\ntreatment,100,12\n", function (string $path): void {
            $rows = (new ObservationCsvReader())->read($path);
            $this->assertSame(2, count($rows));
            $this->assertSame('treatment', $rows[1]->variant);
            $this->assertSame(12, $rows[1]->conversions);
        });
    }

    public function testAcceptsByteOrderMark(): void
    {
        $this->withFile("\xEF\xBB\xBFvariant,exposures,conversions\na,10,1\nb,10,2\n", function (string $path): void {
            $this->assertSame(2, count((new ObservationCsvReader())->read($path)));
        });
    }

    public function testRejectsMalformedContent(): void
    {
        $header = "variant,exposures,conversions\n";
        $cases = ['', "wrong,header,row\n", $header . "a,10\n", $header . "a,10,1,9\n", $header . "a,ten,1\n", $header . "a,-1,0\n", $header . "a,1e3,0\n", $header . "=cmd(),10,1\n", $header . "a,10,11\n"];
        foreach ($cases as $content) {
            $this->withFile($content, function (string $path): void {
                $this->assertThrows(InvalidInput::class, static fn () => (new ObservationCsvReader())->read($path));
            });
        }
    }

    public function testRejectsTooManyRows(): void
    {
        $content = "variant,exposures,conversions\n";
        for ($i = 0; $i < 21; $i++) {
            $content .= "v$i,10,1\n";
        }
        $this->withFile($content, function (string $path): void {
            $this->assertThrows(InvalidInput::class, static fn () => (new ObservationCsvReader())->read($path));
        });
    }

    public function testRejectsOversizedFile(): void
    {
        $this->withFile(str_repeat('x', ObservationCsvReader::MAX_BYTES + 1), function (string $path): void {
            $this->assertThrows(InvalidInput::class, static fn () => (new ObservationCsvReader())->read($path));
        });
    }

    public function testRejectsDirectoriesAndMissingFiles(): void
    {
        $reader = new ObservationCsvReader();
        $this->assertThrows(InvalidInput::class, static fn () => $reader->read(sys_get_temp_dir()));
        $this->assertThrows(InvalidInput::class, static fn () => $reader->read(sys_get_temp_dir() . '/abtest-missing-file.csv'));
    }
}
