# RDM port — Phase 1 (atom-ahg-plugins#168): scaffold ahgRdmPlugin + Dataset + deposit

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#168 (epic #167)
**Source:** Heratio `packages/ahg-rdm` (heratio#1337/#1338) — reverse port Laravel → Symfony 1.4
**Spec:** `docs/parity-issues/rdm-port.md`

## What this delivers
The first phase of the sovereign RDM reverse-port: a net-new, unlocked
`ahgRdmPlugin` that wraps a **Dataset** over a container `information_object` and
deposits files as child IOs + master `digital_object`s. No POPIA scan / gate /
DOI yet (later phases). Thin orchestration — no base-AtoM edits, no MySQL ENUM,
sidecar tables only.

## Files (all net-new under `ahgRdmPlugin/`)
- `extension.json` — v0.1.0, deps: ahgCore, ahgIngest, ahgInformationObjectManage, ahgResearch.
- `config/ahgRdmPluginConfiguration.class.php` — framework bootstrap, `AhgRdm\` PSR-4-style autoloader (mirrors ahgInformationObjectManagePlugin), `rdm` module, routes under `/research/datasets` via `RouteLoader('rdm')`.
- `database/install.sql` — idempotent (`CREATE TABLE IF NOT EXISTS` + guarded `ALTER`) for `rdm_dataset`, `rdm_dataset_file`, `rdm_scan_finding` (ported verbatim incl. later-phase columns: verdict/scanned_at/disposition*/dmp_id).
- `database/seed_dropdowns.sql` — `ahg_dropdown` seeds for `dataset_status` + `rdm_disposition` (INSERT IGNORE).
- `lib/Services/DatasetService.php` (`AhgRdm\Services`) — `create()` (container IO via `\AhgInformationObjectManage\Services\InformationObjectCrudService::create`), `deposit()` (child IO per file + `\QubitDigitalObject`/`\QubitAsset` master DO, manual hash-bucket fallback), `get()/files()/list()`.
- `modules/rdm/` — actions (index/create/show/deposit) + Bootstrap-5 templates.

## Port deltas (Laravel → Symfony/AtoM)
- `InformationObjectService::create()` → `InformationObjectCrudService::create()` (camelCase keys: title/scopeAndContent/parentId/sourceStandard='rdm').
- `IngestService::ingestFile()` (no AtoM equivalent) → native child-IO create + `QubitDigitalObject` + `QubitAsset($name,$contents)` (the proven `ahgIngestPlugin/IngestCommitService` path), with manual `uploads/r/<checksum>` fallback.
- `DB` facade / `now()` / `UploadedFile` → Illuminate Capsule / `date()` / `$_FILES` normaliser.
- Routes: Laravel method-specific → `RouteLoader('rdm')` `any()` + action method-branching; `:id` `\d+`-constrained so `/create` never collides with `/:id`.

## Verification
- `php -l` clean on all 6 PHP files.
- `layout_1col.php` confirmed valid theme idiom; `research_project` table present (left-join target).

## NOT done in this session (handed to Johan — DB/system/release gated)
1. Symlink `plugins/ahgRdmPlugin` → `atom-ahg-plugins/ahgRdmPlugin`.
2. Run `database/install.sql` + `database/seed_dropdowns.sql` on the `archive` DB.
3. `INSERT INTO atom_plugin` to enable (or `extension:enable ahgRdmPlugin`).
4. Clear cache + **restart** php8.3-fpm (opcache validate_timestamps=0).
5. Release via `./bin/release patch`.

## Next
Phase 2 (#169): `PopiaScanService` — deterministic detectors (SA-ID Luhn, email,
phone, passport — masked) + special-category lexicon + gateway NER, async task.
