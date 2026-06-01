Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** search-discovery

### Features to add to Heratio (present in PSIS/AtoM)
- **[high]** Federated search across multiple peers — _PSIS plugin: ahgFederationPlugin_: AtoM FederatedSearchService + federation module for peer discovery/queries; Heratio has no peer capability
- **[high]** OAI harvesting client (inbound) — _PSIS plugin: ahgFederationPlugin_: AtoM HarvestService + HarvestClient; Heratio ahg-oai is provider-only
- **[high]** VIAF/Wikidata actor linking — _PSIS plugin: ahgSemanticSearchPlugin_: AtoM ViafLinkingService + WikidataActorLinkingService; Heratio lacks authority linking
- **[high]** MARC21 decoding for Z3950 records — _PSIS plugin: ahgLibraryPlugin_: AtoM Marc21DecoderService converts to library_biblio_*; Heratio integration unclear
- **[medium]** WordNet sync via Datamuse — _PSIS plugin: ahgSemanticSearchPlugin_: AtoM WordNetSyncService with rate limiting; Heratio doesn't expose
- **[medium]** Wikidata SPARQL sync — _PSIS plugin: ahgSemanticSearchPlugin_: AtoM WikidataSyncService; Heratio lacks
- **[low]** Query expansion testing UI — _PSIS plugin: ahgSemanticSearchPlugin_: AtoM testExpand action for debugging; Heratio has no public test endpoint
- **[low]** Search template management — _PSIS plugin: ahgSemanticSearchPlugin_: AtoM adminTemplates/adminTemplateEdit for reusable templates; Heratio lacks

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.