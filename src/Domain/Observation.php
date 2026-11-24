<?php

declare(strict_types=1);

namespace AbTesting\Domain;

final readonly class Observation
{
    public const int MAX_COUNT = 999_999_999_999_999;

    public function __construct(public string $variant, public int $exposures, public int $conversions)
    {
        Identifier::assertValid($variant, 'Variant name');
        if ($exposures < 1 || $exposures > self::MAX_COUNT) {
            throw new InvalidInput('Exposures must be a positive count within the supported range.');
        }
        if ($conversions < 0 || $conversions > $exposures) {
            throw new InvalidInput('Conversions must be between 0 and the number of exposures.');
        }
    }

    public function rate(): float
    {
        return $this->conversions / $this->exposures;
    }
}
