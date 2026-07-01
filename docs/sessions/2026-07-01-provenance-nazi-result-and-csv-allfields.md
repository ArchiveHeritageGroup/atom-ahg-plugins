# 2026-07-01 — Provenance: Nazi-era Result re-select + CSV export all fields (v3.79.26)

Follow-up to v3.79.25 (provenance notes-not-saving).

## Bug 1 — Nazi-era "Result" dropdown doesn't persist
`nazi_era_provenance_clear` is a `tinyint(1)`. The edit template compared the
DB value with a STRICT string `=== '1'` / `=== '0'`. Laravel returns the tinyint as an
int, so `1 === '1'` is false → the dropdown never re-selects the saved value → shows
"-- Select --" on reload. Worse: the next save then submits the (apparently empty)
dropdown and overwrites the DB back to NULL — so the value "disappeared". (The save path
itself was fine; the record's checkbox/status always saved.)

Fix (`modules/provenance/templates/editSuccess.php`): cast before compare —
`(string) ($record->nazi_era_provenance_clear ?? '') === '1'` (and `=== '0'`).

## Bug 2 — CSV export dropped all record-level fields
`ProvenanceService::exportCsv` only wrote the chain-of-custody EVENTS table. Every
record-level field (acquisition type/date/price, current status/owner, certainty,
has_gaps/gap_description, research status/notes, nazi-era checked/result/notes,
cultural-property status/notes, provenance_summary, acquisition_notes, is_complete/public)
was omitted.

Fix: prepend a "Provenance Record" key/value section with all record-level fields, then the
existing "Chain of Custody Events" table. Added a private `csvRow()` RFC-4180 quoting
helper used by both sections (record notes can contain commas/newlines). Verified live on
archaeology `/provenance/test/export` — full record block + events (with event notes)
present.

## Deploy
Lint clean; mirrored archive→archaeology; php-fpm restarted. Released v3.79.26 (pushed
origin/main + tag). Live CSV export also confirmed the v3.79.25 notes fix (nazi/cultural/
research/summary/acquisition + event notes all populated).
