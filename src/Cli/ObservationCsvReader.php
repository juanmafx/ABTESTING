<?php

declare(strict_types=1);

namespace AbTesting\Cli;

use AbTesting\Domain\Experiment;
use AbTesting\Domain\InvalidInput;
use AbTesting\Domain\Observation;

final class ObservationCsvReader
{
    public const int MAX_BYTES = 65_536;

    private const array HEADER = ['variant', 'exposures', 'conversions'];

    public function read(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidInput('Input must be a readable regular file.');
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidInput('Input could not be opened.');
        }
        try {
            $content = stream_get_contents($handle, self::MAX_BYTES + 1);
        } finally {
            fclose($handle);
        }
        if ($content === false || strlen($content) > self::MAX_BYTES) {
            throw new InvalidInput(sprintf('Input must not exceed %d bytes.', self::MAX_BYTES));
        }
        $buffer = fopen('php://memory', 'w+b');
        if ($buffer === false) {
            throw new InvalidInput('Input could not be buffered.');
        }
        try {
            fwrite($buffer, $content);
            rewind($buffer);
            return $this->parse($buffer);
        } finally {
            fclose($buffer);
        }
    }

    private function parse($handle): array
    {
        $header = fgetcsv($handle, self::MAX_BYTES, ',', '"', '');
        if ($header === false) {
            throw new InvalidInput('Input is empty.');
        }
        $normalized = array_map(static fn (?string $cell): string => strtolower(trim((string) $cell)), $header);
        $normalized[0] = (string) preg_replace('/\A\xEF\xBB\xBF/', '', $normalized[0]);
        if ($normalized !== self::HEADER) {
            throw new InvalidInput('Header must be exactly: variant,exposures,conversions.');
        }
        $observations = [];
        while (($row = fgetcsv($handle, self::MAX_BYTES, ',', '"', '')) !== false) {
            if ($row === [null]) {
                continue;
            }
            if (count($observations) >= Experiment::MAX_VARIANTS) {
                throw new InvalidInput(sprintf('At most %d variants are supported.', Experiment::MAX_VARIANTS));
            }
            if (count($row) !== 3) {
                throw new InvalidInput('Each row must have exactly three columns.');
            }
            $observations[] = new Observation(trim((string) $row[0]), $this->count($row[1]), $this->count($row[2]));
        }
        return $observations;
    }

    private function count(?string $cell): int
    {
        $value = trim((string) $cell);
        if (preg_match('/\A\d{1,15}\z/', $value) !== 1) {
            throw new InvalidInput('Counts must be non-negative integers of at most 15 digits.');
        }
        return (int) $value;
    }
}
