# Harris Matrix #306 closed: reduction, export, import, consistency, scale

Date: 2026-08-24
Releases: atom-ahg-plugins v3.106.5, .7, .8, .9, .10
Issue: atom-extensions-catalog#306

## What shipped

| item | release |
|---|---|
| Transitive reduction | v3.106.5 |
| Export - data package, GraphViz DOT, Phaser CSV | v3.106.7 |
| Relationship import - Phaser CSV, LST | v3.106.8 |
| Consistency checks (6) | v3.106.9 |
| Reduction performance (175x) | v3.106.10 |
| Allen temporal operators | PARKED |

## Transitive reduction is correctness, not tidiness

Harris's Law of Stratigraphic Succession says a matrix shows only IMMEDIATE
relationships. Excavators routinely record A above B, B above C and A above C, and
drawing the third is wrong by the method. Until v3.106.5 every recorded edge was drawn.

The drawn edge is suppressed; the record never is. The page states how many were
suppressed, so a reader can distinguish a correct reduction from lost data.

## The obvious algorithm was a 10.5-second page

`harrisMatrix()` runs on every view of the stratigraphy page with no caching. A graph
search per edge is O(E x (V+E)):

    1000 contexts /  2994 edges     761 ms
    2000 /  7990                   4796 ms
    3000 / 11990                  10516 ms

Fixed by computing reachability once - descendants as bitsets over a reverse topological
order. 60 ms at 3000/11990: a 175x speedup, with output identical on seven correctness
cases run against the shipped method itself, including the diamond case where a naive
reduction wrongly drops an arm.

Realistic chain-like stratigraphy was never the problem (43 ms at 3000 contexts). It is
DENSE records that break it, so benchmark edge density rather than node count. PHASER
cites 500-3000 contexts as its working range; we are inside it now.

## Two wrong assumptions, caught by running the checks

The first consistency run produced 7 findings on BLB-2026 and every one was a false
positive:

- **Cuts do not obey deposit elevation logic.** A cut's top sitting exactly at the
  bottom of the deposit it cuts is correct - a cut extends downward from the surface it
  was cut from, and a fill sits inside it. The check now uses `above` only, with strict
  inequality.
- **Phase numbering direction is a site convention.** Phase 1 is not universally the
  earliest; BLB-2026 numbers them the other way. Asserting either direction gives a
  false positive for every relationship on half the sites in the world. The check now
  infers the convention from the site's own majority and reports only outliers, and
  says nothing when there is no clear majority.

It now reports one finding, which is genuine: two cuts sit in Phase 2 while cutting
Phase 1 deposits, against this site's own convention.

## Formats

**Export** follows Thomas Dye's `hm` table schema, which the Harris Matrix Data Package
builds on: contexts(label, unit-type, position, period, phase, url) and
observations(younger, older, url). observations carries no relation-type column - it
records superposition and nothing else.

Codeberg serves garbage to AI scrapers, so HMDP's optional `inferences` schema could not
be read. Rather than invent conformance, `same_as` correlations are emitted in a
`correlations.csv` named in the descriptor as an AHG extension.

`position` is left empty. hm uses it for surface and basal contexts, and it could be
inferred from the top and bottom tiers, but "surface" is a claim about the ground rather
than about a diagram.

**LST** (BASP Harris, Stratify, ArchEd): first three lines ignored, first unit name on
line four, then blocks of five - name, `above`, `contemporary_with`, `equal_to`, `below`,
comma-separated, all four lines always present.

`contemporary_with` is declined rather than mapped: it means units of the same period
that are not physically joined, and our nearest types both assert physical contact. The
count and the reason appear on screen.

Import is idempotent through `uk_arch_ctxrel`. `insertOrIgnore` returns the affected
count, so "already recorded" is reported separately from "added" - re-running a file
says 22 already recorded rather than claiming 22 additions.

## Allen operators parked on substance

Interval algebra exists to feed Bayesian chronological modelling. There is no
chronological consumer here - no dating, no OxCal, none planned. Thirteen interval
relations would expand the recording UI, the export surface and every check for a
hypothetical, and they change what a relationship IS (intervals with endpoints versus
superposition), so they want designing in rather than retrofitting. Revisit on a real
requirement to feed dates into a model.

## Process note

The output-escaper trap was hit three times in one template - `array_key_exists`,
`!empty`, and an `implode` that killed the page mid-render and took the matrix with it -
despite a memory entry written the same day saying to unwrap once at the top. Guarding
call by call is how the third one gets missed.
