# Release Notes: 1.0.0

Status: prepared, not yet tagged or published.

This is the first functional release. The repository was created in 2015 with a description and a license, and the implementation was added in 2026.

## Highlights

- Stable, weighted assignment with no assignment storage.
- Conversion analysis with Wilson intervals, a two-proportion z-test, and Holm correction.
- Sample ratio mismatch detection.
- Sample size planning.
- No runtime dependencies.

## Requirements

PHP 8.4 or newer.

## Verification

See `reports/tests/summary.md` for the recorded test run. The suite was run locally on PHP 8.5. PHP 8.4 is configured in CI, which has not yet run.

## Upgrade notes

There is no earlier functional version, so no migration is needed.
