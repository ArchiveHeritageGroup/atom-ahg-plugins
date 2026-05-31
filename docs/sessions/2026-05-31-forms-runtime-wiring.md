# ahgFormsPlugin — runtime form render + save wiring (#231)

**Date:** 2026-05-31
**Plugin:** ahgFormsPlugin (atom-ahg-plugins)
**Issue:** ArchiveHeritageGroup/atom-extensions-catalog#231
**Status:** Implementation complete, lint-clean; route/dispatch verified on PSIS; authenticated render + submit E2E pending.

## Problem

`ahgFormsPlugin` was a form *builder* with no *consumer*: templates could be designed
and stored, but nothing rendered them on an edit page at runtime. `resolveTemplate()`
worked and `executeApiGetForm()` returned template JSON, but had zero callers; the
`ahg_form_field_mapping` table was populated by seed data but never read to save.

## Approach (chosen over core-edit override / display_panels)

Self-contained **dedicated forms route**, mirroring how the GLAM sector plugins ship
their own edit routes. Core `/informationobject/edit` is untouched; no dependency on the
locked theme (the `DisplayActionRegistry` display_panels are registered but never rendered
by the theme, so that path was not viable without unlocking ahgThemeB5Plugin).

## Changes

- **Routes** (`config/ahgFormsPluginConfiguration.class.php`): `/forms/new/:templateId`,
  `/forms/edit/:type/:id`, `/forms/submit`.
- **FormRenderService** (new): resolved template → Bootstrap 5 form. Handles single/tabs
  layout, sections, 14 field types, width columns, prefilled values. CSP-safe (no inline JS;
  Bootstrap tab data-attributes only).
- **FormSubmitService** (new): reads `ahg_form_field_mapping`, applies transformations
  (uppercase/lowercase/trim/ucfirst/date_format), writes information_object / accession core
  + i18n via `WriteServiceFactory`, writes property/note via the object→entity→i18n chain,
  records `ahg_form_submission_log`, clears the user's draft. Unmapped fields are reported
  back (surfaced in a flash), never silently dropped.
- **FormValueLoader** (new): inverse read — prefills the form from an existing record's
  mapped columns (IO/accession core + i18n, property values, note contents).
- **Actions**: `executeRenderNew`, `executeRenderEdit`, `executeSubmit` + helpers
  (required-field server validation, post-save redirect to the saved record by slug).
- **Template**: `renderFormSuccess.php`.
- **UI entry points**: "Use form" button on the template list and "Use this form" on the
  preview page (plugin-local templates only — no theme edits).

## Verification

- `php -l` clean on all 8 changed/new files.
- Cache cleared + php8.3-fpm restarted on PSIS; `/forms/new/:id` resolves and dispatches
  identically to the existing `/admin/forms` route (auth gate → login, HTTP 403 when
  unauthenticated). Not 404/500 — route registered, action + services load at runtime.
- **Pending:** authenticated visual render + an end-to-end submit creating a real
  information_object (deferred — needs login session / DB-write approval).

## Notes / follow-ups

- Latent pre-existing bug (not fixed here): `executeFieldAdd()` writes a non-existent
  `atom_field` column on `ahg_form_field`; that action is not currently routed.
- Accession submit path implemented but only IO templates are seeded; accession mapping
  coverage unverified against live schema.
