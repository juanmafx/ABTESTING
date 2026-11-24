# Security Audit

Audit date: 2026-11-23. Scope: the full repository tree and its single-commit history at the time of modernization. This was an internal review, not an independent third-party audit.

## Findings before modernization

The repository held only `README.md` and `LICENSE`. It contained no executable code, dependencies, credentials, private keys, or build artifacts.

## Controls in the 2026 implementation

| Area | Control |
| --- | --- |
| Injection | No SQL, shell, or `eval` in runtime code. The schema tests call `sqlite3` with an argument array and fixed test statements. |
| Terminal and log injection | Identifiers are allow-listed, and error messages do not reflect raw input. |
| CSV | Strict header and numeric parsing. Variant names that begin with formula characters are rejected. |
| Resource limits | 64 KiB input, 20 variants, 256-byte unit identifiers, bounded counts and weights. |
| Privacy | Unit identifiers are never printed or stored by the tool. |
| Hashing | SHA-256 for allocation only, documented as not being a security control. |
| Supply chain | No runtime dependencies. PHPStan is pinned and locked. Composer plugins and scripts are disabled in CI. |
| CI | Read-only token, credentials not persisted, and actions pinned to full commit SHAs. |

## Verification performed

- Automated test suite, see `reports/tests/summary.md`.
- PHPStan at level 5 with no errors.
- `composer audit` reported no advisories for the locked dependency.

## Known limitations

- PHPStan runs at level 5 because the project does not use docblock type annotations.
- The CI workflow has not yet run on GitHub.
- The schema has been tested on SQLite only.

## Open issues

None known.
