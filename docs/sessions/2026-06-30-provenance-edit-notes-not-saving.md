# 2026-06-30 — Provenance edit form: notes not saving (v3.79.25)

## Symptom
On `/provenance/:slug/edit`, several fields didn't persist: chain-of-custody event
**Notes**, **Nazi-era Notes**, **Cultural Property Notes** (and gap description).

## Root cause — write/read table split
`ahgProvenancePlugin` stores provenance notes on TWO places: the base `provenance_record`
table AND `provenance_record_i18n`. The **read** path (`ProvenanceRepository::
getByInformationObjectId`) selects the notes from the **base** table (`pr.*`), but the
**write** path (`ProvenanceService::createRecord`) routed them only to
`provenance_record_i18n` via `saveRecordI18n`. So notes saved to the wrong table and read
back empty — never round-tripping.

Verified on archaeology `test` record (id 2): `cultural_property_notes="Cultural Property"`
sat in `_i18n` while the base column was NULL.

Chain-of-custody event notes were worse: `processEvents` read `event_notes[]` into a var
but never added it to `$eventData`, and `ProvenanceService::addEvent` didn't list `notes`
either — so event notes were dropped entirely (never written anywhere).

NOTE: cultural_property_status ("Disputed"), nazi_era_provenance_checked, and _clear are
base columns written by `saveRecord` and read from base — they DID save; only the notes
were broken (the user's report lumped them together).

## Fixes (3 code changes)
- `ProvenanceService::createRecord` — write `provenance_summary`, `gap_description`,
  `research_notes`, `nazi_era_notes`, `cultural_property_notes` to `$recordData` (base
  table) where the read path reads them. (`acquisition_notes` has no base column, stays
  i18n-only — read via `pri.acquisition_notes`.)
- `ProvenanceService::addEvent` — add `'notes' => $data['notes'] ?? null` to `$eventData`
  (writes to `provenance_event.notes`, which `getEvents` reads via `pe.*`).
- `modules/provenance/actions/actions.class.php` `processEvents` — add
  `'notes' => $eventNotes[$i] ?? null` so the posted `event_notes[]` reaches the service.

## Data backfill (non-destructive)
Records saved before the fix had notes stranded in `_i18n` with base NULL. Ran a
COALESCE/NULLIF backfill on BOTH archeology and archive DBs to copy i18n notes into the
base columns where base was empty (fills empties only, never overwrites):
`UPDATE provenance_record pr JOIN provenance_record_i18n pri ON pri.id=pr.id SET
pr.<col>=COALESCE(NULLIF(pr.<col>,''), pri.<col>) …` for nazi_era_notes,
cultural_property_notes, gap_description, research_notes, provenance_summary. archeology
test record recovered "Cultural Property"; archive backfilled 1 row.

## Deploy
Lint clean; mirrored archive→archaeology; php-fpm restarted. Released v3.79.25 (pushed
origin/main + tag). The dual-write to `_i18n` is left in place (harmless; acquisition_notes
still needs it).
