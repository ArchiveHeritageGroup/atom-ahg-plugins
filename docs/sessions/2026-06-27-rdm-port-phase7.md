# RDM port — Phase 7 (atom-ahg-plugins#174): Feature 1 — DMP link

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#174 (epic #167)
**Source:** Heratio `packages/ahg-rdm` `DmpLinkService` + link/unlink (heratio#1337 Feature 1)
**Builds on:** Phase 6 (#173)

## What this delivers
Link an RDM dataset to a Data Management Plan — **pure orchestration** over the
EXISTING ahgResearchPlugin DMP builder (`DmpService` + `research_dmp`). The plugin
writes only `rdm_dataset.dmp_id`; it never owns DMP data or duplicates the
authoring tool. DMP is project-scoped + advisory (NOT a hard release gate).

## Files
- `lib/Services/DmpLinkService.php` (`AhgRdm\Services`): `context()` (read-model for views), `link()` (validates the plan belongs to the dataset's project), `createAndLink()` (seeds a plan via `DmpService::create` then links), `unlink()`. Writes only `dmp_id`.
- `modules/rdm/actions` — `executeLinkDmp` (link existing / create-and-link), `executeUnlinkDmp`; show + landing now load `dmp` context.
- `config` — routes `…/:id/dmp` (link) + `…/:id/dmp/unlink`.
- `modules/rdm/templates/showSuccess.php` — DMP card: link existing project plan, create-and-link inline, or show linked plan + completeness bar + "Open DMP"/Unlink.
- `modules/rdm/templates/landingSuccess.php` — "Governed by a Data Management Plan [status]" line.
- Compliance scoreboard (Phase 5) already joins the DMP column once `research_dmp` exists — now lit up.

## Port deltas (Laravel → Symfony/AtoM) — the API differs materially
- `AhgResearch\Services\DmpService` (project-scoped: `createPlan(projectId,ownerId,meta)`, `listPlans`, `getPlan`, `getSections`, `completeness(sections)`) → AtoM **global** `\DmpService` (researcher-scoped): `create(researcherId, data)` with `project_id` flowing through `mapFields`; `listForProject`; `get`; `completeness(object)` returns an **int %** (sections are columns on `research_dmp`, no `getSections`).
- `createAndLink` resolves `research_project.owner_id` (a `research_researcher` id) and calls `create($ownerId, $meta + project_id)`.
- `Route::has`/`route()` → plain portal paths (`/research/dmps`, `/research/dmp/view?id=`).
- `Schema::hasTable` → `information_schema`; `now()`/`DB` facade → `date()`/Capsule.

## Activation + verification (LIVE on PSIS)
- No new DDL (`rdm_dataset.dmp_id` shipped in the Phase-1 install.sql; `research_dmp` already present).
- Cache cleared + php-fpm restarted to register the new routes/actions.
- `php -l` clean (14 PHP files).
- `php symfony rdm:demo --fresh` re-run: the previously-guarded DMP block now fires —
  the demo create-links a maDMP to the demo project and the show/landing/scoreboard
  surface it. (See run output in the session.)

## Next
Phase 8 (#175): roll-up RDM dashboard (Chart.js KPIs + date/faculty filters) — the last child of epic #167.
