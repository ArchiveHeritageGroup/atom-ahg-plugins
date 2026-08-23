# Harris Matrix: open-source survey, gaps found, and the export-target decision

Date: 2026-08-23
Issues raised: atom-extensions-catalog#306 (stratigraphy gaps), #307 (mobile field cataloguing)
Related: #255 (knowledge-graph explorer), #257 (archaeological cataloguing model)

## Correction: who the Harris Matrix is named after

KM currently answers that it is "named after **Robert Harris**, a British archaeologist who
developed this method in the **1950s**". **That is wrong on both counts** and appears to be
model invention filling a gap, since none of the uploaded documents named the person.

The Harris Matrix is named after **Edward C. Harris**, who devised it in **1973** while
excavating in Winchester, and set it out in *Principles of Archaeological Stratigraphy*
(1979). Harris is Bermudian. Anyone querying KM on this should treat the earlier answer as
a hallucination.

## Gaps found in ahgArchaeologyPlugin (v3.106.4)

Surveyed against ArkMatrix, PHASER/MATRIX, stratigraphr, tsdye/harris-matrix, PolyChron,
Le Stratifiant, Strati5 and the Harris Matrix Data Package.

1. **No transitive reduction.** Every recorded later-than edge is drawn. Harris's method
   requires only IMMEDIATE relationships - excavators routinely record A>B, B>C and A>C,
   and drawing all three is cluttered and wrong by the Law of Stratigraphic Succession.
   This is a correctness issue, not cosmetics, and is masked by BLB-2026 being small.
   Highest-value fix on the list.
2. **No export of any kind.** `mermaidSource()` is internal rendering only.
3. **No relationship import.** `CSV_CONTEXT_FIELDS` covers contexts only, so an existing
   dig archive can bring its contexts but not its stratigraphy.
4. **No Allen temporal operators.** The MATRIX project uses Allen interval algebra because
   Harris's Before/After/Equals cannot express what Bayesian chronological modelling needs.
5. **Phasing is a stored label, not an analysis.** PHASER derives and tests phase
   assignments; Le Stratifiant checks chronostratigraphic consistency beyond cycles.
6. **No chronological modelling** (OxCal/Bayesian) - probably a deliberate boundary, but
   worth naming as one.
7. **Scale untested.** PHASER cites 500-3000 contexts as its working range; BLB-2026 has 20.

## Export target: the data package beats RiC-O

**[harris-matrix-data-package](https://codeberg.org/steko/harris-matrix-data-package)** is a
Frictionless Data Package specification for stratigraphy following the Harris convention -
the community's own interchange format. It is a **better export target than a RiC-O
mapping** (#255 option B) because it needs no invented predicates for `cuts`/`fills`/`abuts`
and is far more likely to be consumed by an actual archaeologist.

Secondary targets: **GraphViz DOT** (trivial from our edge set) and **PHASER CSV**
(`siteCode`, `sourceID`, `stratRelationship`, `targetID`).

**Legacy import path: LST**, the format used by BASP Harris, Stratify and ArchEd, converted
by `harris2graph`. That is what any pre-existing dig archive will be in.

## Prior art worth reading

- **ArkMatrix** (gitlab.com/arklab/ArkMatrix) - Harris matrix inside ARK, the closest
  structural analogue: a matrix embedded in a recording system rather than standalone.
- **PHASER** (github.com/cbinding/phaser) - editing and analysis, from a CIDOC-CRM
  background; accepts a four-column CSV.
- **semerj/harris-matrix** - d3 + klayjs layered rendering, nearest to our SVG.

⚠️ open-archaeo lists **no licence** for any of these - check each repo before reusing code.
⚠️ **Harris Matrix Composer is NOT open source** despite appearing in such searches; it is a
commercial product from Traxler and Neubauer.

## Mobile field cataloguing (#307)

Separately raised: photograph an artifact in situ, attach it to its find/context/site,
capture phone GPS, upload. Reusable today: `archaeology_object` already has `find_date`,
`finder`, `find_location`, `recovery_method_id`; `archaeology_site` has real coordinate and
`spatial_accuracy_m` columns; `ahgMetadataExtractionPlugin` already reads EXIF GPS; the
ingest pipeline already does virus scan, format ID, checksums and derivatives.

⚠️ **Bug found on the way:** `MetadataExtractionHandler::setGpsCoordinates()` writes the
coordinate as a NOTE STRING (`"GPS Coordinates: %s, %s"`), not into structured fields - so
every EXIF-bearing upload today produces a position that is prose rather than queryable
data. Worth fixing independently of the mobile work.

Design constraints: offline capture is a requirement (digs have no signal, and there is no
PWA or service worker anywhere in the plugins today); record the accuracy the phone reports
rather than implying survey precision; and the context attachment is the whole point,
because a photo not tied to a context is nearly worthless.
