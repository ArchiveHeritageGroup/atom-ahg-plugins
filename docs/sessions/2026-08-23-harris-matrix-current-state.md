# Harris Matrix in AtoM - current state

Date: 2026-08-23
Instance: archaeology.theahg.co.za (192.168.0.131)
Plugin: ahgArchaeologyPlugin, shipped in atom-ahg-plugins v3.104.0, current v3.106.4

## What exists and works

Stratigraphy is modelled as a **directed acyclic graph**, deliberately not as part of the
archival hierarchy. AtoM's nested set holds one parent per record; a context can lie
beneath several others at once, and two contexts dug in different trenches can be the
same feature. So relationships live in `archaeology_context_relationship`, while each
context remains a full `information_object` - catalogued, searchable, in the tree.

Nine relation types with reciprocals (`ArchaeologyService::REL_TYPES`):
`above`/`below`, `cuts`/`cut_by`, `fills`/`filled_by`, and the symmetric `same_as`,
`bonds_with`, `abuts`. Each carries a direction (`later`/`earlier`/`none`).

The matrix is **derived, never stored**: `harrisMatrix()` computes layering from the
relationships on each draw, using union-find to merge `same_as` groups and Kahn
longest-path layering for tiers. Anything reading those same edges therefore stays
consistent with the drawn matrix by construction.

Live surfaces: stratigraphy + matrix (`/archaeology/site/:id/contexts`), context sheets
with relationships and finds, dig plan with map and a section builder sliced by elevation
and filtered by trench and context type, finds register, spatial view, CSV import, PDF
context sheets.

## Demo data

`BLB-2026` - Blaauwbosch Farm, 2026 excavation. 20 contexts, 22 stratigraphic
relationships, 17 nodes over 7 tiers after `same_as` merging, 6 finds, 11 digital objects.
Site record completed 2026-08-23: Settlement / Late Iron Age / Gauteng. The coordinate is
the AHG office, used as a stand-in so mapping can be seen working - it is not a real site.

## Deliverable

A 5:22 narrated walkthrough exists, recorded by `/opt/parity/e2e/harris-narrated.cjs`:
three intro slides (what a Harris Matrix is, why a hierarchy cannot hold stratigraphy, how
AtoM keeps both) then fourteen screens, closing on the matrix full frame. Narrated in
Johan's cloned voice at speed 0.55.

## Open: graph browsing

Logged on **atom-extensions-catalog#255** (knowledge-graph explorer). Summary:

- `ahgRicExplorerPlugin` already has two layers - `KnowledgeGraphService` (pure MySQL,
  rendered with Cytoscape) and `getDataAction` (Fuseki/SPARQL). **A graph explorer needs
  no triplestore**; Fuseki is only for the RiC-O layer.
- Stratigraphy is the strongest case for it, because the DAG is exactly what the hierarchy
  cannot express. The nodes already exist; only the edges are missing.
- Three options: relational edges only (no vocabulary problem - `cuts` stays `cuts`);
  RiC-O mapping (blocked - RiC-O has no `cuts`, `fills` or `abuts`, and flattening them
  into generic relations discards the direction that makes a matrix a matrix); or both.
- Undecided: direction as a first-class edge property, whether finds are in scope, and
  `KnowledgeGraphService::CAP = 60` against dense stratigraphy.
- On archaeology today: RiC plugin not enabled, no triplestore on .131 (Fuseki answers
  only on 112). Any cross-plugin read needs `AhgDb::hasOptionalTable` per #302.

**Decision pending - nothing built.**

## Gotchas recorded elsewhere

Templates: unwrap Symfony's output escaper once before `array_map`, and do not escape an
already-escaped value (both bit this plugin on 2026-08-23). Grepping for `ric` matches
`p-ric-ing` and `met-ric-.
