<?php

declare(strict_types=1);

namespace AbTesting\Domain;

final class Identifier
{
    private const string PATTERN = '/\A[A-Za-z0-9][A-Za-z0-9_.-]{0,63}\z/';

    public static function assertValid(string $value, string $label): void
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidInput(sprintf('%s must be 1-64 characters of letters, digits, dot, dash, or underscore.', $label));
        }
    }
}
