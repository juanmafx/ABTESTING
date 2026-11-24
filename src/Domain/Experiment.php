<?php

declare(strict_types=1);

namespace AbTesting\Domain;

final readonly class Experiment
{
    public const int MAX_VARIANTS = 20;

    public int $totalWeight;

    public array $variants;

    public function __construct(public string $key, array $variants)
    {
        Identifier::assertValid($key, 'Experiment key');
        $count = count($variants);
        if ($count < 2 || $count > self::MAX_VARIANTS) {
            throw new InvalidInput(sprintf('An experiment needs between 2 and %d variants.', self::MAX_VARIANTS));
        }
        $names = array_map(static fn (Variant $variant): string => $variant->name, $variants);
        if (count(array_unique($names)) !== $count) {
            throw new InvalidInput('Variant names must be unique.');
        }
        $this->variants = $variants;
        $this->totalWeight = array_sum(array_map(static fn (Variant $variant): int => $variant->weight, $variants));
    }
}
