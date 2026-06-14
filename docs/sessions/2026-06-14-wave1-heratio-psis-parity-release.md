# Heratio → PSIS Parity — Wave 1 (partial) released v3.62.9

**Date:** 2026-06-14
**Repo:** ArchiveHeritageGroup/atom-ahg-plugins
**Tag:** v3.62.9 (commit b42368e3)
**Context:** First execution batch of the Heratio→PSIS feature-parity port (plan: `docs/sessions/2026-06-13-heratio-psis-parity-audit.md`). Goal = bring Heratio (Laravel) functionality into the PSIS Symfony plugins. Wave 1 = core entities & metadata.

## Shipped in this release

### ahgCustomFieldsPlugin — `multiselect` field type
Adds a multi-select custom-field type alongside `dropdown`. Selections stored as a JSON array in `value_text` (no schema change). Admin picks a taxonomy (taxonomy selector now revealed for multiselect too); edit renders `<select multiple name="cf[key][]">`; view shows comma-joined labels.
Files: `lib/Service/CustomFieldService.php`, `lib/Service/CustomFieldRenderService.php`, `modules/customFieldAdmin/{actions/actions.class.php,templates/_fieldForm.php,templates/indexSuccess.php}`.

### ahgFunctionManagePlugin — ISDF names + maintenance notes
Parallel forms of name (`other_name` type 148), other forms of name (type 149), and the maintenance note (`note` type 127) — full read/edit/save/display via `OtherNameService`/`NoteService`. Surgical typed saves (delete-then-insert per type; maintenance note upserts by id so other notes are untouched).
Files: `lib/Services/FunctionCrudService.php`, `modules/functionManage/{actions/actions.class.php,templates/editSuccess.php,templates/viewSuccess.php}`.

### ahgTermTaxonomyPlugin — SKOS export + related-authorities + skos:validate
- **SKOS export** — new `SkosExportService` serialising a taxonomy as a SKOS concept scheme in RDF/XML, Turtle, N-Triples, JSON-LD (+ optional SKOS-XL). Route `/taxonomy/:id/skos?format=rdf|ttl|nt|jsonld`; export dropdown on the taxonomy browse page.
- **Related-authorities browse** — `TermTaxonomyService::loadRelatedAuthorities()` + `/term/:slug/related-authorities` action/template + link on term page.
- **`skos:validate` CLI** — read-only console task (S1 prefLabel presence, S2 duplicate prefLabel, S3 broader cycle).
Files: `lib/Services/SkosExportService.php` (new), `lib/Services/TermTaxonomyService.php`, `lib/task/skosValidateTask.class.php` (new), `modules/termTaxonomy/actions/actions.class.php`, `config/routing.yml`, templates (`relatedAuthoritiesSuccess.php` new, `indexSuccess.php`, `taxonomyIndexSuccess.php`).

### ahgRadManagePlugin — verified at parity (no change)
Verification showed both audited "gaps" were false: collection_type is absent in Heratio too; related-materials is the shared relation feature (deferred).

## Verification
- All files PHP-lint clean. No base-AtoM edits, no schema changes, Laravel-QB throughout.
- **Runtime-verified:** `sudo -u www-data php symfony skos:validate --taxonomy-id=35 --format=json` → 158 concepts, detected 2 real duplicate prefLabels ("photography", "creative commons"). Proves the term/term_i18n/parent_id query foundation used by all term-taxonomy features.
- Not yet browser-verified: multiselect save UI, function-names save UI, SKOS export web route, related-authorities page (5-min smoke test recommended).

## Method note
The Phase-0 audit over-counted gaps; a Wave-1 verification pass (8 read-only agents) cut 69 claimed gaps to ~50 real (e.g. rad-manage 10→2, term-taxonomy 14→9, custom-fields 2→1). Always verify gaps vs live code before building.

## Increment 2 (next release after v3.62.9)

### ahgFunctionManagePlugin — related functions + related resources (display)
`FunctionCrudService::getRelatedFunctions()` (bidirectional, scoped to `QubitFunctionObject`) and `getRelatedResources()` (function→`QubitInformationObject` via `relation`/`relation_i18n`), surfaced in the view's Relationships area with optional relationship description. Read-only; mirrors the proven `ActorCrudService` pattern. Completes function-manage's view-side relationship parity (edit/create of relations remains a follow-up).

### ahgTermTaxonomyPlugin — SKOS import (CLI)
New `SkosImportService` (parse RDF/XML with no DB access; topo-sort on `skos:broader` so terms are created parent-before-child with the correct `parentId`; create via `WriteServiceFactory::term()->createTerm/createOtherName/createNote`; skip duplicates by prefLabel) + `skos:import` task with `--dry-run` (no writes). **Round-trip verified:** exported taxonomy 35 → RDF, `skos:import --taxonomy-id=35 --dry-run` parsed 158 concepts, would-create 0 / skip 158, zero writes. Web-upload UI is a follow-up; CLI is complete.

## Remaining Wave 1 (not yet built)
- **Shared IO save path** (`IoFormHelper` + `InformationObjectCrudService`) — closes dacs (2), rad related-materials, mods (6), function related-functions/resources in one pass. Highest risk (core IO edit/save on prod) — needs a live test before shipping.
- ahgTermTaxonomyPlugin: SKOS import (write path), cross-vocab mapping (`ahg_term_cross_match` DDL), SHACL vendor file.
- #7 ahgRepositoryManagePlugin (10 gaps, locked, XL).
- #8 IO-manage Export/Import (verify overlap with existing ahgExportPlugin/ahgMetadataExportPlugin first).
