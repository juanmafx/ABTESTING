<?php

declare(strict_types=1);

namespace AbTesting\Cli;

use AbTesting\Domain\Experiment;
use AbTesting\Domain\Variant;

final class VariantSpecParser
{
    public static function parse(string $specification): array
    {
        $entries = explode(',', $specification);
        if (count($entries) > Experiment::MAX_VARIANTS) {
            throw new UsageError(sprintf('At most %d variants are supported.', Experiment::MAX_VARIANTS));
        }
        $variants = [];
        $seen = [];
        foreach ($entries as $entry) {
            if (preg_match('/\A([^:]+):(\d{1,7})\z/', $entry, $matches) !== 1) {
                throw new UsageError('Variants must be formatted as name:weight,name:weight.');
            }
            if (isset($seen[$matches[1]])) {
                throw new UsageError('Variant names must be unique.');
            }
            $seen[$matches[1]] = true;
            $variants[] = new Variant($matches[1], (int) $matches[2]);
        }
        return $variants;
    }
}
