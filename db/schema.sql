-- Optional persistence schema for experiments, assignments, conversions, and analysis runs.
-- Portable across SQLite 3.35+ and PostgreSQL 13+. In SQLite, run PRAGMA foreign_keys = ON per connection.

CREATE TABLE IF NOT EXISTS experiments (
    experiment_key VARCHAR(64) PRIMARY KEY,
    description TEXT NOT NULL DEFAULT '',
    status VARCHAR(16) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'running', 'stopped')),
    control_variant VARCHAR(64) NOT NULL,
    alpha DOUBLE PRECISION NOT NULL DEFAULT 0.05 CHECK (alpha > 0 AND alpha < 1),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP,
    stopped_at TIMESTAMP,
    CHECK (stopped_at IS NULL OR started_at IS NOT NULL)
);

CREATE TABLE IF NOT EXISTS variants (
    experiment_key VARCHAR(64) NOT NULL REFERENCES experiments (experiment_key) ON DELETE CASCADE,
    variant_key VARCHAR(64) NOT NULL,
    weight INTEGER NOT NULL CHECK (weight BETWEEN 1 AND 1000000),
    PRIMARY KEY (experiment_key, variant_key)
);

CREATE TABLE IF NOT EXISTS assignments (
    experiment_key VARCHAR(64) NOT NULL,
    unit_hash CHAR(64) NOT NULL CHECK (length(unit_hash) = 64),
    variant_key VARCHAR(64) NOT NULL,
    bucket INTEGER NOT NULL CHECK (bucket BETWEEN 0 AND 9999),
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (experiment_key, unit_hash),
    FOREIGN KEY (experiment_key, variant_key) REFERENCES variants (experiment_key, variant_key) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_assignments_variant ON assignments (experiment_key, variant_key);

CREATE TABLE IF NOT EXISTS conversions (
    experiment_key VARCHAR(64) NOT NULL,
    unit_hash CHAR(64) NOT NULL,
    metric_key VARCHAR(64) NOT NULL,
    converted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (experiment_key, unit_hash, metric_key),
    FOREIGN KEY (experiment_key, unit_hash) REFERENCES assignments (experiment_key, unit_hash) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_conversions_metric ON conversions (experiment_key, metric_key);

CREATE TABLE IF NOT EXISTS analysis_runs (
    experiment_key VARCHAR(64) NOT NULL REFERENCES experiments (experiment_key) ON DELETE CASCADE,
    metric_key VARCHAR(64) NOT NULL,
    run_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tool_version VARCHAR(32) NOT NULL,
    report_json TEXT NOT NULL,
    PRIMARY KEY (experiment_key, metric_key, run_at)
);
