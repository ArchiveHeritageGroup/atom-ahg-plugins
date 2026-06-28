# Route 500 fixes — workflow / iiif / forms (#187, end-to-end pass)

Date: 2026-06-28
Instance: PSIS / archive (Symfony AtoM)

## Context
End-to-end Playwright pass over the menu-driven checklist (every menu item →
every link/URL), run authed + sequential with real ids/slugs substituted, found
6 parameterised 500s. Five are plugin-side and fixed here; one is base-AtoM.

## Fixes

### ahgWorkflowPlugin → 1.0.1
1. **`/workflow/publish-simulate/:object_id`** & rights pre-check — `PublishGateService`
   queried `rights.object_id`, which does not exist. The base `rights` table links to
   objects through `relation` (type_id 168 = `QubitTerm::RIGHT_ID`, `subject_id` = the
   information object, `object_id` = the rights record — confirmed from base
   `QubitFlatfileImport::createRelation` and `QubitObject::getRights`). Rewrote both the
   preview query and `evaluateHasRights()` to join via `relation`.
2. **`/workflow/history/:object_id`** — `executeObjectHistory` had no view; rendered a
   missing `objectHistorySuccess.php`. Added the template (history table with
   workflow/step/status/user/comment columns, modelled on `historySuccess.php`).
3. **`/workflow/start/:object_id`** — `redirect(getReferer() ?? 'workflow/dashboard')`:
   `getReferer()` returns `''` (not null) when absent, so `??` didn't fall back and
   `redirect('')` 500'd ("Cannot redirect to an empty URL"). Changed to `?:`.

### ahgIiifPlugin → 1.0.2
4. **`/admin/iiif-validation/run/:object_id`** — `IiifValidationService` rights check
   used the same non-existent `rights.object_id`. Rewrote to join via `relation`
   (matching the already-correct `IiifManifestV3Service` direction).

### ahgFormsPlugin → 1.0.2
5. **`/admin/forms/template/:id/edit`** — `executeTemplateEdit` rendered a missing
   `templateEditSuccess.php` on GET. The drag-drop **builder** is the editor; on GET it
   now redirects there (mirrors the existing clone/edit redirect pattern).

## Not fixed — flagged
- **`/accession/:slug/edit`** → `Unknown record property "sf_method" on QubitAccession`.
  The error is inside **base AtoM's** accession edit action mass-assigning request params;
  the pretty override route (plain `sfRoute`) can't bind a `resource` the way base's
  `sfPropelRoute` does. Cannot fix without modifying base AtoM (locked). Base AtoM's own
  accession edit UI is unaffected. Left flagged FAIL in the checklist.

## Verification (authed, maxRedirects off, logs confirmed clean)
- `/workflow/publish-simulate/553` → 200
- `/workflow/history/553` → 200
- `/workflow/start/553` → 302
- `/admin/iiif-validation/run/553` → 200
- `/admin/forms/template/1/edit` → 302

Data-safety: confirmed the GET pass did not mutate — the 10 `ahg_workflow_history`
rows for object 553 predate testing (2026-06-27 23:23); `startWorkflow` returned null
(no workflow configured) on the GET, writing nothing.

## End-to-end checklist
`AHG_EndToEnd_Test_Checklist.{md,docx}`: every menu item → every link/URL, auto-ticked
from the pass. Final: PASS 965 · FAIL 2 (1 distinct, base-AtoM) · N/A 338 ·
destructive/manual 86. Remaining ☐ rows are for the manual tester (parameterised,
destructive/POST, button/JS).

## Constraints honoured
No base AtoM file, form, or table-structure change. Only AHG-plugin/query/view code.
