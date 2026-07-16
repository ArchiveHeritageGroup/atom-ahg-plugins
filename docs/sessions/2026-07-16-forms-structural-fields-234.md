# ahgForms #234: FormSubmitService structural-field resolvers

**Date:** 2026-07-16
**Plugin:** ahgFormsPlugin
**Issue:** ArchiveHeritageGroup/atom-extensions-catalog#234
**Released:** atom-ahg-plugins v3.79.95
**Builds on:** #231 consumer + v3.79.94 template mappings

## What was added

`FormSubmitService` previously wrote only direct columns / properties / notes, so structural descriptive fields (level, dates, creators, subjects) stayed unmapped. Added three resolver/writer paths + 13 mappings so those fields now persist:

1. **`term_id` transformation** (FK columns): a select value resolves to a term id within a configured taxonomy (`transformation_config` `{"taxonomy_id":N}`; numeric→id, else normalised name match; unresolvable→reported). Used for `levelOfDescription`→`information_object.level_of_description_id` (tax 34) and `processingStatus`→`accession.processing_status_id` (tax 79).
2. **`event`** target: fields grouped by event type (default Creation=111) into one `object`+`event`+`event_i18n` row - `date` (display), `start_date`/`end_date` (ISO), and `actor` (creator). Used for `date`/`dateCreated` + `creators`/`creator`/`photographer`.
3. **`object_term_relation`** target: multi-value subject fields resolve to existing terms in a taxonomy and create `object`+`object_term_relation` rows (no vocabulary created on submit; unknown terms skipped). Used for `subject`/`subjects` (tax 35).

Actors are **create-or-linked** by authorized form of name (`WriteServiceFactory::actor()`; numeric→id, exact-name match, else create) - same behaviour as the edit page (name variations create duplicates).

## Mappings added (13, → 69 total)

level+date+creator on tpl 1 & 2; creator+date+subject on tpl 3; processingStatus on tpl 4; dateCreated+photographer+subjects on tpl 5. Live on PSIS + archaeology + seeded in `install.sql`.

## Still unmapped by design

`acquisitionType`/`processingPriority`/`receivedExtentUnit` (those taxonomies aren't populated on this install), `repository` (repository FK, different resolution), `language`/`script` (serialized property), `type`/`format` DC/photo term-selects, `contributor`/`personsDepicted` (actor name-access-point relations, not creation events). These report as unmapped and are completed on the edit page.

## Verified (PSIS, tx-rollback)

All templates create full records - `unmapped=[]` where every field is now mapped: tpl1 `level_of_description_id`→Fonds + creation event (actor+date); tpl3 event + subject→object_term_relation (People); tpl4 `processing_status_id`→Completed; tpl5 event + subjects→object_term_relation (Beach). Residue cleaned (rolled-back actor ES strays deleted; 2 orphan events found were pre-existing from Dec 2025, unrelated).

See [[forms_runtime_wiring_v3463]].
