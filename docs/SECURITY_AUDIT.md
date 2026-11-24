# Security Audit

Audit date: 2026-11-23. Scope: the full repository tree and the history of the `modernize/2026` branch. This was an internal review, not an independent third-party audit.

## Findings before modernization

The repository held only `README.md` and `LICENSE`. It contained no executable code, dependencies, credentials, private keys, or build artifacts.

## Findings in the 2026 implementation, now fixed

| Severity | Finding | Fix |
| --- | --- | --- |
| Medium | A very small `--mde` caused an uncaught division error that exposed a stack trace with file paths. | Out-of-range results are rejected with exit code 2. |
| Medium | Sample sizes beyond the integer range, or from an extremely small power, printed a meaningless number with exit code 0. | The result is range-checked and quantiles stay finite. |
| Medium | A variant with a very small weight could silently receive no traffic. | Assignment rejects variants below 1/10,000 of the total weight. |
| Medium | Power below roughly alpha/2 produced sample sizes that grew as power fell. | Such a power is rejected. |
| Low | The 64 KiB limit relied on the reported file size, which can understate the content. | The limit is enforced on the bytes actually read. |
| Low | Library callers could pass zero, negative, or non-integer expected weights. | Expected weights are validated. |
| Low | Numeric-looking variant names such as `01` and `1` could be wrongly rejected. | Names are compared as strings. |
| Low | The test harness ignored PHP warnings. | Warnings now fail the test. |

## Controls in the 2026 implementation

| Area | Control |
| --- | --- |
| Injection | No SQL, shell, or `eval` in runtime code. The schema tests locate `sqlite3` with a fixed shell command, then run it with an argument array and fixed test statements. |
| Terminal and log injection | Identifiers are allow-listed, and error messages do not reflect raw input. |
| CSV | Strict header and numeric parsing. Variant names that begin with formula characters are rejected. |
| Resource limits | 64 KiB input, 20 variants, 256-byte unit identifiers, bounded counts and weights. |
| Privacy | Unit identifiers are never printed or stored by the tool. |
| Cryptography | SHA-256 is used for allocation only and is documented as not being a security control. |
| Supply chain | No third-party runtime dependencies. PHPStan is pinned and locked. Composer plugins and scripts are disabled in CI. |
| CI | Read-only token, credentials not persisted, and actions pinned to full commit SHAs. |

## Not applicable

Authentication, two-factor authentication, sessions, CSRF, and XSS do not apply. The project has no web interface, accounts, or network listener.

## Verification performed

- Automated test suite, see `reports/tests/summary.md`.
- PHPStan at level 5 with no errors.
- `composer audit` reported no advisories for the locked dependency.
- `gitleaks` with redaction reported no findings.
- GitHub Actions passed on PHP 8.4 and 8.5 for the commit released as 1.0.0.

## Known limitations

- PHPStan runs at level 5 because the project does not use docblock type annotations.
- The schema has been tested on SQLite only.

## Open issues

- CI installs the current Composer release instead of a pinned version.
