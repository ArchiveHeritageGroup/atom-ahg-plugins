# #150 — Unified G/L/A/M knowledge graph (graph surface) — 2026-06-15

**Issue:** #150 (twin of Heratio #1197). **Plugin:** ahgRicExplorerPlugin. **Status:** Option-A graph surface built + verified live, unreleased. Issue stays OPEN (partial).

## Delivered (Option A — the unified graph surface)
- `lib/Services/KnowledgeGraphService.php` (global, Capsule): `build(ioId)` assembles a cross-domain graph centred on a record — holding repository (`information_object.repository_id`), creators/agents (`event`→`actor`, edge labelled by `event.type_id` term), subjects/places/genres (`object_term_relation`→`term`), related records (`relation`, either direction, IO-resolved), donor (`provenance_record.donor_id`). Capped at 60/kind. `listEntities()` for the picker.
- `modules/ricExplorer/actions/knowledgeGraphAction.class.php` (mirrors provenanceGraphAction; auth-gated; id→graph, none→picker).
- `modules/ricExplorer/templates/knowledgeGraphSuccess.php` — Cytoscape view (vendored ricExplorer cytoscape.min.js, CSP-nonce'd JSON-data-script + inline init), cose layout, node types coloured record/repository/actor/term/related/donor; picker table when no id.
- Routes `ric_knowledge_graph` + `_id` in `config/routing.yml` (`/ricExplorer/knowledge-graph[/:id]`).

## Verified
- All `php -l` clean; routes 200 (auth gate, no 500); cytoscape vendored same-origin.
- CLI build on record #768 → 9 nodes / 8 edges (1 record + 1 repository "held by" + 7 creators "Creation"); labels resolved via term lookup. Cross-entity traversal confirmed.

## Deferred (the rest of #150 — keep issue open)
- **CIDOC-CRM mapping** — map IO/actor/term/event to CRM classes/properties (E22/E21/E55/E7…). [buildable, metadata layer]
- **KM join** — link graph entities to km.theahg.co.za knowledge base. [needs KM API]
- **Fuseki/RiC unification** — the existing SPARQL RiC graph stays separate; merging surfaces is a larger task.
