Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** core-ui

### Features to add to Heratio (present in PSIS/AtoM)
- **[medium]** Per-repository theme customization via editThemeAction — _PSIS plugin: ahgUiOverridesPlugin_: AtoM provides per-repository override. Heratio theming is global-only.
- **[medium]** Elasticsearch integration service for search optimization — _PSIS plugin: ahgCorePlugin_: AtoM ahgCorePlugin includes ElasticsearchService.php. Heratio uses pure DB search.
- **[low]** Event sourcing services (EventService, RelationService, TermRelationService) — _PSIS plugin: ahgCorePlugin_: AtoM ahgCorePlugin has data graph services. Heratio ahg-core lacks equivalent.

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.