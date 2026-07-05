# Issue #220 — IIIF AI Extract — Phase 3 (BUILT + VERIFIED)

**Date:** 2026-07-05
**Issue:** [ArchiveHeritageGroup/atom-extensions-catalog#220](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/220)
**Plugin:** `ahgIiifPlugin` · **Builds on:** Phase 1 (v3.79.65) + Phase 2 (v3.79.66)
**Status:** built, lint-clean, verified on PSIS + mirrored to archaeology. UNRELEASED. **No DDL.**

## What shipped (Phase 3)

Two pieces: (a) an MCP tool descriptor (tiny-iiif parity), and (b) `tags`/`entities` write-back into **subject access points**.

| File | Change |
|---|---|
| `lib/Services/IiifAiExtractService.php` | `mcpToolManifest()`; `approve()` now supports `subject_access_points` target; `extractTermNames()`, `findOrCreateSubjectTerm()`, `linkTermToObject()` |
| `modules/iiif/actions/actions.class.php` | +`executeAiMcp` |
| `config/ahgIiifPluginConfiguration.class.php` | +route `GET /iiif/ai/mcp` |
| `modules/iiif/templates/aiExtractReviewSuccess.php` | review select offers `subject access points` for tags/entities rows |

### (a) MCP tool descriptor
- `GET /iiif/ai/mcp` (auth) → tiny-iiif-style manifest: server name/version + 3 tools (`iiif_manifest_canvases`, `iiif_region_extract`, `iiif_list_extractions`) each with an `http` block (method + path template) and a JSON `inputSchema`. Lets an external AI client / thin MCP wrapper drive the existing endpoints deterministically. No new server process — the routes ARE the tools.

### (b) Subject access-point write-back
- Approving a `tags` or `entities` extraction to target `subject_access_points` turns the extraction into Subjects-taxonomy terms (`QubitTaxonomy::SUBJECT_ID = 35`) linked to the record.
- `extractTermNames()`: entities → each JSON entity `text`; tags → comma/semicolon/newline split; cleaned (strip list markers/quotes), CI-deduped, capped at 25, ≤100 chars each.
- Term create/find + link uses the proven **ahgAIPlugin pattern**: find-or-create term via Propel (nested-set-safe), then a **direct Capsule `object_term_relation` insert** — NOT `$io->save()`.

## Key findings / fixes (verification caught two real bugs)

1. **`$io->save()` rolls back the term relation when ES is down.** First attempt used `$io->setTermRelationByName(...)` + `$io->save()`. The arOpenSearch post-save hook throws a `TypeError` (`in_array(null)`) in CLI/no-ES; the term persisted but the `object_term_relation` did NOT — an **orphan term**, while `approve()` falsely reported success. Fix: link via direct Capsule insert (no IO save, no ES) — the ahgAIPlugin approach.
2. **`QubitTerm::save()` also throws on its own ES hook** (after inserting the term with lft/rgt). So `findOrCreateSubjectTerm()` must **re-query the term id after save** rather than trust the return — the row is committed before the hook fires. With that, both create and link are reliable regardless of ES state.

Both are CLI/no-ES artifacts; the web path indexes normally. `search:populate` reconciles the ES index for new subjects (noted in the approve response).

## Verification (PSIS, object 912509, throwaway uniquely-named terms)

- `mcpToolManifest()`: 3 tools, tasks enumerated, valid JSON; `GET /iiif/ai/mcp` → 401 unauth ✓
- `extractTermNames()` parser (isolation): tags dedupe + list-marker strip + entities JSON `text` ✓
- Subject write-back: term created in taxonomy 35 with **lft/rgt populated** (nested-set-safe), `object_term_relation` row created, row status `approved` ✓
- **Idempotent**: a second approve does not double-link (relation count stays 1) ✓
- **All test artifacts removed** (terms + relations + throwaway extract rows); object 912509 restored to prior state (scope NULL, 0 subject relations). One harmless nested-set gap remains from the removed test terms (AtoM tolerates gaps).
- Synced to archaeology (4 files md5-identical, cc both, php-fpm restart, MCP route registered).

## Follow-ups

- Optional: a standalone MCP wrapper script (Python/Node) that reads `/iiif/ai/mcp` and exposes the tools to Claude/Workbench (out of repo scope; the descriptor is the contract).
- Authenticated visual click-through of the review page (incl. the new subject-access-points option) still to be confirmed on PSIS.
- Optional tidiness: `propel:build-nested-set` if the Subjects tree ever needs gap-free lft/rgt.
