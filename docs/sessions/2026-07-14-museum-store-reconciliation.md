# Museum store reconciliation + heratio-dev architecture check - 2026-07-14

Cross-instance knowledge note for any agent (PSIS/archive, heratio, workbench, ahg-ai).

## Summary

PSIS/archive (Symfony AtoM) had a museum-record **split-brain**: the `museum` module's edit
form wrote a JSON blob (`property` name='ccoData') while every reader used the structured
`museum_metadata` table. This was fixed by making `museum_metadata` the single canonical
store (the museum editor now loads and saves it). **heratio-dev (Laravel) was checked and
does NOT have this problem** - its `ahg-museum` package already uses `museum_metadata` as
the one store. heratio-dev is therefore the reference architecture.

## The PSIS split-brain (fixed)

- **Edit path:** museum module edit form -> `property` name='ccoData' (JSON blob), 34 keys,
  mostly `*_display` denormalized/placeholder values.
- **Read paths (canonical):** `museum_metadata` table (91 structured columns) is read by
  reports (`museumReports`), facets (`ahgDisplayPlugin` DynamicFacetService), Report Builder
  (`ahgReportBuilderPlugin`), CSV export/import (`ahgDataMigrationPlugin`), Spectrum
  (`ahgSpectrumPlugin`), MuseumMetadataHelper, and the museum browse index - 8+ consumers
  across 6 plugins.
- **Consequence:** museum edits never reached the readers; reports/facets showed stale data,
  and ccoData-only records were invisible to every reader.
- **Reconcile diff (read-only):** 23 ccoData vs 20 museum_metadata records (18 overlap).
  `museum_metadata` is rich/canonical; `ccoData` had ~0 unique real data (overlap = 0 fields
  equal, 32 placeholder-vs-real "conflicts", 110 fields only in museum_metadata vs 10 only in
  ccoData; the 5 ccoData-only records are demo/test/skeleton). A legacy `cco` module also
  writes museum_metadata via a `register_shutdown_function` hook (older path).

## Fix shipped (PSIS)

- `atom-framework` **v2.13.22-25**: `SectorRecordWriteService` gains
  `syncMuseumMetadata()` (authoritative, non-empty overwrite, skips empty, never touches the
  ~70 unmapped columns) + `museumFormOverridesFromMetadata()` (canonical museum_metadata ->
  form fields). Field mapping centralised in `MUSEUM_FORM_TO_METADATA`.
- `atom-ahg-plugins` **v3.79.83-85**: the museum edit form now LOADS canonical
  museum_metadata values (overlay onto its ccoData) and SAVES authoritatively back to it.
  Verified end-to-end: canonical load, edits to populated fields propagate to readers,
  unchanged fields preserved, no-op saves safe, /museum/add + /museum/edit/:slug render 200.
- One `period` value (io=900951) salvaged from ccoData into museum_metadata.
- **Step 4 PENDING (PSIS):** retire the ccoData write path (archive the blobs, do NOT drop)
  and retire the legacy `cco` module. Then repoint the `SectorRecordWriteService` museum
  sector primary store from ccoData to museum_metadata.

## heratio-dev check (read-only) - NO split-brain

- `packages/ahg-museum/src/Services/MuseumService.php` uses `museum_metadata` as the single
  store: reads via `getBySlug()` (joins museum_metadata, selects its columns), writes via
  `museum_metadata')->insert(...)` (approx lines 573, 1154).
- No `ccoData` property blob anywhere in `ahg-museum`. (ccoData references elsewhere in
  heratio - gallery, sharepoint, translation, ingest - are CCO field definitions/translation/
  import, not museum storage.)
- heratio-dev already implements the target architecture PSIS was migrated toward. It is the
  reference implementation for PSIS Step 4. **No change needed on heratio-dev.**

## Related

Full CRUD sector write service (`SectorRecordWriteService`) covers Library (`library_item`),
DAM (`dam_iptc_metadata`), Museum (`museum_metadata` / historically ccoData), Gallery
(`property` galleryData). Test harness: `atom-ahg-plugins/testing/entity-write-roundtrips.php`.
No changes were made to heratio-dev in this work - inspection only.
