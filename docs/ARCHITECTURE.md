# Architecture

## Overview

ABTESTING is a layered PHP library with a thin CLI. Dependencies point inward: the CLI depends on analysis and assignment, those depend on the domain and statistics, and the domain depends on nothing else.

```text
              bin/abtest
                  |
               src/Cli
              /       \
    src/Assignment   src/Analysis
              \       /       \
              src/Domain   src/Statistics
```

## Module structure

| Path | Responsibility |
| --- | --- |
| `src/Domain` | `Experiment`, `Variant`, `Observation`, identifier rules, and the `InvalidInput` error |
| `src/Assignment` | `VariantAssigner` maps an experiment and unit to a bucket and variant |
| `src/Statistics` | `NormalDistribution` and `ChiSquareDistribution` |
| `src/Analysis` | `ExperimentAnalyzer`, `HolmCorrection`, `SampleSizeCalculator`, and report value objects |
| `src/Cli` | `Application`, option parsing, CSV reading, and output formatting |
| `tests/Support` | Assertions, test discovery, and JUnit and Markdown report writers |
| `tests/Unit` | Test suites for each module and the database schema |
| `db/schema.sql` | Optional relational schema |

## Key design decisions

### Stateless deterministic assignment

The bucket is derived from the first 32 bits of `SHA-256(len(key):key:len(unit):unit)`, scaled to the range 0 to 9,999. The length prefixes stop different key and unit pairs from producing the same message. Including the experiment key makes assignments independent across experiments. Weight comparisons use integer arithmetic to avoid floating-point boundary errors.

### Statistics without dependencies

Normal tail probabilities use Hart's rational approximation as published by West, with a continued fraction for the far tail. Quantiles use Acklam's approximation with one Halley refinement step. Chi-square tails use the exact recurrence for integer and half-integer shapes of the incomplete gamma function. Tests check these against independently computed values.

### Analysis defaults

- The two-proportion test uses a pooled variance, which is the correct variance under the null hypothesis.
- Difference intervals use unpooled variance, because they describe the effect rather than the null hypothesis.
- Holm correction controls the family-wise error rate and is uniformly more powerful than Bonferroni.
- The sample ratio check uses a strict threshold of 0.001, because it is a diagnostic intended to catch broken pipelines.

### No framework or runtime dependency

Runtime supply-chain exposure is zero. The only development dependency is PHPStan, pinned exactly and locked.

## Recommended production integration

```text
Application -> VariantAssigner -> exposure event -> event store
                                                        |
                                 aggregation job -> CSV or Observation objects
                                                        |
                                                 ExperimentAnalyzer -> report store
```

1. Call `VariantAssigner` in the application at the point where the experience differs.
2. Log an exposure only when the user actually sees the experience.
3. Aggregate unique exposures and conversions per variant.
4. Analyze at the planned sample size, and stop if the sample ratio check fails.

The optional schema in `db/schema.sql` models this flow. The project does not ship the event pipeline or a database adapter.
