<?php

declare(strict_types=1);

namespace AbTesting\Assignment;

use AbTesting\Domain\Experiment;
use AbTesting\Domain\InvalidInput;
use LogicException;

final class VariantAssigner
{
    public const int BUCKETS = 10_000;
    public const int MAX_UNIT_BYTES = 256;

    private const int HASH_SPACE = 4_294_967_296;

    public function assign(Experiment $experiment, string $unitId): Assignment
    {
        $bucket = $this->bucket($experiment->key, $unitId);
        $cumulativeWeight = 0;
        foreach ($experiment->variants as $variant) {
            $cumulativeWeight += $variant->weight;
            if ($bucket * $experiment->totalWeight < $cumulativeWeight * self::BUCKETS) {
                return new Assignment($experiment->key, $variant->name, $bucket);
            }
        }
        throw new LogicException('Bucket allocation did not cover the full range.');
    }

    public function bucket(string $experimentKey, string $unitId): int
    {
        $unitLength = strlen($unitId);
        if ($unitLength === 0 || $unitLength > self::MAX_UNIT_BYTES) {
            throw new InvalidInput(sprintf('Unit identifier must be 1-%d bytes.', self::MAX_UNIT_BYTES));
        }
        $message = strlen($experimentKey) . ':' . $experimentKey . ':' . $unitLength . ':' . $unitId;
        $prefix = unpack('N', hash('sha256', $message, true));
        if ($prefix === false || !is_int($prefix[1] ?? null)) {
            throw new LogicException('Unable to decode hash prefix.');
        }
        return intdiv($prefix[1] * self::BUCKETS, self::HASH_SPACE);
    }
}
