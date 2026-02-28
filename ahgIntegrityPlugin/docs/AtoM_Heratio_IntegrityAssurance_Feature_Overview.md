# AtoM Heratio — Integrity Assurance Plugin

## Feature Overview

**Plugin:** ahgIntegrityPlugin v1.0.0
**Category:** Digital Preservation
**Author:** The Archive and Heritage Group (Pty) Ltd
**License:** AGPL-3.0

---

## What It Does

The Integrity Assurance plugin provides enterprise-grade automated integrity verification for digital objects managed by AtoM Heratio. It ensures that archival files remain unchanged over time by comparing stored cryptographic checksums against current file hashes, detecting corruption, unauthorized modifications, or storage failures before they become unrecoverable.

The plugin builds on the existing Digital Preservation plugin's checksum infrastructure, adding sophisticated scheduling, concurrency controls, and failure management capabilities required by large-scale GLAM and DAM institutions.

## Key Features

### Scheduled Verification
- **Flexible scheduling**: Daily, weekly, monthly, or custom cron expressions
- **Scoped verification**: Global (all objects), per-repository, or per-hierarchy node
- **Configurable batch sizes**: Process 200 objects at a time or scan entire collections
- **Default schedules**: Ships with a daily sample check (200 objects) and a weekly full scan

### Concurrency Controls
- **File-based locking** with PID stale recovery to prevent overlapping runs
- **IO throttle**: Configurable microsleep between objects to reduce disk pressure
- **Memory guard**: Automatic exit as "partial" if memory limit exceeded
- **Runtime guard**: Automatic exit as "timeout" if time limit exceeded
- **Overlap prevention**: Configurable maximum concurrent runs per schedule

### Append-Only Verification Ledger
- Complete audit trail of every verification attempt
- Entries are never updated or deleted — full forensic history
- Denormalized repository and information object IDs for efficient scoped queries
- Records file existence, readability, hash values, match status, and timing

### Dead Letter Queue
- Automatic escalation after configurable consecutive failures (default: 3)
- Workflow states: Open, Acknowledged, Investigating, Resolved, Ignored
- Per-object, per-failure-type tracking with unique constraint
- Retry management with configurable maximum retries

### Admin Dashboard
- Real-time statistics: master objects, total verifications, pass rate
- Recent runs and failures at a glance
- Schedule management with enable/disable toggle and on-demand execution
- Dead letter queue management with acknowledgment workflow

### CLI Commands
| Command | Purpose |
|---------|---------|
| `php symfony integrity:verify` | Run fixity verification (single object, batch, or by schedule) |
| `php symfony integrity:schedule` | Manage schedules, run due schedules (for cron) |
| `php symfony integrity:report` | Generate reports in text, JSON, or CSV format |

### Reporting
- Summary statistics with pass rates and trend analysis
- Monthly trend breakdown (12-month view)
- Dead letter queue status reports
- Multi-format output: text (terminal), JSON (integration), CSV (spreadsheets)

## Compliance and Standards

| Standard | How the Plugin Supports It |
|----------|---------------------------|
| **OAIS** (ISO 14721) | Fixity Information element of the Archival Information Package |
| **PREMIS** | Logs fixity check events compatible with PREMIS event vocabulary |
| **NDSA Levels of Preservation** | Level 2+ fixity checking with automated scheduling |
| **NARSSA/NARS** | South African national archives compliance for digital records |
| **ISO 16363** (TDR Audit) | Documented integrity checking for Trusted Digital Repository certification |

## Technical Requirements

| Requirement | Details |
|-------------|---------|
| **AtoM Heratio** | v2.8+ with atom-framework v2.8.0+ |
| **Dependencies** | ahgCorePlugin, ahgPreservationPlugin (for baseline checksums) |
| **PHP** | 8.1 or higher |
| **Database** | MySQL 8.0+ (4 new tables) |
| **Hash Algorithms** | SHA-256 (default), SHA-512 |
| **Storage** | Read access to all digital object storage paths |

## Database Tables

| Table | Purpose |
|-------|---------|
| `integrity_schedule` | Verification schedule definitions with concurrency controls |
| `integrity_run` | Execution records with counters and status |
| `integrity_ledger` | Append-only verification audit trail |
| `integrity_dead_letter` | Persistent failure queue with workflow states |

## Recommended Cron Configuration

```
# Run due integrity schedules every 15 minutes
*/15 * * * * cd /usr/share/nginx/archive && php symfony integrity:schedule --run-due >> /var/log/atom/integrity-scheduler.log 2>&1

# Weekly integrity summary report (Monday 8am)
0 8 * * 1 cd /usr/share/nginx/archive && php symfony integrity:report --summary >> /var/log/atom/integrity-report.log 2>&1
```

## Web Interface

Accessible at **Admin > Integrity** (requires administrator permissions):

| Page | URL | Description |
|------|-----|-------------|
| Dashboard | `/admin/integrity` | Statistics, health overview, recent activity |
| Schedules | `/admin/integrity/schedules` | Manage verification schedules |
| Runs | `/admin/integrity/runs` | Run history with filtering |
| Ledger | `/admin/integrity/ledger` | Browse append-only verification ledger |
| Dead Letter | `/admin/integrity/dead-letter` | Manage persistent failures |
| Report | `/admin/integrity/report` | Visual report with trends |

## Installation

```bash
# 1. Run database migration
mysql -u root archive < atom-ahg-plugins/ahgIntegrityPlugin/database/install.sql

# 2. Enable the plugin
php bin/atom extension:enable ahgIntegrityPlugin

# 3. Clear cache
php symfony cc

# 4. Verify installation
php symfony integrity:verify --status
```

---

*AtoM Heratio is developed by The Archive and Heritage Group (Pty) Ltd for the international GLAM and DAM community.*
