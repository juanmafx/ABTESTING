<?php

declare(strict_types=1);

namespace AbTesting\Cli;

final readonly class Options
{
    private function __construct(private array $values)
    {
    }

    public static function parse(array $arguments, array $allowed): self
    {
        $values = [];
        $count = count($arguments);
        for ($index = 0; $index < $count; $index++) {
            $argument = $arguments[$index];
            if (!str_starts_with($argument, '--')) {
                throw new UsageError('Unexpected positional argument.');
            }
            $parts = explode('=', substr($argument, 2), 2);
            $name = $parts[0];
            if (!in_array($name, $allowed, true)) {
                throw new UsageError('Unknown option.');
            }
            if (isset($values[$name])) {
                throw new UsageError('Option provided more than once.');
            }
            if (isset($parts[1])) {
                $values[$name] = $parts[1];
                continue;
            }
            $index++;
            if ($index >= $count) {
                throw new UsageError(sprintf('Option --%s requires a value.', $name));
            }
            $values[$name] = $arguments[$index];
        }
        return new self($values);
    }

    public function required(string $name): string
    {
        return $this->values[$name] ?? throw new UsageError(sprintf('Missing required option --%s.', $name));
    }

    public function optional(string $name, string $default): string
    {
        return $this->values[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return isset($this->values[$name]);
    }

    public function float(string $name, ?float $default = null): float
    {
        $raw = $this->values[$name] ?? null;
        if ($raw === null) {
            return $default ?? throw new UsageError(sprintf('Missing required option --%s.', $name));
        }
        $value = filter_var($raw, FILTER_VALIDATE_FLOAT);
        if ($value === false || !is_finite($value)) {
            throw new UsageError(sprintf('Option --%s must be a number.', $name));
        }
        return $value;
    }
}
