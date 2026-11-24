<?php

declare(strict_types=1);

namespace AbTesting\Cli;

use AbTesting\Analysis\ExperimentAnalyzer;
use AbTesting\Analysis\SampleSizeCalculator;
use AbTesting\Assignment\VariantAssigner;
use AbTesting\Domain\Experiment;
use AbTesting\Domain\InvalidInput;
use AbTesting\Domain\Variant;

final class Application
{
    public const string VERSION = '1.0.0';

    public const int EXIT_OK = 0;
    public const int EXIT_INVALID = 2;

    private const string USAGE = <<<'TEXT'
    Usage:
      abtest assign --experiment KEY --variants name:weight,name:weight --unit ID
      abtest analyze --input FILE.csv --control NAME [--alpha 0.05] [--variants name:weight,...] [--format text|json]
      abtest sample-size --baseline RATE --mde EFFECT [--alpha 0.05] [--power 0.8]
      abtest --version
      abtest --help

    TEXT;

    public function __construct(private $output, private $errors)
    {
    }

    public function run(array $arguments): int
    {
        $command = $arguments[0] ?? '--help';
        $rest = array_slice($arguments, 1);
        try {
            return match ($command) {
                'assign' => $this->assign($rest),
                'analyze' => $this->analyze($rest),
                'sample-size' => $this->sampleSize($rest),
                '--version' => $this->write(self::VERSION . PHP_EOL),
                '--help', 'help' => $this->write(self::USAGE),
                default => throw new UsageError('Unknown command.'),
            };
        } catch (UsageError | InvalidInput $error) {
            fwrite($this->errors, 'Error: ' . $error->getMessage() . PHP_EOL);
            return self::EXIT_INVALID;
        }
    }

    private function assign(array $arguments): int
    {
        $options = Options::parse($arguments, ['experiment', 'variants', 'unit']);
        $experiment = new Experiment($options->required('experiment'), VariantSpecParser::parse($options->required('variants')));
        $assignment = (new VariantAssigner())->assign($experiment, $options->required('unit'));
        return $this->write(json_encode([
            'experiment' => $assignment->experiment,
            'variant' => $assignment->variant,
            'bucket' => $assignment->bucket,
        ], JSON_THROW_ON_ERROR) . PHP_EOL);
    }

    private function analyze(array $arguments): int
    {
        $options = Options::parse($arguments, ['input', 'control', 'alpha', 'variants', 'format']);
        $format = $options->optional('format', 'text');
        if ($format !== 'text' && $format !== 'json') {
            throw new UsageError('Format must be text or json.');
        }
        $weights = null;
        if ($options->has('variants')) {
            $weights = [];
            foreach (VariantSpecParser::parse($options->required('variants')) as $variant) {
                $weights[$variant->name] = $variant->weight;
            }
        }
        $observations = (new ObservationCsvReader())->read($options->required('input'));
        $report = (new ExperimentAnalyzer())->analyze($observations, $options->required('control'), $options->float('alpha', 0.05), $weights);
        $formatter = new ReportFormatter();
        return $this->write($format === 'json' ? $formatter->toJson($report) : $formatter->toText($report));
    }

    private function sampleSize(array $arguments): int
    {
        $options = Options::parse($arguments, ['baseline', 'mde', 'alpha', 'power']);
        $size = (new SampleSizeCalculator())->perVariant(
            $options->float('baseline'),
            $options->float('mde'),
            $options->float('alpha', 0.05),
            $options->float('power', 0.8),
        );
        return $this->write(json_encode(['perVariant' => $size], JSON_THROW_ON_ERROR) . PHP_EOL);
    }

    private function write(string $text): int
    {
        fwrite($this->output, $text);
        return self::EXIT_OK;
    }
}
