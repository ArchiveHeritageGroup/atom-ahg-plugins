# Heratio Framework — Product Roadmap

**The Archive and Heritage Group (Pty) Ltd**
**Framework:** v2.10.24 | **Plugins:** v3.9.30 (88 plugins) | **Updated:** February 2026

> Heratio is a modernization framework for [Access to Memory (AtoM)](https://www.accesstomemory.org/) 2.10 that extends it into a full GLAM (Galleries, Libraries, Archives, Museums) management platform. Built on Laravel Query Builder integrated with AtoM's Symfony 1.4 core, it delivers enterprise capabilities through a non-invasive plugin ecosystem while maintaining full backward compatibility.

---

## Roadmap Legend

| Label | Meaning |
|-------|---------|
| **Completed** | Production-ready and deployed |
| **In Progress** | Under active development |
| **Planned** | Committed with estimated timeline |
| **Future** | On the radar, not yet scheduled |

---

## Completed

| Capability | Plugin(s) | Notes |
|-----------|-----------|-------|
| **AI Metadata Extraction** | ahgAIPlugin v2.1.0 | NER (spaCy), translation (Argos), summarization, spellcheck, LLM suggestions |
| **AI-Powered Discovery Search** | ahgDiscoveryPlugin | 4-strategy pipeline: ES keyword + NER entity + Qdrant vector + hierarchical. Unified with GLAM browse |
| **AI Condition Assessment** | ahgAiConditionPlugin | YOLOv8 damage detection + EfficientNet classification with archivist review workflow |
| **OAIS Data Ingest** | ahgIngestPlugin | 6-step wizard with SIP/AIP/DIP packaging, 9 AI processing options, background jobs |
| **Landing Page Builder** | ahgLandingPagePlugin | Drag-and-drop block editor with versioning |
| **Enterprise Reporting** | ahgReportBuilderPlugin v2.0 | Rich text editor, Word/PDF/Excel export, 54 data sources, scheduling, collaboration workflow |
| **Heritage Accounting** | ahgHeritageAccountingPlugin v2.0 + ahgIPSASPlugin | Multi-regional: GRAP 103, IPSAS 45, FRS 102, GASB 34, FASB 958, AASB 116, PSAS, IAS 16 |
| **Digital Preservation** | ahgPreservationPlugin | Checksums, fixity, PREMIS events, format registry, PRONOM sync, Siegfried integration |
| **Portable Offline Viewer** | ahgPortableExportPlugin v1.1 | Standalone HTML/JS viewer for CD/USB/ZIP with FlexSearch and hierarchical navigation |
| **Privacy Compliance** | ahgPrivacyPlugin | 7 jurisdictions: POPIA, GDPR, UK GDPR, CCPA, PIPEDA, NDPA, DPA |
| **GraphQL API** | ahgGraphQLPlugin | Full schema with depth/complexity limits, cursor pagination, API key auth |
| **Federation** | ahgFederationPlugin | OAI-PMH harvesting + provider, peer management, provenance tracking |
| **DOI Integration** | ahgDoiPlugin | DataCite minting, queue processing, verification |
| **Records in Contexts** | ahgRicExplorerPlugin | RiC ontology, Fuseki triplestore, SPARQL endpoint |
| **IIIF + 3D** | ahgIiifPlugin + ahg3DModelPlugin | Cantaloupe image server, manifests, Google Model Viewer, AR |
| **Text-to-Speech** | ahgCorePlugin | Browser-native Web Speech API, sector-aware field reading, accessibility |
| **Indigenous Cultural IP** | ahgICIPPlugin | Community registration, TK Labels, consent tracking, cultural notices |
| **Marketplace** | ahgMarketplacePlugin | Fixed-price, auction, multi-currency, seller verification, commission tracking |
| **4 GLAM Sectors** | ahgLibraryPlugin, ahgMuseumPlugin, ahgGalleryPlugin, ahgDAMPlugin | Full sector-specific cataloguing and workflows |
| **Zimbabwe Compliance** | ahgCDPAPlugin, ahgNAZPlugin, ahgNMMZPlugin | CDPA, National Archives Act, National Museums & Monuments Act |

---

## In Progress

| Capability | Status | Target |
|-----------|--------|--------|
| **Voice Command Interface** | TTS complete; speech recognition (ASR) in development | Q1 2026 |
| **Intelligent Cataloguing** | LLM suggestions working; image similarity + HTR planned | Q2 2026 |
| **Linked Data** | RiC/SPARQL working; Wikidata/VIAF/Getty linking in progress | Q2 2026 |
| **Multi-Tenant Architecture** | Plugin exists (v1.2), currently disabled; domain routing + tenant isolation built | Q2 2026 |

---

## Planned — H2 2026

| Capability | Description | Target |
|-----------|-------------|--------|
| **Enterprise Authentication** | LDAP/AD, SAML 2.0, OIDC, MFA — dual-mode (SSO + local fallback) | Q3 2026 |
| **REST API v2** | Full CRUD for all entities, API keys, rate limiting, webhooks, OAI-PMH | Q3 2026 |
| **Handwritten Text Recognition** | HTR for historical vital records (births, deaths, marriages) | Q3 2026 |
| **Image Similarity Search** | Visual similarity matching across digital objects | Q3 2026 |
| **GIS & Spatial Heritage** | Heritage site mapping, geospatial search, national register integration | Q4 2026 |
| **SaaS Deployment** | Managed hosting model with Docker containerization | Q4 2026 |
| **Mobile Field App** | Companion app for field data collection | Q4 2026 |
| **Extended Standards** | CIDOC-CRM export, public SPARQL, BIBFRAME, PBCore | Q4 2026 |

---

## Future — 2027+

| Capability | Description |
|-----------|-------------|
| **Multilingual NER** | Custom models for Afrikaans, isiZulu, Sesotho |
| **Platform Evolution** | Incremental Symfony 1.4 to Laravel migration |
| **Collaboration Tools** | Internal discussion threads and annotation workflows attached to records |

---

## Competitive Context

| Platform | What We're Watching |
|----------|-------------------|
| **ArchivesSpace** | Five-year strategic roadmap (2026-2030), Wikidata plugin, Lyrasis interoperability |
| **CollectiveAccess** | AI automated cataloguing (v2.2), ElasticSearch reimplementation, UI redesign (v3.0) |
| **Arches Project** | Arches Lingo vocabulary management, SPARQL endpoints, GIS capabilities |
| **Archivematica** | Extension architecture, UI simplification, AtoM contributor gatherings |
| **Omeka S** | SaaS model, hierarchy module for archival collections |
| **AtoM Foundation** | AtoM 3 design principles (still in planning — Heratio fills the gap now) |

---

## Community Gaps Addressed

Of [17 identified community gaps](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/170) in the AtoM ecosystem, Heratio addresses all 17 — 15 are complete or have working implementations, and 2 are in active development. Notable gaps that AtoM has never roadmapped include: IIIF integration, multi-tenancy, museum/Spectrum support, gallery/CCO, privacy compliance, heritage accounting, condition assessment, and donor management.

---

## Contributing

We welcome feedback, feature requests, and contributions. Open an issue to start a discussion or submit a pull request.

**Contact:** [The Archive and Heritage Group](https://theahg.co.za)
**License:** GPL-3.0
