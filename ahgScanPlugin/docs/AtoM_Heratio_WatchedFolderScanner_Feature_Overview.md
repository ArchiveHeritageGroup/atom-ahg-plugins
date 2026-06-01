# Watched Folder Scanner (ahgScanPlugin)

**The Archive and Heritage Group (Pty) Ltd**

## Overview

The Watched Folder Scanner turns any directory on your server into a hands-off
ingest dropbox. Drop digital objects into a watched folder and they are
automatically detected, deduplicated, described and ingested into your AtoM
catalogue — no wizard clicks, no manual upload.

It builds directly on the AtoM ingestion pipeline (ahgIngestPlugin), so every
file is processed with the same validation, OAIS packaging, derivative
generation and AI options you already configure for batch imports.

## Key Features

- **Configurable watched folders.** Each folder has a unique code, a watched
  path, a layout, and disposition rules. A folder is bound one-to-one to an
  ingest session that holds its processing configuration (target parent,
  sector, descriptive standard, derivatives, virus scan, OCR, NER, OAIS output).
- **Streaming detection CLI.** `scan:watch` walks the enabled folders, finds
  new files, and feeds them to the ingest pipeline. Run it once per minute from
  cron, or continuously under systemd/supervisord.
- **Checksum deduplication.** Every file is fingerprinted with SHA-256. A file
  already ingested in a folder's session is skipped, so re-scans and partial
  re-drops never create duplicate records.
- **Quiet-period guard.** Files still being written (uploads in progress) are
  left alone until they have been idle for a configurable number of seconds.
- **Processed and failed directories.** Successful files are archived to a
  processed directory (dated sub-folders); failed files are quarantined to a
  failed directory for operator review.
- **Per-pass audit log.** Every scan pass records a `scan_event` row with file
  counts, the ingest job it launched, and any error message.
- **Admin UI.** Manage folders, run an on-demand scan, and review history at
  **Admin > Watched Folders** (`/admin/scan`).

## How it works

1. An administrator registers a watched folder (admin UI or `scan:install --add`).
   A backing ingest session is created automatically with the chosen processing
   configuration.
2. `scan:watch` detects a new, settled, non-duplicate file and stages it as a
   row on the folder's session, recording its SHA-256 checksum.
3. When auto-commit is enabled, the scanner launches the ingest commit pipeline
   (`ingest:commit`), which creates the information object + digital object,
   generates derivatives, runs the configured AI steps, and indexes the record.
4. The source file is moved to the processed directory on success, or
   quarantined on failure.

## Requirements

- AtoM 2.8+ with atom-framework and ahgIngestPlugin enabled.
- PHP 8.1+.
- Optional: ClamAV (`clamscan`) for virus scanning, the AHG AI service for
  OCR/NER. These are honoured when present and skipped gracefully when absent.

## Scheduling

```cron
# Detect and ingest new files every minute
* * * * * cd /usr/share/nginx/archive && php bin/atom scan:watch --once >> log/ahg-scan.log 2>&1
```

Or run continuously:

```bash
php bin/atom scan:watch --interval=30
```
