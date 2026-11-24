<?php

declare(strict_types=1);

namespace AbTesting\Domain;

final readonly class Variant
{
    public const int MAX_WEIGHT = 1_000_000;

    public function __construct(public string $name, public int $weight)
    {
        Identifier::assertValid($name, 'Variant name');
        if ($weight < 1 || $weight > self::MAX_WEIGHT) {
            throw new InvalidInput(sprintf('Variant weight must be between 1 and %d.', self::MAX_WEIGHT));
        }
    }
}
