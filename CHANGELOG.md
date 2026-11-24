# Changelog

All notable changes to this project are documented here. The format follows Keep a Changelog, and the project uses Semantic Versioning.

## [1.0.1] - 2026-11-24

### Changed

- Corrected the release notes and security audit to reflect the published 1.0.0 release and the CI run on the released commit. Documentation only, no change in code behaviour.

## [1.0.0] - 2026-11-24

### Added

- Deterministic, weighted variant assignment.
- Two-proportion z-test, Wilson intervals, difference intervals, and Holm correction.
- Sample ratio mismatch detection.
- Sample size calculator.
- `abtest` command-line tool with text and JSON output.
- Optional SQL schema.
- Automated tests with a committed report, static analysis, and CI.
- Architecture, security, and release documentation.

### Changed

- Rewrote the README in English.

### Fixed

- Reject out-of-range sample sizes instead of crashing or printing a meaningless number.
- Reject a power too low for the chosen alpha.
- Reject variants too small to receive traffic.
- Enforce the CSV size limit on the bytes actually read.
- Validate expected weights and compare variant names as strings.
- Improve quantile accuracy for upper-tail probabilities.
- Treat PHP warnings as test failures.

## Repository created - 2015-05-16

### Added

- Initial repository with a short description and the GPL-2.0 license.
