# CallHub error alerts - weekly-deduped ticket drain (PSIS, 2026-09-11)

atom-ahg-plugins v3.106.93. Errors in `ahg_error_log` now raise a CallHub ticket,
filed under project **AHG Internal**, without any risk of the same fault ticketing twice.

## Shape

`php symfony callhub:alerts` (`ahgCorePlugin/lib/task/callhubAlertsTask.class.php`) is
a CLI drain, not an inline call on the error path. A page render is never coupled to
CallHub's availability. Intended cron: hourly.

It selects `ahg_error_log` rows where `resolved_at IS NULL`, `signature IS NOT NULL`
and `level IN ('error')`. Warnings and notices are never ticketed - they stay in the
log. On PSIS at release time that meant 5 open rows, all warnings, so the first run
raises zero tickets.

Off by default behind `callhub_alerts_enabled`. `--dry-run` claims nothing and writes
nothing.

## How duplicates are prevented

Three layers, and the first is the one that matters:

1. `ahg_error_alert` has `UNIQUE KEY (signature, period)`. The drain **claims** a pair
   there BEFORE writing the spool file. A concurrent drain, or a re-run after a crash,
   loses the insert and sends nothing. If the spool write then fails the claim is
   released, so the next run retries instead of losing the fault.
2. `externalRef` = `psis:<sha256-12 of signature>:<ISO year-week>`. CallHub dedupes
   server-side on this, so a duplicated delivery is still one ticket.
3. `period` is in the key deliberately. A timeless reference would swallow a fault
   that recurs months later; the week gives one ticket per fault per week.

Delivery is via the workbench notification spool at `/var/spool/workbench/notifications`
(tempnam -> chmod 0644 -> rename, so the watcher never reads a half-written file).

## One design correction worth recording

`claim()` returns false both for a duplicate key and for a missing table. Left alone,
a never-created table would make every row report as "already sent this period" - the
same can't-check / found-nothing confusion that was removed from the redaction filter
earlier in this work. The task now checks `hasTable('ahg_error_alert')` once up front
and reports a missing schema as exactly that, exiting 1.

## Central sync was deliberately NOT changed

An earlier claim in this session that `central:sync-errors` duplicates was wrong. It
posts `['errors' => ..., 'replace' => true]` - an idempotent full-state replace. Adding
sent-tracking to it would have made AHG Central drop every error it currently holds.
CallHub only, as instructed.

## Environment note (pre-existing, not this task)

`php symfony <anything>` fails as a non-www-data user on PSIS: `log/` is
`drwxr-xr-x www-data:www-data` and `sfFileLogger` throws on `!is_writable($dir)`.
`central:sync-errors` fails identically. Cron runs as www-data and is unaffected.

## Checks

`ahgCorePlugin/tests/callhub_alert_test.php`, 31 assertions, in the CI glob. All five
suites pass: callhub_alert 31, error_trap_level 21, pii_scoring 68, redaction_filter 12,
visual_redaction_coords 9.
