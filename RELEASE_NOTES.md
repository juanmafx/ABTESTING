# Release Notes: 1.0.1

Released on 2026-11-24.

This is a documentation-only patch release. It corrects the release notes and security audit so that they describe the published 1.0.0 release accurately. There is no change in code behaviour.

## Highlights of the 1.x line

- Stable, weighted assignment with no assignment storage.
- Conversion analysis with Wilson intervals, a two-proportion z-test, and Holm correction.
- Sample ratio mismatch detection.
- Sample size planning.
- No third-party runtime dependencies.

## Requirements

64-bit PHP 8.4 or newer.

## Verification

See `reports/tests/summary.md` for the recorded test run. GitHub Actions passed on PHP 8.4 and 8.5 for the commit released as 1.0.0.

## Upgrade notes

No migration is needed from 1.0.0.
