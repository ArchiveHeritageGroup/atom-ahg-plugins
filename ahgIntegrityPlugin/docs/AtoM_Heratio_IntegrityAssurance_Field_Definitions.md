# AtoM Heratio -- Integrity Assurance Plugin: Field Definitions

**Version:** 1.2.0
**Author:** The Archive and Heritage Group (Pty) Ltd
**Plugin:** ahgIntegrityPlugin
**Database Engine:** MySQL 8.0+

---

## Overview

This document provides a complete field-level reference for all eight database tables in the Integrity Assurance plugin. Each table is described with its purpose, followed by a column-by-column breakdown including data type, nullable status, default value, and a description of the field's role.

All tables use the InnoDB storage engine with `utf8mb4` character set and `utf8mb4_unicode_ci` collation.

---

## Table of Contents

1. [integrity_schedule](#1-integrity_schedule)
2. [integrity_run](#2-integrity_run)
3. [integrity_ledger](#3-integrity_ledger)
4. [integrity_dead_letter](#4-integrity_dead_letter)
5. [integrity_retention_policy](#5-integrity_retention_policy)
6. [integrity_legal_hold](#6-integrity_legal_hold)
7. [integrity_disposition_queue](#7-integrity_disposition_queue)
8. [integrity_alert_config](#8-integrity_alert_config)

---

## 1. integrity_schedule

**Purpose:** Defines verification schedule configurations that govern when and how fixity checks are executed. Each schedule targets a specific scope (global, repository, or hierarchy), specifies the hashing algorithm, frequency, resource limits, and notification preferences.

| Column | Data Type | Nullable | Default | Description |
|--------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | NOT NULL | Auto-generated | Primary key. Unique identifier for the schedule definition. |
| name | VARCHAR(255) | NOT NULL | -- | Human-readable name for the schedule (e.g., "Weekly Full Scan", "Daily Repository Check"). |
| description | TEXT | NULL | NULL | Optional free-text description explaining the purpose or scope of this schedule. |
| scope_type | VARCHAR(20) | NOT NULL | `'global'` | Determines the breadth of the verification scan. Valid values: `global` (all digital objects), `repository` (single repository), `hierarchy` (subtree beneath a specific information object). |
| repository_id | INT | NULL | NULL | Foreign key reference to the repository to scan. Required when `scope_type` is `repository`; ignored otherwise. |
| information_object_id | INT | NULL | NULL | Foreign key reference to the root information object for hierarchy scans. Required when `scope_type` is `hierarchy`; ignored otherwise. |
| algorithm | VARCHAR(10) | NOT NULL | `'sha256'` | Cryptographic hash algorithm used for fixity verification. Valid values: `sha256`, `sha512`. |
| frequency | VARCHAR(20) | NOT NULL | `'weekly'` | How often the schedule should execute. Valid values: `daily`, `weekly`, `monthly`, `ad_hoc`. Ad-hoc schedules are only triggered manually or via API. |
| cron_expression | VARCHAR(100) | NULL | NULL | Optional cron expression for fine-grained scheduling (e.g., `0 2 * * 0` for Sundays at 02:00). When provided, this overrides the `frequency` field for scheduling purposes. |
| batch_size | INT UNSIGNED | NOT NULL | `200` | Number of digital objects to process per batch iteration. Controls memory usage and allows progress checkpointing between batches. |
| io_throttle_ms | INT UNSIGNED | NOT NULL | `0` | Milliseconds to pause between individual file reads. A value of `0` means no throttling. Use this to reduce I/O contention on shared storage (e.g., NAS mounts). |
| max_memory_mb | INT UNSIGNED | NOT NULL | `512` | Maximum memory allocation in megabytes for a single run of this schedule. The runner will abort gracefully if this limit is approached. |
| max_runtime_minutes | INT UNSIGNED | NOT NULL | `120` | Maximum wall-clock time in minutes before the run is forcibly stopped with a `timeout` status. Prevents runaway processes. |
| max_concurrent_runs | TINYINT UNSIGNED | NOT NULL | `1` | Maximum number of simultaneous runs allowed for this schedule. Enforced via lock tokens. Typically `1` to prevent duplicate scanning. |
| is_enabled | TINYINT(1) | NOT NULL | `0` | Whether this schedule is active. Disabled schedules (`0`) are skipped by the scheduler but can still be triggered manually. |
| last_run_at | DATETIME | NULL | NULL | Timestamp of the most recent run start for this schedule. Updated automatically when a run begins. |
| next_run_at | DATETIME | NULL | NULL | Computed timestamp of when this schedule should next execute. Calculated from `frequency` or `cron_expression` after each run completes. |
| total_runs | INT UNSIGNED | NOT NULL | `0` | Cumulative count of all runs executed under this schedule, regardless of outcome. Incremented at run start. |
| notify_on_failure | TINYINT(1) | NOT NULL | `1` | Whether to send email notifications when a run ends with a `failed` or `timeout` status. |
| notify_on_mismatch | TINYINT(1) | NOT NULL | `1` | Whether to send email notifications when one or more hash mismatches are detected during a run. |
| notify_email | VARCHAR(255) | NULL | NULL | Email address for notifications. If NULL, notifications fall back to the system administrator email configured in AHG Settings. |
| created_at | DATETIME | -- | -- | Timestamp when the schedule record was created. |
| updated_at | DATETIME | -- | -- | Timestamp when the schedule record was last modified. |

**Indexes:**
- PRIMARY KEY (`id`)

---

## 2. integrity_run

**Purpose:** Records each execution of a verification schedule. Every time a fixity check is triggered -- whether by the scheduler, manually, via CLI, or through the API -- a new run record is created. It tracks progress counters, timing, and outcome status.

| Column | Data Type | Nullable | Default | Description |
|--------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | NOT NULL | Auto-generated | Primary key. Unique identifier for this run execution. |
| schedule_id | BIGINT UNSIGNED | NULL | NULL | Foreign key to `integrity_schedule.id`. NULL for ad-hoc runs triggered outside a defined schedule. |
| status | VARCHAR(20) | NOT NULL | `'running'` | Current state of the run. Valid values: `running` (in progress), `completed` (finished successfully), `partial` (stopped early but some results recorded), `failed` (terminated due to error), `timeout` (exceeded `max_runtime_minutes`), `cancelled` (stopped by user). |
| algorithm | VARCHAR(10) | -- | -- | Hash algorithm used for this specific run. Copied from the schedule at run start to preserve an immutable record even if the schedule is later changed. |
| objects_scanned | INT UNSIGNED | -- | `0` | Total number of digital objects processed during this run, regardless of outcome. |
| objects_passed | INT UNSIGNED | -- | `0` | Number of objects whose computed hash matched the expected baseline hash. |
| objects_failed | INT UNSIGNED | -- | `0` | Number of objects with a hash mismatch (computed hash differs from expected hash). |
| objects_missing | INT UNSIGNED | -- | `0` | Number of objects whose referenced file could not be found on disk. |
| objects_error | INT UNSIGNED | -- | `0` | Number of objects that encountered errors during verification (e.g., permission denied, I/O failure, corrupted read). |
| objects_skipped | INT UNSIGNED | -- | `0` | Number of objects skipped during the run (e.g., no baseline hash available, file path empty, excluded by filter). |
| bytes_scanned | BIGINT UNSIGNED | -- | `0` | Cumulative size in bytes of all files read during this run. Useful for throughput analysis and I/O impact assessment. |
| triggered_by | VARCHAR(20) | -- | `'manual'` | How this run was initiated. Valid values: `scheduler` (automated cron), `manual` (web UI button), `cli` (command line), `api` (REST/GraphQL endpoint). |
| triggered_by_user | VARCHAR(255) | NULL | NULL | Username or identifier of the person or system that initiated the run. NULL for automated scheduler triggers. |
| lock_token | VARCHAR(64) | NULL | NULL | Unique token used for concurrency control. Prevents multiple instances of the same schedule from running simultaneously. Cleared on run completion. |
| error_message | TEXT | NULL | NULL | Detailed error message if the run ended with `failed` or `timeout` status. NULL for successful runs. |
| started_at | DATETIME | -- | -- | Timestamp when the run began processing. |
| completed_at | DATETIME | NULL | NULL | Timestamp when the run finished. NULL while the run is still in progress. |
| created_at | DATETIME | -- | -- | Timestamp when the run record was inserted. Typically identical to `started_at`. |

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`schedule_id`) REFERENCES `integrity_schedule`(`id`) ON DELETE SET NULL

---

## 3. integrity_ledger

**Purpose:** The append-only verification ledger. Every individual file verification produces exactly one ledger entry, creating a permanent, auditable record of every fixity check ever performed. Ledger entries are never updated or deleted, ensuring a tamper-evident audit trail. Supports chain verification via the `previous_hash` field (Issue #188).

| Column | Data Type | Nullable | Default | Description |
|--------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | NOT NULL | Auto-generated | Primary key. Unique, sequential identifier for this ledger entry. |
| run_id | BIGINT UNSIGNED | NULL | NULL | Foreign key to `integrity_run.id`. Links this entry to the run that produced it. NULL if the verification was performed outside a formal run context. |
| digital_object_id | INT | NOT NULL | -- | Foreign key reference to the `digital_object.id` being verified. Every ledger entry must reference a specific digital object. |
| information_object_id | INT | NULL | NULL | Foreign key reference to the parent `information_object.id`. Denormalized for efficient reporting and filtering by archival description. |
| repository_id | INT | NULL | NULL | Foreign key reference to the `repository.id` that holds this object. Denormalized for repository-scoped reporting. |
| file_path | VARCHAR(1024) | NULL | NULL | Absolute filesystem path to the file that was verified at the time of the check. Recorded for audit purposes even if the path has since changed. |
| file_size | BIGINT UNSIGNED | NULL | NULL | Size of the file in bytes as reported by the filesystem at verification time. NULL if the file was missing or unreadable. |
| file_exists | TINYINT(1) | -- | `0` | Whether the file existed on disk at the time of verification. `1` = exists, `0` = not found. |
| file_readable | TINYINT(1) | -- | `0` | Whether the file was readable (sufficient permissions) at the time of verification. `1` = readable, `0` = not readable or not found. |
| algorithm | VARCHAR(10) | -- | -- | Hash algorithm used for this specific verification (e.g., `sha256`, `sha512`). Recorded per entry for long-term consistency even if the schedule's algorithm changes. |
| expected_hash | VARCHAR(128) | NULL | NULL | The baseline hash value that was expected for this file. Sourced from the digital object's stored checksum. NULL if no baseline existed at verification time. |
| computed_hash | VARCHAR(128) | NULL | NULL | The hash value computed by reading and hashing the file during this verification. NULL if the file was missing, unreadable, or an error occurred before hashing completed. |
| hash_match | TINYINT(1) | NULL | NULL | Whether `computed_hash` matched `expected_hash`. `1` = match, `0` = mismatch, NULL = comparison not possible (e.g., missing file, no baseline). |
| outcome | VARCHAR(30) | NOT NULL | -- | Final result classification for this verification. Valid values: `pass` (hash matches baseline), `mismatch` (hash differs from baseline), `missing` (file not found on disk), `unreadable` (file exists but cannot be read), `permission_error` (insufficient filesystem permissions), `path_drift` (file found at a different path than expected), `no_baseline` (no expected hash stored for comparison), `error` (unexpected error during processing). |
| error_detail | TEXT | NULL | NULL | Detailed error information for non-pass outcomes. Contains exception messages, filesystem errors, or diagnostic context. NULL for `pass` outcomes. |
| duration_ms | INT UNSIGNED | NULL | NULL | Time in milliseconds taken to verify this individual file, including disk read and hash computation. Useful for identifying slow storage or large files. |
| actor | VARCHAR(255) | NULL | NULL | Username or system identity that performed or initiated this verification. Provides accountability in the audit trail. |
| hostname | VARCHAR(255) | NULL | NULL | Server hostname where the verification was executed. Important in multi-server or clustered environments. |
| previous_hash | VARCHAR(128) | NULL | NULL | The hash of the preceding ledger entry for the same digital object, forming a verification chain (Issue #188). Enables detection of ledger tampering by verifying chain continuity. NULL for the first entry of a given digital object. |
| verified_at | DATETIME | -- | -- | Timestamp when this individual verification was performed. This is the authoritative time of the fixity check. |

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`run_id`) REFERENCES `integrity_run`(`id`) ON DELETE SET NULL
- INDEX on `digital_object_id` for per-object history queries
- INDEX on `outcome` for failure reporting
- INDEX on `verified_at` for time-range queries

---

## 4. integrity_dead_letter

**Purpose:** Persistent failure queue that tracks digital objects experiencing repeated verification failures. When an object fails verification, a dead-letter entry is created or updated. This table enables triage workflows: failures can be acknowledged, investigated, retried, resolved, or ignored. Consecutive failure counting and automatic retry scheduling support both automated remediation and manual intervention.

| Column | Data Type | Nullable | Default | Description |
|--------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | NOT NULL | Auto-generated | Primary key. Unique identifier for this dead-letter entry. |
| digital_object_id | INT | NOT NULL | -- | Foreign key reference to the `digital_object.id` that is persistently failing verification. One dead-letter entry per digital object (upserted on repeated failures). |
| failure_type | VARCHAR(30) | NOT NULL | -- | Classification of the failure. Valid values: `mismatch`, `missing`, `unreadable`, `permission_error`, `path_drift`, `no_baseline`, `error`. Same values as `integrity_ledger.outcome` excluding `pass`. |
| status | VARCHAR(20) | -- | `'open'` | Current triage status of this dead-letter entry. Valid values: `open` (new or unacknowledged failure), `acknowledged` (someone has seen it), `investigating` (actively being diagnosed), `resolved` (issue fixed, verification now passes), `ignored` (accepted risk, no further action). |
| consecutive_failures | INT UNSIGNED | -- | `1` | Number of consecutive verification failures for this digital object without an intervening pass. Reset to `0` when the object passes verification. |
| first_failure_at | DATETIME | -- | -- | Timestamp of the first failure in the current consecutive failure sequence. Reset when the object passes and subsequently fails again. |
| last_failure_at | DATETIME | -- | -- | Timestamp of the most recent failure. Updated each time the object fails verification. |
| last_error_detail | TEXT | NULL | NULL | Error detail from the most recent failure. Copied from the corresponding `integrity_ledger.error_detail` for quick reference without joining. |
| last_run_id | BIGINT UNSIGNED | NULL | NULL | Foreign key to `integrity_run.id` of the most recent run in which this object failed. Enables drill-down to the full run context. |
| retry_count | INT UNSIGNED | -- | `0` | Number of automated retry attempts made for this dead-letter entry. Incremented each time the system schedules a retry. |
| max_retries | INT UNSIGNED | -- | `3` | Maximum number of automated retries before the entry requires manual intervention. Configurable per entry; defaults to `3`. |
| next_retry_at | DATETIME | NULL | NULL | Scheduled timestamp for the next automated retry attempt. NULL if retries are exhausted or the entry has been resolved/ignored. |
| acknowledged_by | VARCHAR(255) | NULL | NULL | Username of the person who acknowledged this failure. NULL until the entry is acknowledged. |
| acknowledged_at | DATETIME | NULL | NULL | Timestamp when the entry was acknowledged. NULL until acknowledged. |
| resolution_notes | TEXT | NULL | NULL | Free-text notes describing how the issue was resolved. Intended for audit and knowledge-sharing purposes. |
| resolved_at | DATETIME | NULL | NULL | Timestamp when the entry was marked as resolved. NULL until resolved. |
| created_at | DATETIME | -- | -- | Timestamp when the dead-letter entry was first created. |
| updated_at | DATETIME | -- | -- | Timestamp when the dead-letter entry was last modified (failure count update, status change, etc.). |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE INDEX on `digital_object_id` (one active entry per object)
- FOREIGN KEY (`last_run_id`) REFERENCES `integrity_run`(`id`) ON DELETE SET NULL
- INDEX on `status` for triage filtering

---

## 5. integrity_retention_policy

**Purpose:** Defines retention period rules that determine how long digital objects should be preserved before becoming eligible for disposition review. Policies can be scoped globally, per repository, or per hierarchy subtree, and can optionally filter by MIME type. The trigger type specifies which date field is used to calculate retention expiry.

| Column | Data Type | Nullable | Default | Description |
|--------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | NOT NULL | Auto-generated | Primary key. Unique identifier for this retention policy. |
| name | VARCHAR(255) | NOT NULL | -- | Human-readable name for the policy (e.g., "7-Year Financial Records", "Permanent Photographic Archive"). |
| description | TEXT | NULL | NULL | Optional description explaining the rationale, legal basis, or scope of this retention policy. |
| retention_period_days | INT UNSIGNED | -- | `0` | Number of days to retain objects before they become eligible for disposition. A value of `0` means indefinite retention (objects are never eligible for disposition under this policy). |
| trigger_type | VARCHAR(20) | -- | `'ingest_date'` | Determines which date field is used as the starting point for calculating retention expiry. Valid values: `ingest_date` (date the object was ingested into the system), `last_modified` (date the object or its metadata was last modified), `closure_date` (archival closure date of the parent description), `last_access` (date the object was last accessed or downloaded). |
| scope_type | VARCHAR(20) | -- | `'global'` | Breadth of the policy's applicability. Valid values: `global` (applies to all matching objects system-wide), `repository` (applies only within a specific repository), `hierarchy` (applies only within a subtree rooted at a specific information object). |
| repository_id | INT | NULL | NULL | Foreign key reference to the repository this policy applies to. Required when `scope_type` is `repository`; ignored otherwise. |
| information_object_id | INT | NULL | NULL | Foreign key reference to the root information object for hierarchy-scoped policies. Required when `scope_type` is `hierarchy`; ignored otherwise. |
| object_format | VARCHAR(100) | NULL | NULL | Optional MIME type filter to restrict this policy to specific file formats (e.g., `application/pdf`, `image/tiff`). NULL means the policy applies regardless of format. Supports Issue #189 format-specific retention. |
| is_enabled | TINYINT(1) | -- | `0` | Whether this policy is active. Disabled policies (`0`) are not evaluated during disposition scanning. |
| created_at | DATETIME | -- | -- | Timestamp when the policy record was created. |
| updated_at | DATETIME | -- | -- | Timestamp when the policy record was last modified. |

**Indexes:**
- PRIMARY KEY (`id`)

---

## 6. integrity_legal_hold

**Purpose:** Records legal holds placed on information objects that prevent their disposition regardless of retention policy expiry. A legal hold overrides all retention policies: objects under active legal hold cannot be disposed of, transferred, or destroyed. Legal holds are placed and released by authorized users, with full audit tracking of who and when.

| Column | Data Type | Nullable | Default | Description |
|--------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | NOT NULL | Auto-generated | Primary key. Unique identifier for this legal hold record. |
| information_object_id | INT | NOT NULL | -- | Foreign key reference to the `information_object.id` placed under legal hold. Multiple holds can exist on the same object (e.g., from different legal matters). |
| reason | TEXT | NOT NULL | -- | Mandatory explanation for why the legal hold was placed. Should reference the legal matter, case number, regulatory requirement, or other justification. |
| placed_by | VARCHAR(255) | NOT NULL | -- | Username or identifier of the authorized person who placed the hold. Cannot be NULL to ensure accountability. |
| placed_at | DATETIME | -- | -- | Timestamp when the legal hold was placed. Recorded at creation time. |
| released_by | VARCHAR(255) | NULL | NULL | Username or identifier of the person who released (lifted) the hold. NULL while the hold is active. |
| released_at | DATETIME | NULL | NULL | Timestamp when the hold was released. NULL while the hold is active. |
| status | VARCHAR(20) | -- | `'active'` | Current state of the legal hold. Valid values: `active` (hold is in effect, object cannot be disposed), `released` (hold has been lifted, object may proceed through normal retention/disposition). |
| created_at | DATETIME | -- | -- | Timestamp when the legal hold record was created. Typically identical to `placed_at`. |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX on `information_object_id` for hold lookups
- INDEX on `status` for active hold filtering

---

## 7. integrity_disposition_queue

**Purpose:** Tracks information objects and their associated digital objects that have become eligible for disposition under a retention policy. Objects enter the queue when their retention period expires and progress through a review workflow: eligible, pending review, approved, rejected, held (blocked by legal hold), or disposed. This table ensures that no object is disposed of without explicit review and approval.

| Column | Data Type | Nullable | Default | Description |
|--------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | NOT NULL | Auto-generated | Primary key. Unique identifier for this disposition queue entry. |
| policy_id | BIGINT UNSIGNED | NOT NULL | -- | Foreign key to `integrity_retention_policy.id`. Identifies which retention policy triggered this disposition eligibility. Cannot be NULL -- every queued item must be traceable to a policy. |
| information_object_id | INT | NOT NULL | -- | Foreign key reference to the `information_object.id` that is eligible for disposition. |
| digital_object_id | INT | NULL | NULL | Foreign key reference to a specific `digital_object.id` eligible for disposition. NULL if the disposition applies to the information object as a whole (including all its digital objects). |
| status | VARCHAR(20) | -- | `'eligible'` | Current disposition workflow status. Valid values: `eligible` (retention period expired, awaiting review), `pending_review` (review initiated but not yet decided), `approved` (approved for disposition), `rejected` (disposition denied, object will be retained), `held` (blocked by an active legal hold), `disposed` (disposition action completed). |
| eligible_at | DATETIME | -- | -- | Timestamp when the object became eligible for disposition, calculated from the retention policy's trigger date plus retention period. |
| reviewed_by | VARCHAR(255) | NULL | NULL | Username of the person who reviewed the disposition request. NULL until the item is reviewed. |
| reviewed_at | DATETIME | NULL | NULL | Timestamp when the review decision was made. NULL until reviewed. |
| review_notes | TEXT | NULL | NULL | Free-text notes from the reviewer explaining the decision (approval rationale, rejection reason, etc.). |
| created_at | DATETIME | -- | -- | Timestamp when the queue entry was created. |
| updated_at | DATETIME | -- | -- | Timestamp when the queue entry was last modified. |

**Indexes:**
- PRIMARY KEY (`id`)
- FOREIGN KEY (`policy_id`) REFERENCES `integrity_retention_policy`(`id`) ON DELETE CASCADE
- INDEX on `status` for workflow filtering
- INDEX on `information_object_id` for object lookups

---

## 8. integrity_alert_config

**Purpose:** Configures threshold-based alerts that trigger notifications when integrity metrics cross defined boundaries. Alerts can monitor pass rates, failure counts, dead-letter queue depth, verification backlogs, and run failures. Notifications are delivered via email, webhook, or both.

| Column | Data Type | Nullable | Default | Description |
|--------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | NOT NULL | Auto-generated | Primary key. Unique identifier for this alert configuration. |
| alert_type | VARCHAR(30) | NOT NULL | -- | The metric being monitored. Valid values: `pass_rate_below` (percentage of objects passing verification drops below threshold), `failure_count_above` (number of failures in a single run exceeds threshold), `dead_letter_count_above` (total open dead-letter entries exceed threshold), `backlog_above` (number of objects overdue for verification exceeds threshold), `run_failure` (a run ends with `failed` or `timeout` status). |
| threshold_value | DECIMAL(12,2) | NULL | NULL | Numeric threshold for comparison. Interpretation depends on `alert_type`: percentage for `pass_rate_below` (e.g., `95.00` for 95%), count for `failure_count_above`/`dead_letter_count_above`/`backlog_above`. NULL for `run_failure` (triggers on any run failure regardless of count). |
| comparison | VARCHAR(5) | -- | `'gt'` | Comparison operator applied between the observed metric and `threshold_value`. Valid values: `lt` (less than), `lte` (less than or equal), `gt` (greater than), `gte` (greater than or equal), `eq` (equal to). For example, `pass_rate_below` with `threshold_value=95` and `comparison='lt'` triggers when the pass rate is less than 95%. |
| is_enabled | TINYINT(1) | -- | `1` | Whether this alert is active. Disabled alerts (`0`) are not evaluated. Defaults to enabled. |
| email | VARCHAR(255) | NULL | NULL | Email address to send alert notifications to. NULL if email notifications are not desired for this alert. Falls back to system administrator email if neither email nor webhook is configured. |
| webhook_url | VARCHAR(1024) | NULL | NULL | URL to send HTTP POST webhook notifications to when the alert triggers. The payload includes the alert type, observed value, threshold, and timestamp. NULL if webhook notifications are not desired. |
| webhook_secret | VARCHAR(255) | NULL | NULL | Shared secret used to sign webhook payloads (HMAC-SHA256). Allows the receiving system to verify that the webhook originated from the integrity plugin. NULL if no webhook is configured or signing is not required. |
| last_triggered_at | DATETIME | NULL | NULL | Timestamp of the most recent time this alert was triggered. Used to implement cooldown periods and prevent alert flooding. NULL if the alert has never been triggered. |
| created_at | DATETIME | -- | -- | Timestamp when the alert configuration was created. |
| updated_at | DATETIME | -- | -- | Timestamp when the alert configuration was last modified. |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX on `alert_type` for type-based lookups
- INDEX on `is_enabled` for active alert filtering

---

## Foreign Key Relationships

The following diagram summarizes the foreign key relationships between integrity tables:

```
integrity_schedule
    |
    +--< integrity_run (schedule_id)
            |
            +--< integrity_ledger (run_id)
            |
            +--< integrity_dead_letter (last_run_id)

integrity_retention_policy
    |
    +--< integrity_disposition_queue (policy_id)

integrity_legal_hold (standalone, references information_object)
integrity_alert_config (standalone, no FK to other integrity tables)
```

**Cross-references to core AtoM tables (not enforced by FK constraints):**
- `digital_object.id` -- referenced by `integrity_ledger`, `integrity_dead_letter`, `integrity_disposition_queue`
- `information_object.id` -- referenced by `integrity_schedule`, `integrity_ledger`, `integrity_legal_hold`, `integrity_disposition_queue`, `integrity_retention_policy`
- `repository.id` -- referenced by `integrity_schedule`, `integrity_ledger`, `integrity_retention_policy`

---

## Data Type Conventions

| Convention | Description |
|------------|-------------|
| BIGINT UNSIGNED AUTO_INCREMENT | Used for all primary keys in integrity tables to support large-scale deployments. |
| VARCHAR with COMMENT | Used instead of ENUM for all status and type fields, ensuring flexibility and avoiding migration issues. |
| TINYINT(1) | Used for boolean fields. `1` = true, `0` = false. |
| DATETIME | Used for all timestamps. Stored in UTC. |
| TEXT | Used for unbounded text fields (descriptions, error details, notes). |
| DECIMAL(12,2) | Used for threshold values requiring fractional precision (percentages, counts with decimals). |

---

## Version History

| Version | Date | Description |
|---------|------|-------------|
| 1.0.0 | 2026-02-15 | Initial field definitions for 8 core tables. |
| 1.1.0 | 2026-02-20 | Added `previous_hash` to `integrity_ledger` (Issue #188). Added `object_format` to `integrity_retention_policy` (Issue #189). |
| 1.2.0 | 2026-03-01 | Comprehensive field definitions document with full column descriptions, indexes, and relationship diagrams. |
