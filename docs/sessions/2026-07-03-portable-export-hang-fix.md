# Portable export — hang + archive-mode crash fixes

**Date:** 2026-07-03 · **Repo:** atom-ahg-plugins v3.79.58 · **Plugin:** ahgPortableExportPlugin
**Instances:** archive/PSIS + archaeology (synced)

## Symptom
Export "hangs" at "Extracting catalogue/entity data…"; jobs pages show no pending job.

## Cause 1 — dead queue launch
`launchBackground()`/`launchImportBackground()` dispatched to `QueueService`
(`ahg_queue_job` table), but NO QueueService worker runs on archive — the live
`symfony jobs:worker` drains the legacy `job` (QubitJob) table, a different queue.
`ahg_queue_job` had 215 pending, nothing draining → job stuck `pending` forever.
**Fix:** launch via `nohup php symfony portable:export --export-id=N` (no worker needed).
Portable exports are not AtoM jobs, so they never appear on /jobs — the /portable-export
list is where they show, with progress + Download.

## Cause 2 — archive-mode schema crashes (ArchiveExtractor)
- `rights` has no `object_id`/`act_id`; rights link to IOs via `relation`
  (QubitTerm::RIGHT_ID=168, subject_id=IO, object_id=rights.id). `rights_i18n` real cols
  are identifier_value/type/role (not license_identifier), statute_* live on `rights`.
- `accession` query used `a.source_of_acquisition_id`/`a.location_information` — those are
  on `accession_i18n`.
Fixed both selects to the real schema and wrapped every entity extraction in try/catch
(`$extractionErrors`) so one bad table can't abort the whole export.

## Verify
Fonds archive export completes: 691 descriptions, 318 authorities, 650 digital objects,
224 events, 619 relations, 518 term-relations, 4 accessions, 61 taxonomies; rights=0
(0 rows), physical_objects=0. Stuck jobs #12/#13 run to completion. Synced + restarted.
