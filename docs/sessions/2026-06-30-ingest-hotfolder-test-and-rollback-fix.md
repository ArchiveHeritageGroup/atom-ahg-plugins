# 2026-06-30 — Hot-folder e2e test + fix silent rollback gap (v3.79.23)

Scope: archaeology (test target) + archive canonical.

## Hot-folder end-to-end test (archaeology) — PASSED
Registered a temp watched folder with 2 files, ran `ingest:watch`:
- dry-run detected 2 files; real run created session + job, IO count 10→12 (one IO per
  file, title from filename, + digital objects), files moved to `.processed/<ts>/`;
- re-run reported "no new files" (idempotent);
- teardown returned IO count to 10.

## Bug found + fixed: rollback silently deleted 0 records
Root cause (NOT a tracking gap — `parseRowsFromDirectory` does populate `ingest_row` and
`processRow` sets `created_atom_id`): `IngestCommitService::rollback()` calls
`$io->delete()`, which re-indexes on delete. When the search backend isn't fully
initialised (CLI / unattended `ingest:watch` context — the OpenSearch model's
`allowedLanguages` is null) that re-index throws. The exception was swallowed by the
loop's outer try/catch, so rollback deleted 0, left the records orphaned, yet still marked
the job 'cancelled'. (Worked in web context where search is initialised; failed for the
CLI/unattended path the hot-folder needs.)

Fix (`IngestCommitService.php`):
- New `deleteWithoutIndexHook($object)` helper: best-effort remove from the index while
  search is enabled, then `QubitSearch::disable()` around the model `delete()` so it can't
  throw, then `QubitSearch::enable()` in a finally. Used for both DO and IO deletes.
- Rollback now collects per-row errors into `$errors` and records them in the audit log
  instead of silently swallowing them.

New `ingest:rollback --job-id=N` CLI task (`lib/task/ingestRollbackTask.class.php`): undo an
unattended hot-folder batch from the command line; advises `search:populate` afterwards to
clear stale index entries.

## Verification (archaeology)
Re-ran ingest (IO 10→12, session 5 / job 2) → `ingest:rollback --job-id=2` →
"deleted 2 record(s)" (was 0 before the fix) → IO count back to 10, 0 leftover rows.
lint clean; mirrored archive→archaeology; cache cleared. Released v3.79.23 (pushed +
tagged). All test artifacts cleaned; archaeology pristine.

## Note
The hot-folder watcher (`ingest:watch`) + cron remain live on archaeology
(`/etc/cron.d/archaeology-ingest-watch`, every 15 min as www-data). archive/PSIS has the
code but not the `ingest_watch_folder` table (run migration_watch_folder.sql there if the
watcher is wanted on PSIS).
