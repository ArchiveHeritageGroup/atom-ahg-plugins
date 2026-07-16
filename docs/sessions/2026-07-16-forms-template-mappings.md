# ahgForms: field mappings for templates 2-5 (+ accession create fix)

**Date:** 2026-07-16
**Plugins:** ahgFormsPlugin; **Framework:** atom-framework (StandaloneAccessionWriteService)
**Released:** atom-ahg-plugins v3.79.94 + atom-framework (patch)
**Context:** follow-up to #231 (forms consumer). Only template 1 (ISAD-G Minimal) had field mappings; templates 2-5 were seeded without any, so a submit created nothing.

## What was added

**52 field mappings** (`ahg_form_field_mapping`) for templates 2-5 - applied to the live PSIS + archaeology DBs and appended to `ahgFormsPlugin/database/install.sql` (name-resolved `INSERT...SELECT` against the `@isadg_full_id / @dc_simple_id / @accession_id / @photo_id` vars) for fresh installs. Total mappings now 56 (was 4).

Engine capability (`FormSubmitService`): maps to **direct IO/accession columns**, **`property`** (EAV, IO only), and **`note`** (typed, IO only). It does NOT create events/actors/term-relations, so structural fields stay **unmapped by design** (reported to the user, filled via the normal edit page):

| Template | Mapped | Unmapped (structural) |
|---|---|---|
| 2 ISAD-G Full (IO) | 18 i18n/base cols + `descriptionIdentifier` + 3 notes (pub/general/archivist 120/125/124) | date, levelOfDescription, creators, language, adminHistory |
| 3 Dublin Core (IO) | title, description→scope, identifier, rights→reproduction_conditions + 5 `dc.*` properties | creator, subject, contributor, date, type, language |
| 4 Accession | identifier, date, title, scope, appraisal, physical_characteristics, processing_notes, donorName→source_of_acquisition, receivedExtentNumber→received_extent_units | acquisitionType, receivedExtentUnit, donorAddress, donorContactInfo, processingStatus, processingPriority (accession takes no property/note) |
| 5 Photo (IO) | title, identifier, description→scope, dimensions→physical_characteristics, access/use restrictions + `condition`→note(288) + 5 `photo.*` properties | photographer, dates, format, copyright, subjects, personsDepicted |

## Framework bug fixed (pre-existing)

`StandaloneAccessionWriteService::createAccession` never set `created_at`/`updated_at`, but the `accession` table requires them (NOT NULL, no default) - so **all** accession creation via this service errored (`Field 'created_at' doesn't have a default value`). IO creation was unaffected (its timestamps live on `object`). Fixed by defaulting both to `now()` in `createAccession`.

## Verified (PSIS, transactional rollback - zero persist)

All 4 templates create correct records: mapped columns, typed notes, and EAV properties all land; unmapped structural fields are correctly reported; accession now creates after the framework fix. No DB/ES residue. Templates 1's earlier E2E (#231) still holds.

See [[forms_runtime_wiring_v3463]].
