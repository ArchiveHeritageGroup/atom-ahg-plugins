# ahgArchaeologyPlugin Phases 4a and 4b - finds, CSV import, context sheets, spatial

**Date:** 2026-08-23
**Issue:** atom-ahg-plugins#190
**Built on:** archive/PSIS. **Tested and demonstrated on:** archaeology (atom210, 192.168.0.131)

With this the AtoM-line port covers every phase of heratio#1428: 1, 2, 3, 4a and 4b.

## Phase 4a - finds

Find browse, view and form. Browse searches accession number, title and storage location and
filters by site, by context, and by **"not linked to a context"** - that last one is the
provenance backlog, which is a post-excavation question rather than a UI convenience.

Two rules the form enforces, both verified:

**A find's description is created beneath its CONTEXT**, not beneath the site, falling back
to the site only when no context is recorded. That is what makes an assemblage visible as a
group in the archival hierarchy instead of only through a query.

**A site and context that disagree are refused**, and supplying only a context infers the
site from it. A context belongs to exactly one site, so accepting a mismatched pair would
place a find in one site's assemblage and another's context - a contradiction nothing
downstream could resolve.

The context picker refreshes on site change through a `contexts.json` endpoint plus an
inline script carrying the CSP nonce. Without it the picker keeps whichever site's contexts
were rendered at page load, so changing the site leaves a list that looks correct, belongs
to the wrong dig, and produces a save refused for a mismatch the user cannot see. It
degrades: with scripting off the server still renders the right contexts, and a failed fetch
leaves the existing list rather than emptying it.

## Phase 4b - CSV import

Contexts and their relationships load from one sheet, two passes: upsert every context, then
resolve relationship columns to ids, so a row may name a context defined further down the
same file.

**The preview is a real run inside a transaction that is rolled back**, not a simulation. A
simulation that diverges from the real thing is worse than no preview. Verified: preview
left 20 contexts at 20 while reporting the 5 creations it would make; commit took it to 25;
re-import stayed at 25 with 5 updated in place.

Relationships go through `addRelationship()`, so reciprocity and the cycle guard apply
exactly as to typed entry. **An import cannot introduce a contradiction the form would have
refused.** Unknown context type, a row with no `context_number`, and a relationship naming a
nonexistent context were each warned individually without aborting the run.

Parsing is BOM-tolerant. A sheet saved from Excel begins with one, which would otherwise
make the first column name unmatchable - every row would be reported as missing
`context_number` while the file looks perfectly correct.

## Phase 4b - context sheet PDF

dompdf, already present in `atom-framework/vendor`, with a styled-HTML fallback where the
library is absent. Relationships are grouped in conventional order rather than
alphabetically, so it reads as a context sheet.

## Phase 4b - Elasticsearch and spatial

**Elasticsearch needed nothing.** Sites, contexts and finds each extend `information_object`,
so all were already indexed. Verified against the index directly.

**Spatial could not be done in the index.** AtoM's `information_object` mapping carries
**48 properties and not one geo field** - no `geo_point`, no `geo_shape`. Heratio had a
`gis` field to populate; AtoM has nothing to populate. Adding one means editing
`config/search.yml`, a base AtoM file, which is out of bounds on archaeology and RARI.

So spatial is answered from the database instead: `sitesNear()` computes great-circle
distance in SQL behind a deliberately generous bounding box (a tight box silently drops
sites near its corners). Verified against known separations: a site at its own coordinate
returns 0.0000 km, Pretoria to Johannesburg returns 49.9 km against a real ~50 km, the same
query at a 10 km radius excludes rather than merely re-sorting, and Cape Town returns
nothing.

**The consequence to remember: archaeology sites appear on the plugin's map, not on AtoM's
own map**, and proximity is a database question rather than a search question. Closing that
gap requires a base AtoM change.

## The dig plan and map

Two server-rendered views, both inline SVG with presentation attributes only and no `style=""`
attributes anywhere - see the CSP note from the same day for why that matters.

The plan draws a scaled section per trench and is **filterable**: by trench, by context type,
by elevation window, features on or off, and vertical exaggeration to x4. Filters are GET
parameters and the whole drawing re-renders server-side, so there is no client state and
nothing for CSP to block.

Both views are built to refuse to mislead. The plan reports how many contexts a filter hid.
A context missing either elevation is listed separately rather than drawn at a guessed depth,
and an interval with nothing recorded stays blank. The map states coverage before it draws
("N of M sites have a recorded position") because a map that quietly omits the unplaced ones
lets a reader conclude there is nothing in the empty space, and it draws the recorded
positional accuracy to scale as a halo so a 5 km estimate cannot be read as a survey point.

## The description panel

A site, context or find is an information object, so AtoM serves it through its ordinary
description view, which knows nothing about this plugin. `ArchaeologyPanelInjector` adds a
panel to those pages linking to the stratigraphy, the matrix, the plan and the context sheet.
Without it, landing on a site description offers no route to its own Harris Matrix, and the
excavation record and the catalogue record look like two unrelated systems.

It follows `SiteRecordPanelInjector`'s lesson: there is deliberately no "is another plugin
rendering this?" check, only a marker test that answers the question by observation.

## Bug found in the port

**`$this->context` is a reserved action property.** `sfComponent::initialize()` assigns
`$this->context`, creating a real property. PHP's `__set()` only fires for *inaccessible*
properties, so `$this->context = $row` in an action writes straight to Symfony's own context
object: the template variable is never created (it reads as null) **and the request's context
is destroyed**. Surfaced as
`contextLabel(): Argument #1 must be of type object, null given`.

The context record now travels as `$this->ctx`. Reserved names are `context`, `request`,
`response`, `dispatcher`, `varHolder`, `moduleName`, `actionName`,
`requestParameterHolder` - documented at the top of the actions class. Same class of trap as
`$csrf_token` in `AhgController`.

## State

BLB-2026 on archaeology: 20 contexts, 22 relationships, 6 finds, published, with the Harris
matrix and schematic section attached as digital objects. Nested set clean.

Not committed, not released, not deployed anywhere but archive and archaeology.
