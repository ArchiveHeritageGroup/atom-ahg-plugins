# Route 500 fixes — accessions numbering + forms template import (#187)

Date: 2026-06-27
Instance: PSIS / archive (Symfony AtoM)

## Context
The sequential Playwright pass over the merged manual-test checklist exposed 2 real
authed 500s that the earlier `curl -L` sweep had masked (it follows redirects). Both
are in unlocked AHG plugins; neither touches a base AtoM form or base table structure.

## Fix 1 — `/admin/accessions/numbering` (ahgAccessionManagePlugin → 2.0.1)
`accessionIntakeActions::executeNumbering` joined `repository_i18n as ri` and selected
`ri.authorized_form_of_name`. That column does not exist on `repository_i18n` — a
repository's display name lives on `actor_i18n` (`repository.id == actor.id`), so the
query 500'd with an unknown-column error.

Change: `leftJoin('repository_i18n as ri', …)` → `leftJoin('actor_i18n as ri', …)`.
Reads the correct existing base column; no schema change.

## Fix 2 — `/admin/forms/template/import` (ahgFormsPlugin → 1.0.1)
`formsActions::executeTemplateImport` only redirected inside the successful-POST branch.
On GET (or a failed/no-file POST) it fell through with no view → Symfony tried to render
`templateImportSuccess.php`, which does not exist → 500.

Change: added a trailing `$this->redirect('@ahg_forms_templates');` so the action always
returns to the templates page when it has nothing to render.

## Verification (authed, maxRedirects off)
- `/admin/accessions/numbering` → **200** (was 500)
- `/admin/forms/template/import` → **302** (clean redirect to templates; was 500)

Merged checklist updated: 7 rows → PASS, regenerated `.docx`.
Final tally: PASS=640 FAIL=0 N/A=127 (parameterless route surface).

## Constraints honoured
No base AtoM file, no base AtoM form, no base table schema change. Only AHG-plugin
action code corrected.
