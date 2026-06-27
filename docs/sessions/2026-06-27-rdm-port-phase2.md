# RDM port — Phase 2 (atom-ahg-plugins#169): POPIA scan service + async task

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#169 (epic #167)
**Source:** Heratio `packages/ahg-rdm` `PopiaScanService` + `ScanDatasetJob` (heratio#1339)
**Builds on:** Phase 1 (#168)

## What this delivers
The POPIA sensitivity scan — deterministic-first, AI-augmented, human-final.
Runs over a dataset's deposited files, persists masked findings, sets a verdict,
and moves the dataset to the human-review gate (next phase) or back to draft.

## Files
- `lib/Services/PopiaScanService.php` (`AhgRdm\Services`) — `scanDataset()`:
  - **Deterministic** (no LLM): SA ID (13-digit + embedded-date + Luhn), email, SA phone (+27/0), passport (letter+8). Masked samples.
  - **Special-category lexicon**: health/religion/biometric/orientation terms → `special_category`.
  - **NER augmentation** (best-effort, AI-suggested): `ahgNerService->extract()` via the AI gateway; maps spaCy `PERSON/GPE/ORG` → person/location/org. Swallows quota/down errors so deterministic findings stand alone.
  - **Text extraction**: pdftotext (born-digital) → scanned-PDF OCR fallback (pdftoppm + tesseract, 10-page/200-DPI cap) → image tesseract → text read. OCR-derived findings demoted one confidence notch.
  - **Verdict**: SPECIAL_CATEGORY > PERSONAL > CLEAR. CLEAR → status `draft`; any PII → `review`. Idempotent (clears prior findings on re-scan).
- `lib/task/rdmScanTask.class.php` — `php symfony rdm:scan --dataset-id=N` (mirrors `ingest:commit`: `sfContext` + `\AhgCore\Core\AhgDb::init()`); resets status off `scanning` on failure.
- `modules/rdm/actions/actions.class.php` — `executeScan()` sets `scanning` + launches the task via `nohup` (NER exceeds php-fpm limits); `executeShow()` now loads findings.
- `modules/rdm/templates/showSuccess.php` — verdict badge, Run/Re-run scan button, masked findings table (special-category first).

## Port deltas (Laravel → Symfony/AtoM)
- `NerService` (persons/places/organizations) → `ahgNerService::extract()` returning spaCy-labelled `entities` (PERSON/ORG/GPE) — remapped.
- Laravel queue `ScanDatasetJob::dispatch()` → symfony `arBaseTask` launched via `nohup` (same pattern ahgIngestPlugin uses).
- `DigitalObjectService::resolveDiskPath` / `EncryptionService` / `PdfTextExtractService` / `OcrService` → AtoM master-DO path (`sf_web_dir` + `digital_object.path` + `name`) + shell `pdftotext`/`pdftoppm`/`tesseract` (PSIS stores plain files; no encrypt-at-rest layer).
- `now()`/`DB` facade/`Log` → `date()`/Capsule/swallowed.

## Verification
- `php -l` clean on all 8 PHP files.
- Dependencies confirmed present: `ahgAIPlugin/lib/Services/NerService.php` (`ahgNerService`), `\AhgCore\Core\AhgDb::init()`.
- SA-ID detector is Luhn+date gated (the demo's headline finding); samples masked before persistence.

## NOT done (handed to Johan)
Activation unchanged from Phase 1 (symlink → install.sql → enable → cache+restart).
Scan needs `pdftotext`/`tesseract`/`pdftoppm` on the host (already used by ahgIngest)
and a working AI-gateway key in `ahg_ai_settings` for the NER augmentation (the
deterministic + lexicon detectors run without it).

## Next
Phase 3 (#170): human gate — confirm/dismiss each finding with provenance; open
release blocked while any PERSONAL/SPECIAL finding is pending or confirmed.
