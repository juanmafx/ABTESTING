<?php

declare(strict_types=1);

namespace AbTesting\Tests\Unit;

use AbTesting\Tests\Support\TestCase;

final class SchemaTest extends TestCase
{
    private const string SEED = "INSERT INTO experiments (experiment_key, control_variant) VALUES ('e', 'a');"
        . "INSERT INTO variants VALUES ('e', 'a', 1), ('e', 'b', 1);";

    private function sqlite(string $script): array
    {
        $binary = trim((string) shell_exec('command -v sqlite3'));
        if ($binary === '') {
            $this->skip('sqlite3 command-line tool is not installed');
        }
        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/db/schema.sql');
        $process = proc_open([$binary, '-batch', ':memory:'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        fwrite($pipes[0], ".bail on\nPRAGMA foreign_keys = ON;\n" . $schema . "\n" . $script . "\n");
        fclose($pipes[0]);
        $output = (string) stream_get_contents($pipes[1]);
        $errors = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $output, $errors];
    }

    private function hash(string $character): string
    {
        return str_repeat($character, 64);
    }

    public function testSchemaCreatesAllTables(): void
    {
        [$code, $output] = $this->sqlite("SELECT group_concat(name) FROM (SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name);");
        $this->assertSame(0, $code);
        $this->assertSame('analysis_runs,assignments,conversions,experiments,variants', trim($output));
    }

    public function testSchemaIsIdempotent(): void
    {
        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/db/schema.sql');
        [$code] = $this->sqlite($schema);
        $this->assertSame(0, $code);
    }

    public function testValidLifecycleIsAccepted(): void
    {
        $hash = $this->hash('a');
        [$code, $output] = $this->sqlite(self::SEED
            . "INSERT INTO assignments (experiment_key, unit_hash, variant_key, bucket) VALUES ('e', '$hash', 'a', 10);"
            . "INSERT INTO conversions (experiment_key, unit_hash, metric_key) VALUES ('e', '$hash', 'purchase');"
            . 'SELECT count(*) FROM conversions;');
        $this->assertSame(0, $code);
        $this->assertSame('1', trim($output));
    }

    public function testConstraintsRejectInvalidRows(): void
    {
        $hash = $this->hash('b');
        $statements = [
            "INSERT INTO variants VALUES ('e', 'c', 0);",
            "INSERT INTO variants VALUES ('missing', 'c', 1);",
            "INSERT INTO assignments (experiment_key, unit_hash, variant_key, bucket) VALUES ('e', 'short', 'a', 1);",
            "INSERT INTO assignments (experiment_key, unit_hash, variant_key, bucket) VALUES ('e', '$hash', 'a', 10000);",
            "INSERT INTO assignments (experiment_key, unit_hash, variant_key, bucket) VALUES ('e', '$hash', 'unknown', 1);",
            "INSERT INTO conversions (experiment_key, unit_hash, metric_key) VALUES ('e', '$hash', 'purchase');",
            "UPDATE experiments SET status = 'invalid';",
            'UPDATE experiments SET alpha = 1.5;',
        ];
        foreach ($statements as $statement) {
            [$code] = $this->sqlite(self::SEED . $statement);
            $this->assertTrue($code !== 0, $statement);
        }
    }

    public function testDeletingExperimentCascades(): void
    {
        $hash = $this->hash('c');
        [$code, $output] = $this->sqlite(self::SEED
            . "INSERT INTO assignments (experiment_key, unit_hash, variant_key, bucket) VALUES ('e', '$hash', 'b', 5);"
            . "DELETE FROM experiments WHERE experiment_key = 'e';"
            . 'SELECT (SELECT count(*) FROM variants) + (SELECT count(*) FROM assignments);');
        $this->assertSame(0, $code);
        $this->assertSame('0', trim($output));
    }
}
