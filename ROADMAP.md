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

# Future: Public Portal (Archives-led) + Hard Multi-Tenancy + Governance/Hardening

Tracking issue: **#198**

## Target outcome
Deliver a converged GLAM public portal (archives-led) that is materially stronger than vendor suites in:
- Discovery relevance + explainability
- Rights enforcement consistency (view/download/export)
- Hard multi-tenant isolation (uploads/index/cache/audit)
- Production maturity (jobs, observability, DR)

---

## EPIC A — Public Portal VNext (Archives-led)

### A1. Tenant-scoped branding + routing
- Tenant themes (logo/colors/header/footer) per domain or URL prefix
- Tenant-safe routing with enforced tenant context in all controllers/middleware

### A2. Search/browse that operators trust
- Facets: level of description, dates, places, creators, subjects, digitised availability
- Hierarchy-aware ranking (fonds/series context boosts)
- Explainable ranking panel (“why you got this result”)

### A3. Record view improvements
- Strong hierarchy breadcrumb + parent/children context blocks
- Clear restrictions banner (rights/embargo/POPIA)
- Provenance display where applicable: source, digitisation notes, checksum/fixity metadata

### A4. Digital object delivery
- Derivative policy engine (thumb/public access copies vs restricted masters)
- AV streaming (optional future)
- Transcript search (optional future; governed by rights policy)

### A5. Public requests
- Reproduction/access request workflow
- Requester dashboard (status tracking)
- Automated rights gate before submission + escalation path

Acceptance:
- Every public render/download/request is tenant-scoped end-to-end
- Rights enforcement is consistent across all access paths

---

## EPIC B — Description UX (Staff; quality multiplier for public)

### B1. Template-driven description forms
- Per-level templates (fonds/series/file/item) + required fields + validation rules
- Conditional fields by record type

### B2. Bulk operations with safety
- Bulk edit with diff preview
- Rollback snapshot (transactional or event-sourced)
- Async apply via job queue with progress + resumability

### B3. Authority workbench (dedupe/merge governance)
- Fuzzy duplicate detection + deterministic rules
- Merge with rollback + provenance
- Canonical sources layering: local reference lists first, external (Wikidata/GeoNames) only when missing, cached with provenance

Acceptance:
- Authority merges are auditable and reversible
- Bulk edits are queued, resumable, and leave a complete audit trail

---

## EPIC C — Hard Multi-Tenancy (Isolation Model)

Hard isolation boundaries (non-negotiable):
1) Upload isolation: `/uploads/<tenant>/...` + per-tenant temp dirs; no shared namespaces
2) Search isolation: per-tenant index prefix + aliases; strict scoping in queries; per-tenant reindex tooling
3) Cache isolation: cache keys include `tenant_id`; per-tenant cache dirs; per-tenant clear-cache tooling
4) Audit isolation: every event stamped with `tenant_id`, `user_id`, `request_id`; exportable audit packs per tenant

Acceptance:
- Automated leakage tests for cross-tenant files/index/cache
- Tenant deprovision removes uploads/indexes/caches cleanly
- Tenants can be backed up/restored independently

---

## EPIC D — Security/Hardening Standards (Cross-cutting)

### D1. Upload hardening standard
- Allowlisted upload types; extension + MIME validation (`finfo`), size limits
- Quarantine + optional scanning hook
- Rate limiting for upload endpoints
- Store outside webroot or enforce nginx non-exec rules for uploads

### D2. Outbound HTTP policy (SSRF controls)
- Allowlist destinations; block RFC1918/link-local by default
- Strict timeouts; no redirects for configurable URLs
- Log outbound requests (tenant_id + request_id)

### D3. Shell execution policy
- Avoid shell where possible
- If needed: `escapeshellarg()` + strict allowlists + `realpath` containment
- Never secrets in argv; use secure defaults files

### D4. Serialization policy
- Prefer JSON
- If `unserialize()` is used: `allowed_classes=false` + schema validation

Acceptance:
- Security model documented + enforced via PR checklist
- Regression tests for critical policies (uploads/tenancy/rights)

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
**License:** AGPL-3.0
