# 2026-06-29 — Ingest: availability-gated options, local-folder upload, hot-folder auto-ingest (v3.79.18)

Scope: archaeology instance (`/usr/share/nginx/archeology`, own checkout of the shared
atom-ahg-plugins repo) + archive canonical repo. Three ingest improvements.

## 1. Processing options gated by service availability
`ahgIngestPlugin/modules/ingest/templates/configureSuccess.php` — each Processing Option
(Virus Scan, OCR, NER, Summarize, Spell Check, Format ID, Face Detection, Translate) now
renders only when its backend is available. Detection mirrors the AHG Settings > Data
Ingest "Service Availability" card exactly:
- CLI tools probed on PATH: `clamdscan`, `tesseract`, `aspell`, `sf` (Siegfried).
- AI tasks (NER/Summarize/Translate/Face) gated on `class_exists('ahgAIPluginConfiguration')`
  — true only when ahgAIPlugin is ENABLED (disabled plugins are not autoloaded).
Added `$anyAvail` fallback note and null-guarded the translate-lang-panel JS so a hidden
Translate option can't break the script. On archaeology (ahgAIPlugin disabled) this shows
Virus Scan/OCR/Spell Check/Format ID and hides the 4 AI options.

## 2. Local-folder upload (browser)
Upload step now accepts a whole local folder via `<input webkitdirectory multiple
name="ingest_folder[]">` (template) + a folder branch in `executeUpload` (action): every
file lands in a per-batch subdir under the session upload dir and is registered as a
`directory`-type source, flowing through the same pipeline as a server directory. NOT
admin-gated (files come from the browser, not an arbitrary server path). JS shows a
file-count/size summary.

## 3. Watched (hot) folder auto-ingest
Full unattended pipeline:
- **Table** `ingest_watch_folder` (watch_path UNIQUE, label, config JSON snapshot,
  user_id, is_enabled, last_scan_at, last_status, files_ingested). In install.sql +
  `database/migration_watch_folder.sql`. Applied on archaeology.
- **Button** "Set as watched folder" on the Upload step (admin-only), uses HTML5
  `formaction` to submit the server-directory field to a new action.
- **Action** `executeSetWatchFolder` + route `/ingest/:id/watch-folder` (admin-gated,
  validates dir): snapshots THIS session's config (sector/standard/repository/parent/
  output/processing) into the registry, upsert by path.
- **Task** `ingestWatchTask` (`ingest:watch`, extends arBaseTask): for each enabled
  folder, moves new top-level files into `.processing_<ts>`, `createSession(config)` →
  `processUpload(directory)` → `parseRows` → `startJob`/`executeJob`, then moves the batch
  to `.processed/<ts>/`. Supports `--id=` and `--dry-run`; per-folder try/catch updates
  last_status. Run as www-data.
- **Cron** registered in the Settings cron dashboard (cronJobsAction) and installed on
  archaeology at `/etc/cron.d/archaeology-ingest-watch` (every 15 min, www-data, logs to
  /var/log/atom/ingest-watch.log).

## Verification (archaeology, live)
- lint clean on all files; `ingest:watch --help` registers; `--dry-run` → "No enabled
  watched folders"; POST /ingest/3/watch-folder → 302 (auth); GET /ingest/3/upload → 302
  (no 500). Cache cleared + php-fpm restarted.

## Notes
- Released from archive canonical as v3.79.18 (9 files). Archive's own DB needs
  `migration_watch_folder.sql` run before the watcher works there (archaeology already has
  the table). archaeology is at v3.79.11 with local mods + hasn't fetched — files were
  mirrored directly rather than via git pull.
- Reuses IngestService::createSession/processUpload/parseRows + IngestCommitService::
  startJob/executeJob — no pipeline duplication.
