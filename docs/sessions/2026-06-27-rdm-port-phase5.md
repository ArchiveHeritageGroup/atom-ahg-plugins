# RDM port — Phase 5 (atom-ahg-plugins#172): compliance scoreboard

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#172 (epic #167)
**Source:** Heratio `packages/ahg-rdm` `ComplianceReportService` + compliance view (heratio#1342)
**Builds on:** Phase 4 (#171)

## What this delivers
The RDM librarian's defensibility view — a per-dataset compliance scoreboard:
POPIA verdict, finding counts (pending/confirmed), access disposition, DOI, and
project/faculty + DMP linkage. Filterable by institution / verdict / disposition.
Read-only aggregation, **no new DDL**.

## Files
- `lib/Services/ComplianceReportService.php` (`AhgRdm\Services`): `rows(filters)` (grouped join rdm_dataset × research_project × rdm_scan_finding), `institutions()`, `summary()` (total / flagged / restricted / open / unreviewed / dmp_linked). DMP columns join in only when `rdm_dataset.dmp_id` + `research_dmp` both exist (graceful degrade pre-Phase-7).
- `modules/rdm/actions` — `executeCompliance()` (auth; filters → rows/institutions/summary).
- `config` — route `/research/datasets/compliance` (registered before `/:id`; non-numeric so no collision).
- `modules/rdm/templates/complianceSuccess.php` — summary strip + filter form + scoreboard table (verdict/access colour badges, DOI link, DMP/Project column).
- `modules/rdm/templates/indexSuccess.php` — "Compliance scoreboard" button.

## Port deltas (Laravel → Symfony/AtoM)
- `Schema::hasColumn`/`hasTable` → `information_schema` lookups (`columnExists`/`tableExists`) — robust on Capsule without the Schema facade.
- Collection helpers (`whereIn`/`where('pending','>',0)`/`whereNotNull`) work unchanged on Capsule's returned Collection.
- Blade `compliance.blade.php` → `complianceSuccess.php` (`decorate_with('layout_1col.php')`, `esc_specialchars`, `mb_strimwidth` for `Str::limit`). Dashboard cross-link deferred to Phase 8 (#175).
- `research_project.institution` confirmed present on AtoM.

## Verification
- `php -l` clean on all rdm files (12 PHP).
- No new tables/columns — pure read aggregation over existing rdm_* + research_project (+ optional research_dmp).

## Next
Phase 6 (#173): synthetic demo task (`php symfony rdm:demo --fresh`) on 100%-synthetic
assets — exercises the full deposit → scan → gate → release → scoreboard chain.
