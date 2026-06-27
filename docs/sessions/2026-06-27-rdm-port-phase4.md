# RDM port — Phase 4 (atom-ahg-plugins#171): ODRL access/embargo + DOI + public landing

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#171 (epic #167)
**Source:** Heratio `packages/ahg-rdm` `DatasetReleaseService` + `landing` (heratio#1341, parity #1347/#1348)
**Builds on:** Phase 3 (#170)

## What this delivers
The release side-effects behind the human-gate disposition: ODRL access/embargo
policies on the dataset's records, an environment-gated DataCite DOI, and a
public citable landing page. The Phase-3 gate's guarded hook now auto-wires this.

## Files
- `lib/Services/DatasetReleaseService.php` (`AhgRdm\Services`):
  - `apply(datasetId, disposition, userId, embargoUntil)` — clears prior rdm policies; for restrict/de-identify → indefinite ODRL `prohibition` on `use`+`reproduce`; embargo → same with `date_to`; release → open (no policy). Mints a DOI for any finalised disposition. Targets the container IO **and every child file IO**.
  - `mintDoi()` — **environment-gated** (parity #1348): real DataCite registration only when `ahg_doi_config.is_active` row has `environment` in production/prod/live; otherwise a reserved test-prefix DOI `10.5072/heratio.dataset.<id>` with **no external call**. Idempotent (returns existing `rdm_dataset.doi`).
- `modules/rdm/actions` — `executeLanding()` (public, **no requireAuth**).
- `config` — public route `/research/datasets/:id/landing`.
- `modules/rdm/templates/landingSuccess.php` — DataCite-style citation, DOI, access badge (Open/Embargoed/Restricted/Not released); binaries stay gated.
- `modules/rdm/templates/showSuccess.php` — disposition note updated (enforcement is live now) + DOI + landing link.

## Port deltas (Laravel → Symfony/AtoM)
- `app(OdrlService::class)` (`AhgResearch\Services`) → AtoM global-namespace `\OdrlService` (ahgResearchPlugin); `getPoliciesForTarget()` → `getPolicies()`; `createPolicy`/`deletePolicy` keys identical (`research_rights_policy`).
- `DoiService::mint($io,null,$dryRun)` → AtoM `\ahgDoiPlugin\Services\DoiService::mintDoi($io,'findable')`; live call gated on `ahg_doi_config.environment` (the AtoM config already has that column) instead of a passed dry-run flag.
- `now()->addYear()`/`DB`/`Log` → `date('…', strtotime('+1 year'))`/Capsule/`error_log`.
- Blade `landing.blade.php` → `landingSuccess.php` (`decorate_with('layout_1col.php')`, `esc_specialchars`). DMP line omitted until Phase 7 (#174) lands `DmpLinkService`.

## Verification
- `php -l` clean on all rdm files (now 11 PHP).
- `\OdrlService` confirmed global-namespace; `ahg_doi_config.environment` column confirmed present; `research_rights_policy` is the policy table.
- Phase-3 `PopiaGateService::applyReleaseEffects()` now resolves `DatasetReleaseService` → disposition auto-applies ODRL + DOI. No new DDL (reuses `research_rights_policy`, `ahg_doi`, `ahg_doi_config`, `rdm_dataset.doi`).

## KNOWN LIMITATION (parity #1347 — raw-binary gate)
Heratio gates the raw digital-object byte stream via `OdrlService::isDigitalObjectPermitted`.
On AtoM, digital objects are served as **static files by nginx** from `/uploads/r/…`,
not a PHP route — so a guessed download URL is **not** intercepted by ODRL. The ODRL
policies here gate the IO show page (the catalogue access path) and the landing page
omits binary links for non-open datasets, but a true raw-byte gate needs an nginx
`auth_request` → PHP check or a base-AtoM change (base is LOCKED). Tracked as a
follow-up; do not claim full byte-level embargo until that lands.

## Next
Phase 5 (#172): compliance scoreboard (per-faculty, filterable) in ahgReports.
