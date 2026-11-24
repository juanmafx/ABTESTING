# Release Notes: 1.0.0

Status: prepared, not yet tagged or released.

This is the first functional release. The repository was created in 2015 with a description and a license, and the implementation was added in 2026.

## Highlights

- Stable, weighted assignment with no assignment storage.
- Conversion analysis with Wilson intervals, a two-proportion z-test, and Holm correction.
- Sample ratio mismatch detection.
- Sample size planning.
- No third-party runtime dependencies.

## Requirements

64-bit PHP 8.4 or newer.

## Verification

See `reports/tests/summary.md` for the recorded test run on PHP 8.5. GitHub Actions passed on PHP 8.4 and 8.5 for the initial implementation commit `4d7bf93`. Later commits have not run in CI.

## Upgrade notes

There is no earlier functional version, so no migration is needed.
