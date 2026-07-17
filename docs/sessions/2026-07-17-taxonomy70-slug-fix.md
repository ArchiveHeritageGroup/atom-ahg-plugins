# Fix: taxonomy-70 display-standard terms lacked slugs (search:populate error)

**Date:** 2026-07-17
**Repos:** atom-ahg-plugins v3.79.100, atom-framework v2.13.33
**Trigger:** clean-room full-plugin install on VM atom210 - `search:populate`
printed `Couldn't find term (id: 449/455/456/464)` (still completed, 343 docs).

## Root cause

`ahgMuseumPlugin`, `ahgGalleryPlugin`, `ahgDAMPlugin`, `ahgLibraryPlugin` each
insert a "display standard" term into taxonomy 70 (Information object templates)
via `install.sql`, creating the `object` + `term` + `term_i18n` rows but **no
`slug` row** (and no `parent_id`). Base AtoM's term indexer
`arOpenSearchTermPdo::loadData()` does an **INNER JOIN on `slug`**:

```sql
FROM term JOIN slug ON term.id = slug.object_id JOIN object ON term.id = object.id
WHERE term.id = :id
```

A term with no slug row returns zero rows → `throw sfException("Couldn't find
term (id: ...)")`. (The nested-set / `parent_id` was a red herring - the terms
were also orphaned from the term tree, but that is not what raised the error.)

## Fix

1. **4 plugins** - add `parent_id = 110` (`QubitTerm::ROOT_ID`) to the
   taxonomy-70 term INSERT so the term is a proper child of the term root
   (matches base tax-70 terms 353-357 = ISAD/DC/MODS/RAD/DACS, all parent 110).
2. **Post-install docs** (INSTALLATION.md + README) - add two AtoM tasks before
   `search:populate`:
   - `propel:build-nested-set` - recompute `lft`/`rgt` after plugins add terms.
   - `propel:generate-slugs` - **the actual fix**: backfills slugs for any
     slug-less object (produces name-based slugs, e.g.
     `museum-cco-cataloging-cultural-objects`), so the indexer's slug JOIN
     matches.

## Verified (VM atom210)

- The 4 terms had `slug_rows=0`; `propel:generate-slugs` backfilled them.
- `search:populate` → **0 "Couldn't find term" errors** (was 4).
- All 109 plugins enabled (all except ahgFederationPlugin + ahgMultiTenantPlugin),
  site healthy, empty catalog (no demo data).

## Note

The canonical fix for "term created via SQL without a slug" in AtoM is
`propel:generate-slugs`, not hardcoding slug values per plugin (arbitrary +
collision-prone). Hence the post-install task rather than slug INSERTs.
