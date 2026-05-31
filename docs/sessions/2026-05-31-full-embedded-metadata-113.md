# Full embedded metadata capture & display (#113)

**Date:** 2026-05-31
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#113 (PSIS-parity twin of heratio#1106, CLOSED)
**Plugins:** ahgMetadataExtractionPlugin (core), ahgAPIPlugin, ahgIiifPlugin
**Status:** Built, lint-clean. Needs: `CREATE TABLE ahg_embedded_metadata` on PSIS + cache/restart + backfill run (live-DB; awaiting approval).

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

## Remaining
- Full feature-overview doc (.md + .docx) + in-app /help still to write (acceptance: docs/help).
