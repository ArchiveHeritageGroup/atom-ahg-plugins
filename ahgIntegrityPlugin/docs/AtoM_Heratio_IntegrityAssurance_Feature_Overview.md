# AtoM Heratio — Integrity Assurance Plugin

## Feature Overview

**Plugin:** ahgIntegrityPlugin v1.2.0
**Category:** Digital Preservation
**Author:** The Archive and Heritage Group (Pty) Ltd
**License:** AGPL-3.0

---

## What It Does

The Integrity Assurance plugin provides enterprise-grade automated integrity verification for digital objects managed by AtoM Heratio. It ensures that archival files remain unchanged over time by comparing stored cryptographic checksums against current file hashes, detecting corruption, unauthorized modifications, or storage failures before they become unrecoverable.

The plugin builds on the existing Digital Preservation plugin's checksum infrastructure, adding sophisticated scheduling, concurrency controls, failure management, retention policies, legal holds, and threshold-based alerting required by large-scale GLAM and DAM institutions.

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
- **Actor and hostname tracking** for multi-server environments
- **Previous hash chain** (v1.2.0): Each ledger entry records the computed hash of the prior successful verification, enabling tamper detection and chain-of-custody verification
- Denormalized repository and information object IDs for efficient scoped queries
- Records file existence, readability, hash values, match status, and timing

### Dead Letter Queue
- Automatic escalation after configurable consecutive failures (default: 3)
- Workflow states: Open, Acknowledged, Investigating, Resolved, Ignored
- Per-object, per-failure-type tracking with unique constraint
- Retry management with configurable maximum retries

### CSV Export & Auditor Pack (v1.1.0)
- **CSV export**: Download the complete verification ledger with date, repository, and outcome filters
- **Auditor Pack**: ZIP archive containing standalone summary.html (no external dependencies), exceptions.csv, and config-snapshot.json
- **CLI export**: `--export-csv` and `--auditor-pack` flags for automated reporting pipelines

### Retention Policy Engine (v1.1.0, enhanced v1.2.0)
- **Policy definitions**: Name, description, retention period (days), trigger type, scope
- **Trigger types**: Ingest date, last modified, closure date, last access
- **Scope types**: Global, per-repository, or per-hierarchy node
- **Object format filter** (v1.2.0): Restrict policies to specific MIME types (e.g., `image/tiff`, `application/pdf`) using prefix matching
- **Disposition review queue**: Eligible, Pending Review, Approved, Rejected, Held, Disposed
- **Safe disposition**: Marks as "disposed" only — no actual deletion (preserving archival integrity)

### Legal Holds (v1.1.0)
- Place legal holds on information objects to block disposition
- Automatic blocking of disposition queue entries when hold is placed
- Release workflow with re-evaluation of queue entries
- Full audit trail in the verification ledger

### Threshold-Based Alerting (v1.1.0)
- **Alert types**: Pass rate below threshold, failure count above, dead letter count above, backlog above, run failure
- **Email notifications**: Via SwiftMailer (matches existing AtoM pattern)
- **Webhook notifications**: HTTP POST with HMAC-SHA256 signature verification
- **Schedule notifications**: Existing notify_email, notify_on_failure, notify_on_mismatch wired to alert system
- **Non-fatal**: Alert failures never break verification runs

### Enhanced Dashboard (v1.1.0, enhanced v1.2.0)
- **Backlog card**: Count of master digital objects never verified
- **Throughput card**: Objects/hour and GB/hour over the last 7 days
- **Storage growth KPI** (v1.2.0): Total storage scanned and average GB/day over 30 days
- **Daily Trend chart**: Interactive Chart.js stacked bar chart (v1.2.0, upgraded from CSS bars), green=pass, red=fail
- **Repository Breakdown table**: Per-repository totals, pass/fail counts, pass rate — clickable for drill-down filtering (v1.2.0)
- **Failure Type Breakdown table**: Outcome distribution with percentages
- **Format Breakdown table** (v1.2.0): Verification results by file format (PRONOM)
- **Repository filter** (v1.2.0): Dropdown filter to scope all dashboard statistics to a specific repository

### Admin Dashboard
- Real-time statistics: master objects, total verifications, pass rate
- Recent runs and failures at a glance
- Schedule management with enable/disable toggle and on-demand execution
- Dead letter queue management with acknowledgment workflow
- Navigation to Export, Policies, Holds, Alerts pages

### CLI Commands
| Command | Purpose |
|---------|---------|
| `php symfony integrity:verify` | Run fixity verification (single object, batch, or by schedule) |
| `php symfony integrity:schedule` | Manage schedules, run due schedules (for cron) |
| `php symfony integrity:report` | Generate reports, CSV exports, auditor packs |
| `php symfony integrity:retention` | Manage retention policies, legal holds, disposition queue |

