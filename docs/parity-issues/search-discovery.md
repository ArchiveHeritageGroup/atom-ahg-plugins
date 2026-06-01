PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** search-discovery — current parity ≈ **65%**.

Search-discovery: 65% parity. PSIS gaps: ResourceSync endpoint (high), search analytics/CTR (high), vector+RRF blending (high), 6 PSIS facets (high), Z3950 server mode (high), PageIndex LLM extraction (high), plus 7 medium/low. Heratio gaps: federated search (high), OAI harvest (high), VIAF/Wikidata (high), plus 5 medium/low. Architectural difference: Heratio is modern Laravel + vector search + analytics; AtoM is mature Symfony 1.4 + federated/peer infrastructure. Both support multi-strategy discovery (keyword/entity/hierarchical/vector/image) but with different fusion (RRF vs weights). Heratio covers cutting-edge discovery, AtoM covers distributed infrastructure.

### High-severity gaps (PSIS missing)
- **ResourceSync 1.1 Source endpoint** — `ahg-resourcesync` → `none`. Heratio ResourceSyncController /.well-known/resourcesync + /resourcesync/*.xml; AtoM has no ResourceSync plugin
- **Search analytics dashboard with CTR tracking** — `ahg-search` → `ahgSearchPlugin`. Heratio SearchAnalyticsService + ahg_search_query_log.click_position + analyticsDashboard; AtoM lacks dashboard
- **Vector search with Qdrant + RRF blending** — `ahg-search` → `ahgSearchPlugin`. Heratio VectorSearchService + BlendedSearchService with RRF formula; AtoM delegates to semantic plugin
- **Faceted search with 6 PSIS-parity facets** — `ahg-search` → `ahgSearchPlugin`. Heratio buildActiveFilters() handles languages/places/subjects/genres/names/collection; SearchService.php lacks facet aggs
- **Z3950 server mode (incoming client queries)** — `ahg-z3950` → `ahgLibraryPlugin`. Heratio Z3950ServerService + Z3950ServerCommand; AtoM Z3950Service is client-only
- **PageIndex LLM hierarchical tree extraction** — `ahg-discovery` → `ahgDiscoveryPlugin`. Heratio PageIndexService + OllamaPageIndexClient + ahg_pageindex_tree table; AtoM PageIndexMigration incomplete

### Medium-severity gaps
- Search suggestion (Did you mean) — `ahg-search` → `ahgSearchPlugin`.
- Advanced search form with filter UI — `ahg-search` → `ahgSearchPlugin`.
- Cursor-based pagination — `ahg-search` → `ahgSearchPlugin`.
- Geospatial search filtering — `ahg-search` → `ahgSearchPlugin`.
- SRU server endpoint controller — `ahg-z3950` → `ahgLibraryPlugin`.
- Discovery simulated query corpus — `ahg-discovery` → `ahgDiscoveryPlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.