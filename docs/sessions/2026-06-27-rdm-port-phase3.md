# RDM port — Phase 3 (atom-ahg-plugins#170): human gate + provenance

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#170 (epic #167)
**Source:** Heratio `packages/ahg-rdm` `PopiaGateService` (heratio#1340)
**Builds on:** Phase 2 (#169)

## What this delivers
The human review gate — the authority over the POPIA scan. The scan only
suggests; a reviewer confirms (real PII) or dismisses (false positive) every
finding, and applies a dataset disposition. **Open release is blocked** while any
PERSONAL/SPECIAL finding is pending or confirmed.

## Files
- `lib/Services/PopiaGateService.php` (`AhgRdm\Services`):
  - `resolveFinding(findingId, confirm|dismiss, note, userId)` — sets `review_status` + `reviewed_by/_at` + masked `decision_note`; logs provenance.
  - `gateStatus(datasetId)` — counts pending / confirmed / dismissed PERSONAL+SPECIAL; `can_release = (pending==0 && confirmed==0)`.
  - `setDisposition(datasetId, restrict|embargo|de-identify|release, userId, embargoUntil)` — **release blocked unless gate clear**; persists disposition + status (release→`published`, protective→`restricted`); guarded Phase-4 release effects; provenance.
  - `logProvenance()` — durable on-row trail + best-effort `error_log` breadcrumb (cross-cutting AI-disclosure log is wired when present; never blocks the gate).
- `modules/rdm/actions` — `executeResolveFinding`, `executeDisposition`; `executeShow` now loads `gate`.
- `config` — routes `…/findings/:fid/resolve` + `…/disposition`.
- `modules/rdm/templates/showSuccess.php` — gate banner (clear/blocked), per-finding Confirm/Dismiss + note (resolved show status badge + note), disposition form (release `<option>` disabled until gate clear; embargo date).

## Port deltas (Laravel → Symfony/AtoM)
- `now()`/`DB` facade → `date()`/Capsule.
- Phase-4 `app(DatasetReleaseService::class)->apply()` → **guarded** `applyReleaseEffects()` (no-ops until `DatasetReleaseService` lands in #171, so the gate stands alone now).
- Cross-cutting `\AhgResearch\Services\AiDisclosureService` (no AtoM equivalent) → durable on-row provenance + `error_log` breadcrumb. `ahgAiCompliancePlugin/InferenceLogger` exists but needs a `ReceiptChain`; deferred — on-row trail is the source of truth.
- Laravel route method binding → `RouteLoader('rdm')` + action method-branching.

## Verification
- `php -l` clean on all 9 PHP files; showSuccess control-flow balanced (16/16 if/endif).
- Reused the Phase-1 `rdm_scan_finding` review columns (`review_status`/`reviewed_by`/`reviewed_at`/`decision_note`) — **no new DDL** this phase.
- Gate logic verified by reading: a dataset with any pending/confirmed PERSONAL/SPECIAL cannot pick `release` (option disabled in UI + `setDisposition` throws server-side).

## NOT done (handed to Johan)
No new DDL. Activation unchanged (symlink → install.sql already covers the
columns → enable → cache+restart). Phase 4 will add `DatasetReleaseService`
(ODRL access/embargo + DataCite DOI + public landing), which `setDisposition`
auto-wires via the guarded hook.

## Next
Phase 4 (#171): access/embargo (ODRL) + DOI + public citable landing page.