### REST API (v1.2.0)
- **25+ JSON API endpoints** for integration with external monitoring and automation systems
- **Paginated list endpoints**: Ledger, runs, holds, policies with configurable `limit` and `skip` parameters
- **Analytics endpoints**: Daily trend, repository breakdown, format breakdown, throughput, storage growth
- **Action endpoints**: Verify objects, manage schedules, retention policies, legal holds, alerts
- **OpenAPI specification**: Full OpenAPI 3.0.3 documentation included (see `docs/openapi.yaml`)
- **Admin authentication required** on all endpoints

### Reporting
- Summary statistics with pass rates and trend analysis
- Monthly trend breakdown (12-month view)
- Dead letter queue status reports
- Multi-format output: text (terminal), JSON (integration), CSV (spreadsheets)
- Filtered CSV export with 50,000 row limit
- Auditor Pack ZIP for compliance audits

## Compliance and Standards

| Standard | How the Plugin Supports It |
|----------|---------------------------|
| **OAIS** (ISO 14721) | Fixity Information element of the Archival Information Package |
| **PREMIS** | Logs fixity check events compatible with PREMIS event vocabulary |
| **NDSA Levels of Preservation** | Level 2+ fixity checking with automated scheduling |
| **NARSSA/NARS** | South African national archives compliance for digital records |
| **ISO 16363** (TDR Audit) | Documented integrity checking for Trusted Digital Repository certification |
| **ISO 15489** (Records Management) | Supports authenticity, reliability, integrity, and usability requirements |
| **Records Retention** | Configurable retention policies with disposition review queue |
| **Legal Hold** | Litigation hold capability to prevent disposition of records under review |

## Technical Requirements

| Requirement | Details |
|-------------|---------|
| **AtoM Heratio** | v2.8+ with atom-framework v2.8.0+ |
| **Dependencies** | ahgCorePlugin, ahgPreservationPlugin (for baseline checksums) |
| **PHP** | 8.1 or higher |
| **Database** | MySQL 8.0+ (8 tables) |
| **Hash Algorithms** | SHA-256 (default), SHA-512 |
| **Storage** | Read access to all digital object storage paths |

## Database Tables

| Table | Purpose |
|-------|---------|
| `integrity_schedule` | Verification schedule definitions with concurrency controls |
| `integrity_run` | Execution records with counters and status |
| `integrity_ledger` | Append-only verification audit trail with actor/hostname tracking |
| `integrity_dead_letter` | Persistent failure queue with workflow states |
| `integrity_retention_policy` | Retention period definitions and scope rules |
| `integrity_legal_hold` | Legal holds blocking disposition |
| `integrity_disposition_queue` | Disposition review queue |
| `integrity_alert_config` | Threshold-based alert configuration |

## Recommended Cron Configuration

```
# Run due integrity schedules every 15 minutes
*/15 * * * * cd /usr/share/nginx/archive && php symfony integrity:schedule --run-due >> /var/log/atom/integrity-scheduler.log 2>&1

# Scan for retention-eligible objects daily at 1am
0 1 * * * cd /usr/share/nginx/archive && php symfony integrity:retention --scan-eligible >> /var/log/atom/integrity-retention.log 2>&1

# Weekly integrity summary report (Monday 8am)
0 8 * * 1 cd /usr/share/nginx/archive && php symfony integrity:report --summary >> /var/log/atom/integrity-report.log 2>&1
```

## Web Interface

Accessible at **Admin > Integrity** (requires administrator permissions):

| Page | URL | Description |
|------|-----|-------------|
| Dashboard | `/admin/integrity` | Statistics, health overview, trend chart, breakdowns |
| Schedules | `/admin/integrity/schedules` | Manage verification schedules |
| Runs | `/admin/integrity/runs` | Run history with filtering |
| Ledger | `/admin/integrity/ledger` | Browse append-only verification ledger |
| Dead Letter | `/admin/integrity/dead-letter` | Manage persistent failures |
| Report | `/admin/integrity/report` | Visual report with trends |
| Export | `/admin/integrity/export` | CSV export and Auditor Pack download |
| Policies | `/admin/integrity/policies` | Retention policy management |
| Holds | `/admin/integrity/holds` | Legal hold management |
| Disposition | `/admin/integrity/disposition` | Disposition review queue |
| Alerts | `/admin/integrity/alerts` | Threshold-based alert configuration |

## Installation

```bash
# 1. Run database migration
mysql -u root archive < atom-ahg-plugins/ahgIntegrityPlugin/database/install.sql

# 2. Enable the plugin
php bin/atom extension:enable ahgIntegrityPlugin

# 3. Clear cache
php symfony cc

# 4. Run schema migration (adds actor/hostname columns if upgrading from v1.0.0)
php symfony integrity:verify --status

# 5. Verify installation
php symfony integrity:retention --status
```

---

*AtoM Heratio is developed by The Archive and Heritage Group (Pty) Ltd for the international GLAM and DAM community.*
