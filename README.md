# ABTESTING

A dependency-free PHP 8.4+ library and command-line tool for running A/B tests: deterministic variant assignment, sample size planning, and statistically careful analysis of conversion experiments.

> **Project history:** originally created in 2015, modernized in 2026. The 2015 repository contained only a short description and the license. The implementation in this repository was added in 2026 to deliver the purpose that description stated: open source A/B testing in PHP.

## Why this project exists

A/B tests fail quietly. A biased split, an unnoticed traffic imbalance, or several variants compared without correction can all produce a confident and wrong decision. This project keeps the core mechanics small enough to read and audit, and makes those common mistakes visible by default.

## What it does

| Capability | Method |
| --- | --- |
| Variant assignment | SHA-256 bucketing into 10,000 buckets, stable per experiment and unit, with integer weights |
| Conversion rate intervals | Wilson score interval |
| Treatment versus control | Two-sided pooled two-proportion z-test, with an unpooled interval for the difference |
| Multiple variants | Holm step-down correction of p-values |
| Traffic health | Sample ratio mismatch check using a chi-square goodness-of-fit test, flagged below p = 0.001 |
| Planning | Required sample size per variant for a target effect, alpha, and power |

It does not serve traffic, collect events, or provide a dashboard. It is a library and CLI meant to sit inside an application or analysis workflow.

## Architecture

```text
bin/abtest          CLI entry point
src/Domain/         Validated value objects
src/Assignment/     Deterministic bucketing and allocation
src/Statistics/     Normal and chi-square distributions
src/Analysis/       Experiment analysis and sample size planning
src/Cli/            Argument parsing, CSV input, report formatting
db/schema.sql       Optional persistence schema
tests/              Test harness and test suites
reports/tests/      Report from an actual test run
```

There is no client or server application, so the repository has no `frontend/` or `backend/` split. See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Quick start

Requirements: 64-bit PHP 8.4 or newer. The tool has no third-party runtime dependencies.

```bash
git clone https://github.com/juanmafx/ABTESTING.git
cd ABTESTING
php bin/abtest analyze --input examples/observations.csv --control control
```

The file in `examples/` is synthetic sample data.

## Usage guide

### 1. Plan the sample size

```bash
php bin/abtest sample-size --baseline 0.10 --mde 0.02 --alpha 0.05 --power 0.8
```

```json
{"perVariant":3841}
```

`--mde` is the absolute change in conversion rate you want to detect.

### 2. Assign units to variants

```bash
php bin/abtest assign --experiment checkout --variants control:50,treatment:50 --unit user-1
```

```json
{"experiment":"checkout","variant":"treatment","bucket":6718}
```

The same experiment and unit always produce the same variant, so no assignment storage is required. Changing the weights or the variant order can move units between variants, so keep them fixed while an experiment runs.

### 3. Analyze the results

Provide a CSV file with this exact header:

```csv
variant,exposures,conversions
control,10000,1000
treatment,10000,1100
```

```bash
php bin/abtest analyze --input examples/observations.csv --control control --format json
```

For an unequal split, pass the planned weights so the sample ratio check uses them:

```bash
php bin/abtest analyze --input results.csv --control control --variants control:90,treatment:10
```

### Use it as a library

```php
use AbTesting\Assignment\VariantAssigner;
use AbTesting\Domain\Experiment;
use AbTesting\Domain\Variant;

$experiment = new Experiment('checkout', [new Variant('control', 50), new Variant('treatment', 50)]);
$assignment = (new VariantAssigner())->assign($experiment, 'user-1');
```

### Reading results responsibly

- If the sample ratio check reports a mismatch, investigate the assignment or logging before trusting any comparison.
- The test is fixed-horizon. Decide the sample size first; repeatedly checking and stopping early inflates false positives.
- Significance uses Holm-adjusted p-values. The reported difference intervals are not adjusted for multiple comparisons.
- The z-test relies on a normal approximation, which is weak for very small samples or rates close to 0 or 1.

## Configuration

The tool uses command-line options only. It reads no environment variables or configuration files.

| Option | Commands | Default | Meaning |
| --- | --- | --- | --- |
| `--experiment` | assign | required | Experiment key |
| `--variants` | assign, analyze | required for assign | `name:weight` pairs |
| `--unit` | assign | required | Stable unit identifier, up to 256 bytes |
| `--input` | analyze | required | CSV file, up to 64 KiB and 20 variants |
| `--control` | analyze | required | Control variant name |
| `--alpha` | analyze, sample-size | `0.05` | Significance level |
| `--format` | analyze | `text` | `text` or `json` |
| `--baseline` | sample-size | required | Baseline conversion rate |
| `--mde` | sample-size | required | Absolute minimum detectable effect |
| `--power` | sample-size | `0.8` | Statistical power |

Exit codes: `0` for success and `2` for invalid usage or input.

Limits: `assign` requires every variant to hold at least 1/10,000 of the total weight, so that each one can receive traffic. `sample-size` rejects a power too low for the chosen alpha, and any result above 999,999,999,999,999 units.

## Database schema (optional)

The library and CLI do not use a database. [db/schema.sql](db/schema.sql) is an optional starting point for teams that want to persist experiments, assignments, conversions, and analysis runs. Its automated tests run against SQLite. It is written to be portable to PostgreSQL, but has not been run on PostgreSQL.

Store a keyed hash of each unit identifier in `unit_hash`, never the raw identifier.

## Testing

```bash
php tests/run.php
php tests/run.php --report-dir reports/tests
```

The suite uses a small in-repository harness so it runs on a bare PHP install without the XML extensions PHPUnit requires. Statistical tests compare results with fixed reference values. PHP warnings raised during a test count as failures. Schema tests are skipped if the `sqlite3` command is unavailable.

Static analysis requires the pinned development dependency:

```bash
composer install --no-scripts --no-plugins
vendor/bin/phpstan analyse --no-progress --memory-limit=512M
```

The latest committed report is in [reports/tests/summary.md](reports/tests/summary.md), with JUnit output in [reports/tests/junit.xml](reports/tests/junit.xml).

## Security notes

- All identifiers, counts, and rates are validated before use, and error messages do not echo raw input.
- Unit identifiers are hashed for bucketing and never printed.
- CSV input is limited in size and row count, and must be a regular file.
- Assignment hashing is for uniform allocation, not secrecy. Anyone who knows the experiment key and unit identifier can compute the variant, so never use it as an access control.

See [SECURITY.md](SECURITY.md) and [docs/SECURITY_AUDIT.md](docs/SECURITY_AUDIT.md).

## Versioning

The current version is 1.0.1. See [CHANGELOG.md](CHANGELOG.md) and [RELEASE_NOTES.md](RELEASE_NOTES.md).

## License

GNU General Public License, version 2. See [LICENSE](LICENSE).
