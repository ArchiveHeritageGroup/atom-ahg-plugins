# ahgArchaeologyPlugin Phases 2-3 - stratigraphic relationships and the Harris Matrix

**Date:** 2026-08-23
**Issue:** atom-ahg-plugins#190
**Built on:** archive/PSIS. **Tested and seeded on:** archaeology (atom210, 192.168.0.131)

## Delivered

**Phase 2 - relationships.** Nine recording types with their reciprocals and temporal
direction (`REL_TYPES`). Adding one writes both directions, so a relationship stated once
cannot disagree with itself. `laterThanReaches()` walks the above/cuts/fills graph
iteratively with a seen-set and refuses any edge that would close a loop, plus
self-relations and unknown types. Removing an edge removes its mirror.

**Phase 3 - Harris Matrix.** Union-find over `same_as` merges correlated contexts into one
node; Kahn longest-path layering then places each node as far down as its deepest chain of
superposition requires. Rendered server-side as tiered markup with Bootstrap classes only -
no charting library, no CDN, no inline style attributes, so nothing for CSP to block.
Mermaid source offered separately for redrawing elsewhere.

**Bracket notation.** `contextLabel()` derives conventional notation from the context
*type*, not from how the number was typed: cuts and interfaces in square brackets, deposits
and fills in round. Consistent across matrix, context list and sheet.

**`ensureSiteDescription()`** - a gap found by a failing assertion, see below.

## Harris allows three relationships; we record nine

Harris (1989) states only three connections are possible between two units: none,
superposition, and correlation as parts of a once-whole deposit. The nine types excavators
actually write down collapse onto those three - above/cuts/fills and their reciprocals are
superposition, `same_as` is correlation, and `bonds_with`/`abuts` carry no ordering at all.
The matrix therefore builds edges from `LATER_THAN` only and merges on `same_as`; the other
two are recorded and displayed but contribute nothing to the layout. That is correct, not
an omission.

## The bug the test found

The first seed run passed 21 of 22 assertions. The failure: every context had a description
and **none was in the tree**.

Cause was in the test, but it exposed a real gap. The test created the *site* description
with `StandaloneInformationObjectWriteService` directly, which leaves `lft`/`rgt` null.
`placeInNestedSet()` then correctly **declined** to splice the context descriptions beneath
a parent that is itself outside the nested set - refusing rather than corrupting the set,
exactly as designed. But the plugin had no path of its own to create a site description, so
any caller would have hit the same wall.

Fixed by adding `ensureSiteDescription(int $siteId, ?string $title, ?int $parentIoId)`,
which routes through the same tree-aware `createDescription()`. Re-run: 22 of 22.

**The lesson worth keeping: an assertion that a record "exists" would have passed. Only
asserting it was in the tree caught it.**

## Seeded scenario on archaeology

`BLB-2026`, Blaauwbosch Farm 2026 excavation, hung under the existing "Blaauwbosch Farm
excavation archive" (IO 5302) rather than floating at the root.

- **20 contexts** across two trenches (12 / 8): topsoils, colluvium, a storage pit cut and
  its fill, an occupation deposit, a hearth cut and fill, a stone wall footing abutting its
  construction backfill, a boundary ditch cut and fill, a trampled surface, laminated sands
  and sterile basal sands.
- **22 logical relationships**, stored as 44 rows (both directions).
- **3 correlated pairs across the trenches** (`1009 = 2009`, `1010 = 2010`, `1001 = 2001`),
  so 20 contexts resolve to **17 nodes** in the matrix.
- **7 tiers**, no cycle.

## Verified (22/22)

Reciprocity written both ways; cycle refused (`1010 above 1001`); self-relation refused;
unknown type refused; matrix cycle-free with correct context and relationship counts; 3
pairs merged to 17 nodes; 7 tiers; latest tier holds both topsoils and the unstratified
cuts and fills; `[1003]` for a cut and `(1004)` for a fill; all 20 descriptions created
**and** placed under the site; no duplicate `lft` anywhere in `information_object`.

Routes resolve and are access-gated both directly on .131 and through
`archaeology.theahg.co.za`.

## Still open

- Phase 4b: CSV import, context-sheet PDF, Elasticsearch and spatial indexing.
- Find browse and view still `forward404` by design.
- `propel:build-nested-set` not run: the 50 seeded terms on both instances still have
  `lft IS NULL`, because `StandaloneTermWriteService` has the same nested-set gap the
  information-object service has. Term dropdowns are unaffected.
- Not released, not committed.
