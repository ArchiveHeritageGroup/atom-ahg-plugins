# Full embedded metadata capture & display (#113)

**Date:** 2026-05-31
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#113 (PSIS-parity twin of heratio#1106, CLOSED)
**Plugins:** ahgMetadataExtractionPlugin (core), ahgAPIPlugin, ahgIiifPlugin
**Status:** Built, lint-clean, DEPLOYED to PSIS — table created, 302/304 image masters backfilled, web reloaded. Pending: docs `.docx` + in-app `/help`; release.

## Critical correction (filesystem-only plugin)
ahgMetadataExtractionPlugin is **filesystem-only** (not enabled in atom_plugin): its classes
are used directly by the framework, but its Symfony **module/routes/tasks do NOT load**. So:
- Display moved from `/metadataExtraction/view` (404) → **ahgDAMPlugin** `executeEditIptc` /
  `editIptcSuccess.php` (the served DAM asset page). DAM viewer panel.
- Backfill moved from a `php symfony` task → **`php bin/atom metadata:backfill-embedded`**
  (`lib/Commands/MetadataBackfillEmbeddedCommand.php`; CommandRegistry discovers lib/Commands
  by filesystem glob, independent of enablement). Self-heals the table (CREATE TABLE IF NOT EXISTS).
- `EmbeddedMetadataService` constructor made CLI-safe (no hard sfConfig dependency).
- **ExifTool rc fix:** exiftool returns rc=1 on minor format errors while still emitting tags;
  `extractFull` now parses output regardless of exit code (was discarding usable data).

## Problem
Photos carry far more embedded metadata than was surfaced. Extraction already ran
`exiftool -json -a -G1` (full), but base AtoM's `arEmbeddedMetadataParser` only maps a
curated subset; the complete tag set was never stored or displayed.

## Design (mirrors heratio#1106; AtoM property-model adapted)
Store the COMPLETE `exiftool -json -a -G1 -struct -u` grouped JSON verbatim, then display
it grouped + searchable (GPS-gated for public) and flow it through API + IIIF.

- **Storage:** plugin-owned table `ahg_embedded_metadata` (one row per master DO,
  `raw_metadata` LONGTEXT, `has_gps`, `tag_count`). Chosen over the core `property` table
  (capacity-uncertain + core-schema off-limits) — no core DDL, ample capacity.
- **Service:** `EmbeddedMetadataService` (extractFull / store / captureAndStore / getRaw /
  group / gpsGate / hasGps). Best-effort: failures never block upload; missing table no-ops.

## Changes
- `EmbeddedMetadataService.php` (new) + `database/install.sql` (new table) + extension.json.
- `MetadataExtractionHandler::extractAndApply()` — calls `captureAndStore()` after the
  curated apply (additive, best-effort).
- `metadata:backfill-embedded` CLI (new) — re-extract masters; `--force/--limit/--id/--dry-run`.
- `metadataExtraction/view` action+template — full grouped panel, live JS filter (CSP-nonce),
  GPS gated for non-admins, "contains GPS" notice.
- **API** `digitalObjectsReadAction` — `embedded_metadata.full` = stored set, grouped, GPS-gated.
- **IIIF** `IiifManifestV3Service` — appends stored full set as per-group manifest metadata
  entries, GPS-gated, independent of the live curated extraction.

## Preservation note
ahgPreservationPlugin has no embedded-metadata block to extend on the AtoM side (unlike
Heratio's OCFL extension). The full set lives in `ahg_embedded_metadata`, captured by
ahgBackupPlugin DB backups — so it is retained preservation-side without a separate PREMIS block.

## Verification
- `php -l` clean on all 7 files; install.sql no-ENUM + IF NOT EXISTS; exiftool `-G1` key shape
  ("Group:Tag" flat) verified against a real PSIS image.
- **Pending (live actions, need approval):** `CREATE TABLE ahg_embedded_metadata`; cache clear +
  php-fpm restart; `metadata:backfill-embedded --dry-run` then a real run; authenticated render
  of the view panel.

## Docs + /help (done)
- `atom-extensions-catalog/docs/full-embedded-metadata-user-guide.md` + `.docx` — end-user guide.
- `atom-extensions-catalog/docs/AtoM_Heratio_FullEmbeddedMetadata_Feature_Overview.md` + `.docx`.
- In-app /help: imported via `php symfony help:import --file=full-embedded-metadata-user-guide.md`
  (help_article, category "User Guide", 421 words, 5 sections).

## Remaining
- Browser eyeball of the DAM "Full embedded metadata" panel (authenticated).
- Release: atom-ahg-plugins (code) + atom-extensions-catalog (docs, root-owned → chown first).
