# Technique classification, the researcher export, and the revised RARI plan

**Date:** 18 August 2026
**Releases:** atom-ahg-plugins v3.103.28, v3.103.29
**Instances:** RARI dev (192.168.0.133)

## A researcher reported records missing from the spatial export

Andrew (external, ARADA) found the CSV under-reporting badly: ~800 painted sites in
Lesotho on ARADA against 4 in the file, ~3,700 painted ZA entries against 2,469,
~300 engravings against ~100. He also asked for subject access points as a
delimited column.

Four separate causes, all in the report rather than the data.

### 1. Top-level only, and no top-level record carries a subject tag

The report defaults to `topLevelOnly = true`. RARI has 292,278 descriptions of which
5,703 are top-level, and **zero of those 5,703 carry any subject access point** -
every technique tag sits at item level. The default therefore excluded the entire
population being counted.

His figures line up exactly with the underlying data: Lesotho 833 place-tagged
(his "~800"), South Africa 4,332 (his "~3,700").

### 2. It read one taxonomy, and the smaller one

Technique was read from taxonomy 35 (Subjects) only. Measured:

| Taxonomy | Descriptions tagged paint/engrav |
|---|---|
| **78 Genre** | **34,247** |
| 35 Subjects | 7,216 |
| 42 Places | 48 |

So roughly five sixths of the classification was invisible. In a corrected run
`Is Painted` came back FALSE on all 127,330 rows while 121,505 rows had tags -
because the tags collected were collection names ("RARI Main Slide Collection",
"Natal Museum"), not techniques.

Fixed: `$accessPointTaxonomies = [35, 78]`, settable per instance.
Painted 0 -> **10,464**, engraved 0 -> **2,141**.

### 3. Country emitted site names

`extractCountry()` fell back to the first place term when no country matched, so a
column labelled Country contained "Ha Baroana I", "Game Pass I 7240". Now blank when
there is no country - an empty cell is better than one that looks like an answer.

### 4. Place filters that cannot be satisfied

Covered in the previous session log: the form pre-selected four countries, and on
RARI the coordinate-bearing and country-tagged populations do not overlap at all.

## Term ids instead of substring matching

Technique was matched by `stripos()` against hardcoded English fragments. That query
also catches the PLACE names **Paintshop Shelter I** (48 uses) and **Scrapfield I**
(245) - classifying sites as painted rock art on the strength of what they are
called. Same shape as matching `RiC` inside `ameRICan`.

Now: configured term **names** resolve to term **ids** once per export, scoped to the
access-point taxonomies, matched in full rather than as fragments, and expanded to
child terms. Names not hardcoded ids because ids are per-instance - RARI's Rock
Painting is 111739 and no other installation will agree.

⚠️ **Be honest about the effect: on this export it changed nothing.** 10,464 painted
and 2,141 engraved before and after, because the place-name false positives live in
taxonomy 42 which this report does not read.

**Where it was a real bug is the shipped term lists.** `Khoekhoen`, `Khoi` and
`geometric` were in the **engraved** list - a people and a design description,
neither a technique - and `Khoekhoen` has **267 uses in taxonomy 35**, which the
report does read. Any export whose population included those records would have
classified them engraved on cultural attribution, silently. Also removed: `ochre`
and `pigment`, which match nothing.

Matching on ids makes that class of error impossible rather than merely absent today.

## The structure exists in the raw data; the migration lost it

The strongest finding of the session. The dump's site records carry four separate
controlled fields, with tiny vocabularies:

| Source field | Sites | Distinct | Values |
|---|---|---|---|
| `<technique>` | 7,011 | **3** | Painting 6,262 · Engraving 706 · Painted engraving 43 |
| `<painting>` | 5,820 | 4 | Brush painted 4,713 · Finger painted 1,322 · Handprint 101 · Handprints 4 |
| `<engraving>` | 733 | 3 | Pecking 601 · Scratching 100 · Incision 92 |
| `<tradition>` | 5,544 | 14 | San 4,335 · Pygmy 564 · KhoeKhoen (Khoi) 267 · Bantu 220 · Chewa 105 |

AtoM flattened all four into generic Subject and Genre access points, beside
collection names, institutions and people. That is why the report had to guess.

**It closes the loop on the term-list bug.** `KhoeKhoen (Khoi)` has 267 uses and is a
**tradition** in the source, filed correctly - the same 267 records the substring
matcher was about to call engraved. The distinction was never missing from the data,
only from what survived the import.

## Enrichment plan - additive, because existing data cannot be replaced

Constraint from Johan: data is already imported and new data has been added since, so
nothing may be replaced; enrich instead.

`ahg_site_attribute` already exists and is the right home -
`site_record_id, taxonomy, code, note` - and is empty (7 rows) on both instances.

    taxonomy = 'technique'            code = Painting | Engraving | Painted engraving
    taxonomy = 'painting_technique'   code = Brush painted | Finger painted | Handprint
    taxonomy = 'engraving_technique'  code = Pecking | Scratching | Incision
    taxonomy = 'tradition'            code = San | KhoeKhoen (Khoi) | Bantu | ...

Sequence: seed the four vocabularies into `ahg_dropdown` so they are editable in the
Dropdown Manager; dry run the match (site name -> `ahg_site_record`, the same key the
coordinate import used, where 3,060 of 3,062 resolved to exactly one actor); apply
**insert-only**, matching `(site_record_id, taxonomy, code)` so a re-run is a no-op;
leave every existing access point untouched.

⛔ **Do not merge vocabulary variants without RARI.** `Handprint`/`Handprints` are
probably one term and `KhoeKhoen (Khoi)` should agree with their house spelling, but
they own the vocabulary.

⚠️ **Coverage is the 7,633 sites in the dump, not the catalogue.** Descriptions
catalogued after the original import have no counterpart and keep only their access
points. Any report built on this must say so.

## Deliverable

`/usr/share/nginx/archive/stuff/RARI_spatial_export_2026-08-18.csv` - 17MB, 127,330
rows, coarse `degree_square` cells throughout, `Subject Access Points` as a delimited
column so brush-painted and finger-painted can be separated without relying on the
two booleans.

⚠️ **At 111km cells Lesotho and South Africa are not separable** - Lesotho is ~220km
across and entirely enclosed by ZA, so one cell straddles the border across most of
the country. Andrew's Lesotho-versus-ZA comparison needs the 15-minute cell (~28km),
which is a disclosure decision, not a technical one.

## Plan updated

`docs/RARI_Production_Migration_Plan.md` (v3.103.29) now carries the revised cutover
sequence, the coordinate recovery, the plugin parity findings, and the enrichment
plan above. It also corrects its own statement that "No coordinates exist in RARI's
current data" - 3,062 do.

## Open

- 90 held coordinate rows - RARI decides.
- 877 parallel-name site codes - the original open decision, unchanged.
- Vocabulary normalisation - RARI decides.
- Cell precision for the researcher export - disclosure decision.
- `Atom210@test` is now a public-facing password on archaeology.theahg.co.za.
