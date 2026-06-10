# 2026-06-10 — Provenance Graph (issue #149 strand 3)

## Summary
Built a relational **Provenance Graph** into `ahgRicExplorerPlugin` — the third and heaviest implementable strand of tracking issue **#149**, completing the trio with the AI Cataloguer and the Researcher Copilot. Live + service-verified on PSIS; not yet released.

## Why relational (not Fuseki)
The existing RiC explorer graph is Fuseki/SPARQL-backed and needs the triplestore populated. This new graph reads the `ahgProvenancePlugin` relational tables directly (`provenance_record` / `provenance_event` / `provenance_agent`), so it always works — and it surfaces authenticity / due-diligence signals the SPARQL view doesn't.

## What it does
At `/ricExplorer/provenance` you get a picker of the records that have provenance; opening one renders an interactive **chain-of-custody graph** (agents, transfer events with direction + date, donor) using the bundled cytoscape, alongside a "Custody & acquisition" panel and an **"Authenticity & due diligence"** panel — custody gaps, Nazi-era provenance checked/clear, cultural-property status, completeness.

## Components (all in ahgRicExplorerPlugin)
- `lib/Services/ProvenanceGraphService.php` — `build($ioId)` (cytoscape `{nodes, edges, summary}` from the provenance tables) + `listRecords()`. Graceful empty state for records without provenance.
- Route `/ricExplorer/provenance[/:id]`; single-action class `ricExplorerProvenanceGraphAction`. Graph JSON embedded server-side (no extra AJAX endpoint).
- `modules/ricExplorer/templates/provenanceGraphSuccess.php` + `web/js/provenance-graph.js` (bundled cytoscape; breadthfirst directed layout).
- CLI task `ric:install-provenance-menu` adds a "Provenance graph" nav link under Manage (nested-set, idempotent) — run on PSIS. No new tables.

## Verification
Service-level (debug=false CLI): `build(902316)` → 5 nodes (record + 4 agents) + 5 edges (4 transfers + dashed current-custody); the summary correctly flagged a problematic provenance (custody gaps, Nazi-era checked-but-not-clear, cultural-property claimed). `listRecords()` = 11. A record without provenance returns a single node + empty state. Route resolves; nav link live; site healthy.

Caveat: the **authenticated cytoscape render** was not visually confirmed (no test password; the action is auth-gated because provenance data is internal). JS lint-clean and uses the same bundled cytoscape as the existing explorer — recommend a click-through before release.

## Status
Built + service-verified on PSIS. Pending: authenticated UI click-through, release (`./bin/release minor`), `php symfony ric:install-provenance-menu` per instance. **All three #149 implementable strands are now built** (AI Cataloguer, Researcher Copilot, Provenance Graph).
