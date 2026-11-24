<?php

declare(strict_types=1);

namespace AbTesting\Assignment;

final readonly class Assignment
{
    public function __construct(public string $experiment, public string $variant, public int $bucket)
    {
    }
}
