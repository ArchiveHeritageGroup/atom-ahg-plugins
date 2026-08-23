# ahgArchaeologyPlugin Phase 1 - stratigraphic context recording

**Date:** 2026-08-23
**Issue:** atom-ahg-plugins#190 (parity twin of heratio#1428)
**Instance:** PSIS / archive (`/usr/share/nginx/archive`)

## What and why

AtoM had no archaeology module at all. `ahgSiteRecordPlugin` records sites, but on a
different model - a site record extends an *actor*, from the RARI rock-art lineage - and
there was no find entity and no stratigraphy anywhere. Issue #190 asked for parity with
the Heratio `ahg-archaeology` module, so the decision taken was a full port of the
information-object-based model rather than bolting contexts onto the actor-based one.

The reason stratigraphy needs its own tables at all: AtoM's description structure is a
tree, one parent per record. A stratigraphic sequence is a directed acyclic graph - a
layer can lie beneath two others at once, a cut can truncate several deposits, and two
contexts in different trenches can prove to be the same event. None of that fits a
parent-child hierarchy, so the sequence cannot ride `information_object` and needs an
edge table.

## Delivered

New plugin `ahgArchaeologyPlugin` v0.1.0, 14 files.

- `database/install.sql` - `archaeology_site`, `archaeology_context`,
  `archaeology_context_relationship`, `archaeology_object`. VARCHAR + COMMENT rather than
  ENUM throughout; utf8mb4_0900_ai_ci to match core.
- `lib/Services/ArchaeologyService.php` - vocabularies, site and context CRUD,
  description creation, find backfill, dashboard statistics.
- `lib/task/archaeologySeedVocabulariesTask.class.php` - `ahg:archaeology-seed-vocabularies`.
- `modules/archaeology/` - 9 actions, security.yml, 7 templates.

## Decisions worth keeping

**All four tables ship in install.sql now, including the relationship table whose UI is
Phase 2.** The installer runs `install.sql` and nothing else - `migration_*.sql` files in
a plugin's `database/` directory are never executed - so a table "added later" in a
migration would silently not exist on any fresh install.

**Descriptions are spliced into the nested set explicitly.** The framework's
`StandaloneInformationObjectWriteService` builds the `object` -> `information_object` ->
i18n chain and `autoSlug` writes the slug, but **nothing sets `lft`/`rgt`**. Those columns
are nullable, so the insert succeeds and the description never appears in the tree until
someone runs `propel:build-nested-set`. `ArchaeologyService::placeInNestedSet()` does the
standard insert-as-last-child splice; if the parent is itself outside the tree it declines
rather than corrupting the set.

**A context with no site description gets no description at all**, deliberately. Creating
one at the root of the tree would be worse than none, because nobody goes looking for a
stray top-level description.

## Gotchas hit

- **`sfBaseTask::execute()` must be `public` and untyped.** A `protected function
  execute(...): void` is signature-incompatible and fails at load.
- **`getApplicationConfiguration()` does not exist on `sfBaseTask`.** Tasks bootstrap the
  query builder with `new sfDatabaseManager($this->configuration)`. The former would also
  poison the production cache from CLI.
- **Templates must not emit a CSRF field.** `AhgController` declares `$csrf_token` as a
  real property so it never reaches the template holder; a hand-written hidden field posts
  empty and overrides ahgCorePlugin's injection, failing validation.
- **Terms created through `StandaloneTermWriteService` have `lft IS NULL`** - the same
  nested-set gap as the information-object service, not yet fixed there. All 50 seeded
  terms are outside the term nested set until `propel:build-nested-set` runs. Term
  dropdowns are unaffected because the service queries `term`/`term_i18n` directly.

## Verification

- Schema validated in a throwaway `archaeology_sqlcheck` schema first: 4 tables, 5 foreign
  keys resolved, second run a clean no-op. Schema dropped; `archive` and `archeology`
  confirmed untouched.
- Installed into `archive`: 4 tables, 0 rows.
- Plugin enabled in `atom_plugin` (is_enabled 1, is_core 0, is_locked 0, load_order 60).
- Seeder: 6 taxonomies, 50 terms. Re-run created 0 - idempotent. Every term has a slug
  (0 without) and is parented to `QubitTerm::ROOT_ID` (0 mis-parented), so `search:populate`
  will not throw "Couldn't find term".
- Routes: `/archaeology`, `/archaeology/sites`, `/archaeology/site/add`,
  `/archaeology/site/1` all return the login page (resolved and access-gated), while
  `/nonexistent-route-xyz` returns 404 - proving registration rather than fallback.
- `ahg_error_log` clean for the window.

## Not yet done

- Phase 2 relationships, Phase 3 Harris Matrix, Phase 4a forms, Phase 4b CSV/PDF/ES.
- Find browse and view deliberately `forward404` with an explicit message; routes and
  schema exist.
- **The write path is unexercised.** No site or context has been created, so
  `saveContext()` -> `ensureContextDescription()` -> `placeInNestedSet()` has not run
  against real data.
- `propel:build-nested-set` not run - a whole-catalogue tree rebuild on a live instance
  was outside the approved steps.
- Nothing deployed to archaeology (`/usr/share/nginx/archeology`), per the standing
  do-nothing-unless-told rule.
- Not released; no version bump, no commit.

## Follow-on filed

Sections and 3D rendering was scoped as a separate enhancement: schematic section from
elevations, bracket notation derived from context type, hotspot linking on the scanned
measured section, and 3D linking through `ahg3DModelPlugin`. Blocked in part by
`excavation_reference` being free text, which prevents reliable per-trench grouping.
