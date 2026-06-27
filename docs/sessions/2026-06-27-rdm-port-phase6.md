# RDM port — Phase 6 (atom-ahg-plugins#173): synthetic demo task

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#173 (epic #167)
**Source:** Heratio `packages/ahg-rdm` `RdmDemoCommand` + `resources/demo/*` (heratio#1343)
**Builds on:** Phase 5 (#172)

## What this delivers
A one-command, end-to-end proof of the whole Feature-2 pipeline on **100%-
synthetic** data: `php symfony rdm:demo --fresh`. deposit → POPIA scan → human
gate (confirm all → release BLOCKED → restrict) → DOI → landing + scoreboard.

## Files
- `lib/task/rdmDemoTask.class.php` — `arBaseTask` (`rdm:demo --fresh`); `sfContext` + `\AhgCore\Core\AhgDb::init()`; orchestrates DatasetService/PopiaScanService/PopiaGateService; prints findings + a result report with live URLs.
- `data/demo/` — copied synthetic assets: `survey_responses.csv` (Luhn-valid fake SA IDs + emails/phones), `interview_transcripts/interview_01.txt` (health/treatment lexicon + names), `consent_forms.pdf` (born-digital), `consent_form_scanned.pdf` (image-only → OCR path), `climate_measurements.csv` + `readme.txt` (CLEAR negative control).

## Port deltas (Laravel → Symfony/AtoM)
- `php artisan ahg:rdm-demo` (Illuminate `Command`) → `php symfony rdm:demo` (`arBaseTask`, mirrors `rdm:scan`).
- `app(...)` / `UploadedFile` → `new \AhgRdm\Services\…` / the deposit `['tmp_path','original_name']` array shape.
- `DB::table('users')` → AtoM `user` table; `research_researcher` (23 present, owns the demo project); `research_project` insert keys match AtoM cols (owner_id/title/institution/status/visibility).
- `DmpLinkService` (Phase 7, #174) → **guarded** require — demo links a DMP once that service lands, skips cleanly until then.
- `config('app.url')` → `sfConfig::get('app_siteBaseUrl', 'https://psis.theahg.co.za')`; `now()`/`$this->table()` → `date()`/`logSection`.

## Acceptance (per the spec)
~17 findings expected: 3 SA IDs (deterministic, Luhn) + emails + phones from the
survey CSV; health/treatment lexicon + NER names from the transcript; consent
PDFs via pdftotext/OCR; the climate CSV passes **CLEAR**. Open release is blocked
once findings are confirmed; restrict applies ODRL + mints a (test-prefix) DOI.

## Verification
- `php -l` clean (now 13 PHP files).
- `user` / `research_researcher` / `research_project` columns confirmed on AtoM.
- NOTE: the task creates **real** container + child information_objects + digital_objects
  in the archive DB (that's how RDM deposit works). `--fresh` purges prior `rdm_*`
  demo rows; the underlying demo IOs/DOs are left (same as Heratio) — a known demo
  artifact, not catalogue-published (draft, restricted).

## Next
Phase 7 (#174): Feature 1 — DMP link (wire the existing ahgResearchPlugin DmpService);
the demo's guarded DMP block then activates automatically.
