# AtoM AHG Extensions

**Transform Access to Memory into a Complete GLAM Solution**

> ### ➜ [Download the plugins](docs/plugins/README.md)
>
> Nine plugins for AtoM 2.9 and 2.10, each installable on its own, with what
> they do and what they look like. That page carries the current version of
> each; the [releases page](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases)
> holds the full history.

## What is AtoM?

[Access to Memory (AtoM)](https://www.accesstomemory.org) is a powerful, open-source archival management system trusted by institutions worldwide. AtoM provides:

### Core AtoM Capabilities

| Feature | Description |
|---------|-------------|
| **Archival Description** | Full ISAD(G), RAD, DACS standards support |
| **Authority Records** | ISAAR(CPF) compliant entity management |
| **Hierarchical Arrangement** | Unlimited fonds/series/file/item levels |
| **Multi-Repository** | Host multiple archives in one instance |
| **Multilingual** | 20+ languages with i18n support |
| **Digital Objects** | Upload and link files to descriptions |
| **Finding Aids** | Generate EAD, Dublin Core exports |
| **Search & Browse** | Elasticsearch-powered discovery |
| **Access Control** | User groups and permissions |
| **Accessions** | Track incoming materials |
| **Deaccessions** | Document disposals |
| **Physical Storage** | Location tracking |
| **Import/Export** | CSV, EAD, EAC-CPF, SKOS |
| **OAI-PMH Harvesting** | Share metadata with aggregators |
| **Theming** | Customizable appearance |
| **API Access** | REST API for integrations |

**AtoM is excellent archival software.** It handles the core archival workflow beautifully.

---

## What AtoM Extensions Adds

AtoM Extensions transforms AtoM into a **complete GLAM solution** (Galleries, Libraries, Archives, Museums) with modern architecture, international compliance, and enterprise features.

### Feature Comparison

| Capability | Base AtoM | + AtoM Extensions |
|------------|:---------:|:-----------------:|
| Core Archival Functions | ✅ Full | ✅ Enhanced |
| Modern Bootstrap 5 UI | ❌ | ✅ |
| Laravel Query Builder Integration | ❌ | ✅ |
| **Security & Compliance** | | |
| Security Classification System | ❌ | ✅ |
| GDPR Compliance (EU) | ❌ | ✅ |
| POPIA Compliance (South Africa) | ❌ | ✅ |
| CCPA Compliance (California) | ❌ | ✅ |
| PIPEDA Compliance (Canada) | ❌ | ✅ |
| Comprehensive Audit Trail | ❌ | ✅ |
| **GLAM Sector Support** | | |
| Archives | ✅ | ✅ Enhanced |
| Libraries | Partial | ✅ Full |
| Museums (Collections Procedures) | ❌ | ✅ |
| Galleries (CCO) | ❌ | ✅ |
| Digital Asset Management | ❌ | ✅ |
| **Heritage & Finance** | | |
| GRAP 103 Heritage Accounting | ❌ | ✅ |
| Asset Valuation & Depreciation | ❌ | ✅ |
| Insurance Management | ❌ | ✅ |
| **Research & Access** | | |
| Research Portal | ❌ | ✅ |
| Reading Room Booking | ❌ | ✅ |
| Access Request Workflow | ❌ | ✅ |
| Embargo Management | ❌ | ✅ |
| **Collection Management** | | |
| Donor Agreement Tracking | ❌ | ✅ |
| Condition Assessment | ❌ | ✅ |
| Conservation Tracking | ❌ | ✅ |
| Provenance Research | ❌ | ✅ |
| Vendor/Supplier Management | ❌ | ✅ |
| **Advanced Features** | | |
| Landing Page Builder | ❌ | ✅ |
| Display Profile System | ❌ | ✅ |
| IIIF Image Viewer | ❌ | ✅ |
| Records in Contexts (RiC) | ❌ | ✅ |
| AI Entity Extraction | ❌ | ✅ |
| Automated Backups | ❌ | ✅ |

### Why Both Together?

**AtoM** = Rock-solid archival foundation trusted by national archives, universities, and cultural institutions.

**AtoM Extensions** = Modern enhancements for institutions needing:
- Multi-sector GLAM support
- International regulatory compliance
- Enterprise security features
- Advanced collection management
- Public engagement tools

---

## Plugin catalogue

Generated from each plugin's `extension.json`, so it cannot drift from what is
actually in the repository. Every name links to the plugin directory, which holds
its own README with a standalone install recipe.

Install top to bottom: nothing in a section depends on anything below it.

### Prerequisites

| Plugin | Tables | Description |
|---|---|---|
| `ahgRuntimePlugin` | 3 | AHG runtime - shared services and base classes required by AHG plugins. **Generated from atom-framework, not cloned** - it is not in this repository. Build it before anything else. |
| [`ahgCorePlugin`](ahgCorePlugin/) | 5 | Core utilities and shared services for AHG plugins. Provides centralized database access, configuration, taxonomy resolution, file storage, and cross-plugin contracts. Every other plugin below depends on it. |

### Depends on ahgCorePlugin only

| Plugin | Tables | Requires | Description |
|---|---|---|---|
| [`ahg3DModelPlugin`](ahg3DModelPlugin/) | 11 | [`ahgCorePlugin`](ahgCorePlugin/) | 3D model viewing with Google Model Viewer, Gaussian Splat support, AR, hotspots, and IIIF 3D manifests |
| [`ahgAIPlugin`](ahgAIPlugin/) | 27 | [`ahgCorePlugin`](ahgCorePlugin/) | AI-powered tools for archival management: Named Entity Recognition (NER), Translation, Summarization, Spellcheck, and LLM Description Suggestions |
| [`ahgAccessionManagePlugin`](ahgAccessionManagePlugin/) | 15 | [`ahgCorePlugin`](ahgCorePlugin/) | First-class accession management with intake queue, appraisal workflow, container tracking, rights inheritance, and multi-tenant isolation |
| [`ahgActorManagePlugin`](ahgActorManagePlugin/) | 1 | [`ahgCorePlugin`](ahgCorePlugin/) | High-performance actor browse and autocomplete using Laravel Query Builder and direct ES queries. Replaces base AtoM actor browse that causes N+1 query hangs |
| [`ahgAiCompliancePlugin`](ahgAiCompliancePlugin/) | 6 | [`ahgCorePlugin`](ahgCorePlugin/) | EU AI Act Article 12 record-keeping (PSIS port of ahg/ai-compliance). Tamper-evident receipt chain over every AI inference call using the ahg/inference-receipts library (SHA-256 chain + RFC 8785 JCS + Ed25519) |
| [`ahgAnnotationsPlugin`](ahgAnnotationsPlugin/) | 1 | [`ahgCorePlugin`](ahgCorePlugin/) | Standalone W3C Web Annotation Data Model + Protocol backend (#146, parity with Heratio ahg-annotations) |
| [`ahgArchaeologyPlugin`](ahgArchaeologyPlugin/) | 4 | [`ahgCorePlugin`](ahgCorePlugin/) | Archaeological site, find and stratigraphic context recording - single-context recording with Harris Matrix, for excavation archives |
| [`ahgArtworkRequestPlugin`](ahgArtworkRequestPlugin/) | 4 | [`ahgCorePlugin`](ahgCorePlugin/) | Staff requests to place artworks in offices and shared spaces. Captures the request, checks availability, notifies the responsible staff and records the decision - the conversation itself stays with people |
| [`ahgAuditTrailPlugin`](ahgAuditTrailPlugin/) | 6 | [`ahgCorePlugin`](ahgCorePlugin/) | Comprehensive audit trail logging for AtoM with POPIA/NARSSA compliance. Provides AhgAuditService for centralized audit logging |
| [`ahgBackupPlugin`](ahgBackupPlugin/) | 3 | [`ahgCorePlugin`](ahgCorePlugin/) | Database and file backup with scheduling, restore, upload and retention management |
| [`ahgC2paPlugin`](ahgC2paPlugin/) | 1 | [`ahgCorePlugin`](ahgCorePlugin/) | C2PA (Coalition for Content Provenance and Authenticity) 2.1 content credentials. Generate, Ed25519-sign and embed signed provenance manifests on digital-object derivatives (JUMBF via c2patool, or .c2pa.json sidecars), surface embedded EXIF/IPTC/XMP as C2PA Standard Metadata Assertions, declare AI training-mining stance, and verify manifests. PSIS port of Heratio's ahg/c2pa, sharing the ahg/inference-receipts Ed25519 key chain with ahgAiCompliancePlugin |
| [`ahgCDPAPlugin`](ahgCDPAPlugin/) | 9 | [`ahgCorePlugin`](ahgCorePlugin/) | Zimbabwe Cyber and Data Protection Act [Chapter 12:07] compliance - DPO registration, breach management, DPIA, consent tracking, data subject requests, and POTRAZ reporting |
| [`ahgCartPlugin`](ahgCartPlugin/) | 8 | [`ahgCorePlugin`](ahgCorePlugin/) | Shopping cart with dual-mode support: Standard (Request to Publish) and E-Commerce with PayFast payment integration |
| [`ahgConditionPlugin`](ahgConditionPlugin/) | 16 | [`ahgCorePlugin`](ahgCorePlugin/) | Condition assessment and reporting for museum objects |
| [`ahgContactPlugin`](ahgContactPlugin/) | 1 | [`ahgCorePlugin`](ahgCorePlugin/) | Contact information management for actors and repositories |
| [`ahgCustomFieldsPlugin`](ahgCustomFieldsPlugin/) | 2 | [`ahgCorePlugin`](ahgCorePlugin/) | Admin-configurable custom metadata fields for any entity type. Define fields via UI - no code changes needed |
| [`ahgDAMPlugin`](ahgDAMPlugin/) | 12 | [`ahgCorePlugin`](ahgCorePlugin/) | Digital Asset Management with IPTC metadata, watermarks, derivatives, and Creative Commons licensing |
| [`ahgDacsManagePlugin`](ahgDacsManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | DACS (Describing Archives: A Content Standard) edit form for information objects |
| [`ahgDcManagePlugin`](ahgDcManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Dublin Core descriptive standard edit form for information objects |
| [`ahgDedupePlugin`](ahgDedupePlugin/) | 5 | [`ahgCorePlugin`](ahgCorePlugin/) | Duplicate detection and merging for archival records - configurable rules, scan jobs, compare view, merge workflow, and reports |
| [`ahgDiscoveryPlugin`](ahgDiscoveryPlugin/) | 4 | [`ahgCorePlugin`](ahgCorePlugin/) | Topic discovery and related content - natural language search with NER, synonym expansion, hierarchical context, and PageIndex LLM-driven retrieval over EAD, PDF, and RiC-O records |
| [`ahgDisplayPlugin`](ahgDisplayPlugin/) | 14 | [`ahgCorePlugin`](ahgCorePlugin/) | GLAM browser and display modes for archival content. Includes DisplayRegistry for extension-based action/panel/badge registration |
| [`ahgDoiPlugin`](ahgDoiPlugin/) | 5 | [`ahgCorePlugin`](ahgCorePlugin/) | DOI integration via DataCite - mint, deactivate, sync, queue processing, badge display, and verification |
| [`ahgDonorAgreementPlugin`](ahgDonorAgreementPlugin/) | 11 | [`ahgCorePlugin`](ahgCorePlugin/) | Donor Agreement Management with contract uploads, reminders, and rights tracking |
| [`ahgDonorManagePlugin`](ahgDonorManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Donor browse and management using Laravel Query Builder |
| [`ahgEmailDeliveryPlugin`](ahgEmailDeliveryPlugin/) | 1 | [`ahgCorePlugin`](ahgCorePlugin/) | Email deliverability: bounce capture + suppression list + send-time gate (#145, parity with Heratio EmailBounceController/EmailSuppressionGate) |
| [`ahgExportPlugin`](ahgExportPlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Archival export functionality for CSV, EAD, and other formats |
| [`ahgFavoritesPlugin`](ahgFavoritesPlugin/) | 3 | [`ahgCorePlugin`](ahgCorePlugin/) | Full-featured favorites/bookmarks management with folders, notes, bulk operations, and AJAX toggle |
| [`ahgFeedbackPlugin`](ahgFeedbackPlugin/) | 2 | [`ahgCorePlugin`](ahgCorePlugin/) | User feedback and suggestions management for archival records |
| [`ahgFormsPlugin`](ahgFormsPlugin/) | 6 | [`ahgCorePlugin`](ahgCorePlugin/) | Configurable metadata entry forms per repository - drag-drop form builder, template library, field assignments, draft saving, and import/export |
| [`ahgFunctionManagePlugin`](ahgFunctionManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | ISDF function browse, view, edit, and delete management using Laravel Query Builder |
| [`ahgFunctionsDocsPlugin`](ahgFunctionsDocsPlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Auto-generated, browsable catalogue of routes, CLI tasks and services (#148, parity with Heratio ahg-functions-docs) |
| [`ahgGISPlugin`](ahgGISPlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Geospatial search and GeoJSON export for heritage records with coordinates |
| [`ahgGalleryPlugin`](ahgGalleryPlugin/) | 12 | [`ahgCorePlugin`](ahgCorePlugin/) | Gallery and exhibition management with artist tracking, loans, insurance, and facility reports |
| [`ahgGraphQLPlugin`](ahgGraphQLPlugin/) | 1 | [`ahgAPIPlugin`](ahgAPIPlugin/) | GraphQL API endpoint providing flexible querying with security safeguards including depth limiting and complexity analysis |
| [`ahgHelpPlugin`](ahgHelpPlugin/) | 2 | [`ahgCorePlugin`](ahgCorePlugin/) | Online help system with searchable documentation, contextual help, and FlexSearch-powered instant search |
| [`ahgHeritageAccountingPlugin`](ahgHeritageAccountingPlugin/) | 11 | [`ahgCorePlugin`](ahgCorePlugin/) | Multi-regional heritage asset financial accounting with support for IPSAS, GRAP, FRS, GASB, AASB, PSAS standards |
| [`ahgICIPPlugin`](ahgICIPPlugin/) | 13 | [`ahgCorePlugin`](ahgCorePlugin/) | Indigenous Cultural and Intellectual Property management - community registration, TK Labels, cultural notices, consent tracking, consultations, and access restrictions |
| [`ahgIPSASPlugin`](ahgIPSASPlugin/) | 10 | [`ahgCorePlugin`](ahgCorePlugin/) | International Public Sector Accounting Standards (IPSAS 45) heritage asset accounting - asset register, valuations, impairments, insurance, depreciation, disposals, and financial year reporting |
| [`ahgIiifPlugin`](ahgIiifPlugin/) | 25 | [`ahgCorePlugin`](ahgCorePlugin/) | IIIF viewer integration with Mirador and OpenSeadragon support |
| [`ahgImageArPlugin`](ahgImageArPlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Place a flat 2D archival image into augmented reality (WebXR hit-test). #147, parity with Heratio ahg-image-ar |
| [`ahgInformationObjectManagePlugin`](ahgInformationObjectManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | ISAD(G) information object CRUD management - create, edit, delete, digital object upload, treeview navigation via Laravel Query Builder |
| [`ahgJobsManagePlugin`](ahgJobsManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Background jobs browse, delete, and report management using Laravel Query Builder |
| [`ahgLabelPlugin`](ahgLabelPlugin/) | 1 | [`ahgCorePlugin`](ahgCorePlugin/) | Label generation for archival objects with customizable templates |
| [`ahgLandingPagePlugin`](ahgLandingPagePlugin/) | 8 | [`ahgCorePlugin`](ahgCorePlugin/) | Visual landing page builder with drag-and-drop blocks |
| [`ahgLibraryPlugin`](ahgLibraryPlugin/) | 44 | [`ahgCorePlugin`](ahgCorePlugin/) | Library cataloging with MARC-inspired fields, ISBN lookup, and bibliographic management |
| [`ahgLoanPlugin`](ahgLoanPlugin/) | 19 | [`ahgCorePlugin`](ahgCorePlugin/) | Shared loan management for GLAM institutions - Museums, Galleries, Archives, Libraries, and Digital Assets. Based on Collections Procedures and international GLAM standards |
| [`ahgMarketplacePlugin`](ahgMarketplacePlugin/) | 16 | [`ahgCorePlugin`](ahgCorePlugin/) | Online art/gallery marketplace with multi-GLAM sector support. Fixed pricing, make-an-offer negotiations, and timed auctions. Multi-currency, seller verification, commission tracking, and payout management |
| [`ahgMenuManagePlugin`](ahgMenuManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Menu configuration management - list, edit, and delete AtoM navigation menus via Laravel Query Builder |
| [`ahgMetadataExtractionPlugin`](ahgMetadataExtractionPlugin/) | 2 | [`ahgCorePlugin`](ahgCorePlugin/) | Universal metadata extraction from digital objects using ExifTool, with automatic field mapping and preservation metadata support |
| [`ahgModsManagePlugin`](ahgModsManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | MODS (Metadata Object Description Schema) descriptive standard edit form for information objects |
| [`ahgMultiTenantPlugin`](ahgMultiTenantPlugin/) | 3 | [`ahgCorePlugin`](ahgCorePlugin/) | Multi-tenancy with domain routing, dedicated tenant tables, and user hierarchy |
| [`ahgNAZPlugin`](ahgNAZPlugin/) | 10 | [`ahgCorePlugin`](ahgCorePlugin/) | Zimbabwe National Archives Act [Chapter 25:06] compliance - 25-year closure rule, researcher permits, records schedules, transfers, and protected records management |
| [`ahgNMMZPlugin`](ahgNMMZPlugin/) | 9 | [`ahgCorePlugin`](ahgCorePlugin/) | Zimbabwe National Museums and Monuments Act [Chapter 25:11] compliance - monument register, antiquities, export permits, archaeological sites, heritage impact assessments |
| [`ahgOcflPlugin`](ahgOcflPlugin/) | 1 | [`ahgCorePlugin`](ahgCorePlugin/) | OCFL v1.1 (Oxford Common File Layout) preservation storage: storage-root management, content-addressed object versioning with deterministic inventory.json + SHA-512 digests, per-version directories, fixity verification and tar export |
| [`ahgPortableExportPlugin`](ahgPortableExportPlugin/) | 3 | [`ahgCorePlugin`](ahgCorePlugin/) | Full-system portable export and import for AtoM Heratio. Export complete instances with 15 entity types, SHA-256 checksummed manifests, and self-documenting packages. Import archives into fresh instances with ID remapping, merge/replace/dry-run modes. Also supports offline HTML viewer export for CD/USB/ZIP distribution |
| [`ahgPreservationPlugin`](ahgPreservationPlugin/) | 30 | [`ahgCorePlugin`](ahgCorePlugin/) | Digital preservation: checksums, fixity verification, PREMIS events, format registry |
| [`ahgPrivacyPlugin`](ahgPrivacyPlugin/) | 34 | [`ahgCorePlugin`](ahgCorePlugin/) | POPIA/GDPR Privacy Compliance Management - DSAR tracking, Breach register, ROPA, Consent management |
| [`ahgProvenancePlugin`](ahgProvenancePlugin/) | 9 | [`ahgCorePlugin`](ahgCorePlugin/) | Chain of custody and provenance tracking for archival records, museum objects, and library materials, plus AI inference provenance recording and Ed25519 manifest signing |
| [`ahgRadManagePlugin`](ahgRadManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | RAD (Rules for Archival Description) descriptive standard edit form for information objects |
| [`ahgRecordsManagePlugin`](ahgRecordsManagePlugin/) | 2 | [`ahgCorePlugin`](ahgCorePlugin/) | Records-management file plan / classification scheme - a nested-set tree of classification nodes (function/series/file) with retention and disposal-action metadata. PSIS-parity port of the Heratio ahg-records-manage FilePlanService (#118). Email capture and full disposal/retention execution are follow-ups |
| [`ahgRegistryPlugin`](ahgRegistryPlugin/) | 36 | [`ahgCorePlugin`](ahgCorePlugin/) | AtoM/Heratio Community Hub & Registry - Directory of institutions, vendors, software, user groups, discussions, blog, and sync API for the GLAM community |
| [`ahgReportBuilderPlugin`](ahgReportBuilderPlugin/) | 15 | [`ahgCorePlugin`](ahgCorePlugin/) | Enterprise report builder with rich text editing (Quill.js), Word/PDF/XLSX/CSV export, drag-drop sections, templates, collaboration workflows, SQL queries, sharing, and scheduling |
| [`ahgReportsPlugin`](ahgReportsPlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Central reporting dashboard with accession, authority, repository, storage and taxonomy reports |
| [`ahgRepositoryManagePlugin`](ahgRepositoryManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | High-performance archival institution browse using Laravel Query Builder and direct ES queries |
| [`ahgRequestToPublishPlugin`](ahgRequestToPublishPlugin/) | 4 | [`ahgCorePlugin`](ahgCorePlugin/) | Manage publication requests for archival images and digital objects |
| [`ahgResearcherPlugin`](ahgResearcherPlugin/) | 4 | [`ahgCorePlugin`](ahgCorePlugin/) | Researcher collection upload and approval workflow - online submissions and offline exchange import with two-step archivist review |
| [`ahgRicExplorerPlugin`](ahgRicExplorerPlugin/) | 6 | [`ahgCorePlugin`](ahgCorePlugin/) | Records in Context (RiC) visualization, exploration, and Fuseki triplestore integration |
| [`ahgRicManagePlugin`](ahgRicManagePlugin/) | 1 | [`ahgCorePlugin`](ahgCorePlugin/) | Adds Records in Context (RiC-O) as a selectable descriptive standard. A record captured as RiC keeps its RiC-O entity type, record-centric RiC properties, and typed RiC relations, all sourced from MySQL. Record-centric now, extensible to further RiC entity types later |
| [`ahgRightsHolderManagePlugin`](ahgRightsHolderManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Rights holder browse and management using Laravel Query Builder |
| [`ahgRightsPlugin`](ahgRightsPlugin/) | 24 | [`ahgCorePlugin`](ahgCorePlugin/) | Core rights management including PREMIS rights, Creative Commons, rights holders, and orphan works tracking |
| [`ahgSAHRAPlugin`](ahgSAHRAPlugin/) | 7 | [`ahgCorePlugin`](ahgCorePlugin/) | South African Heritage Resources Agency (SAHRA) permit workflow under the National Heritage Resources Act, 1999 (Act 25 of 1999). Researcher applies, supervising professor endorses, then the application is submitted to SAHRA and the outcome recorded. Covers s.35 archaeology/palaeontology/meteorites, s.32 heritage object export, s.34 structures and s.36 burial grounds & graves |
| [`ahgSecurityClearancePlugin`](ahgSecurityClearancePlugin/) | 21 | [`ahgCorePlugin`](ahgCorePlugin/) | Security classification, user clearance, embargo, watermarking and extended rights management |
| [`ahgSemanticSearchPlugin`](ahgSemanticSearchPlugin/) | 8 | [`ahgCorePlugin`](ahgCorePlugin/) | Semantic search and thesaurus management with WordNet/Datamuse API sync, Wikidata SPARQL integration, and vector embeddings via Ollama |
| [`ahgSettingsPlugin`](ahgSettingsPlugin/) | 5 | [`ahgCorePlugin`](ahgCorePlugin/) | Extended settings management for AtoM with sections, metadata templates, and configuration tools |
| [`ahgSiteRecordPlugin`](ahgSiteRecordPlugin/) | 3 | [`ahgCorePlugin`](ahgCorePlugin/) | Field recording for archaeological, heritage and rock art sites - site records attached to authority records, with role-gated locality |
| [`ahgSpectrumPlugin`](ahgSpectrumPlugin/) | 33 | [`ahgCorePlugin`](ahgCorePlugin/) | Museum collections procedures - acquisition, loans, movement, conservation, valuation, and workflow management |
| [`ahgStaticPagePlugin`](ahgStaticPagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | Static page management - list, edit, and delete AtoM static pages via Laravel Query Builder |
| [`ahgStatisticsPlugin`](ahgStatisticsPlugin/) | 5 | [`ahgCorePlugin`](ahgCorePlugin/) | Usage statistics tracking with page views, downloads, GeoIP lookup, bot filtering, and reporting dashboards |
| [`ahgStorageManagePlugin`](ahgStorageManagePlugin/) | 3 | [`ahgCorePlugin`](ahgCorePlugin/) | Physical storage browse and management using Laravel Query Builder |
| [`ahgTermTaxonomyPlugin`](ahgTermTaxonomyPlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | High-performance term and taxonomy browse (subjects, places, genres) using Laravel Query Builder and direct ES queries |
| [`ahgTiffPdfMergePlugin`](ahgTiffPdfMergePlugin/) | 3 | [`ahgCorePlugin`](ahgCorePlugin/) | TIFF and PDF merge job management for digital preservation |
| [`ahgTimeLimitedShareLinkPlugin`](ahgTimeLimitedShareLinkPlugin/) | 2 | [`ahgCorePlugin`](ahgCorePlugin/) | Time-limited, auditable share links for information_object records. Anonymous bearer-token access with HMAC-derived URL-safe tokens, optional max-access count, expiry caps, classified-record gating, and admin revocation. Integrates with ahgAuditTrailPlugin and ahgSecurityClearancePlugin |
| [`ahgUiOverridesPlugin`](ahgUiOverridesPlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | UI action overrides for AtoM modules - centralized location for action customizations |
| [`ahgUserManagePlugin`](ahgUserManagePlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/) | User browse and management using Laravel Query Builder |
| [`ahgUserRegistrationPlugin`](ahgUserRegistrationPlugin/) | 1 | [`ahgCorePlugin`](ahgCorePlugin/) | Public user self-registration with email verification and admin approval workflow |
| [`ahgVendorPlugin`](ahgVendorPlugin/) | 14 | [`ahgCorePlugin`](ahgCorePlugin/) | Vendor and supplier management with transactions, services, and contact tracking |
| [`ahgVersionControlPlugin`](ahgVersionControlPlugin/) | 2 | [`ahgCorePlugin`](ahgCorePlugin/) | Version history with diff and restore for information_object and actor. Mirrors the AHG version-snapshot pattern used by reports, landing pages and heritage contributions. Integrates with ahgAuditTrailPlugin and ahgSecurityClearancePlugin |

### Tier 2 - depends on a plugin in the tier above

| Plugin | Tables | Requires | Description |
|---|---|---|---|
| [`ahgAccessRequestPlugin`](ahgAccessRequestPlugin/) | 7 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgSecurityClearancePlugin`](ahgSecurityClearancePlugin/) | Researcher access request management for restricted materials |
| [`ahgAiConditionPlugin`](ahgAiConditionPlugin/) | 6 | [`ahgConditionPlugin`](ahgConditionPlugin/), [`ahgCorePlugin`](ahgCorePlugin/) | AI-powered condition assessment using YOLOv8 damage detection and EfficientNet classification. Companion to ahgConditionPlugin |
| [`ahgAuthorityPlugin`](ahgAuthorityPlugin/) | 7 | [`ahgActorManagePlugin`](ahgActorManagePlugin/), [`ahgCorePlugin`](ahgCorePlugin/) | Comprehensive authority record enhancements: external linking (Wikidata, VIAF, ULAN, LCNAF), completeness dashboard, NER-to-authority pipeline, relationship graph, merge/split workflow, bulk deduplication, structured occupations, ISDF functions, EAC-CPF export enrichment, and contact panel surfacing |
| [`ahgAuthorityResolutionPlugin`](ahgAuthorityResolutionPlugin/) | 7 | [`ahgAIPlugin`](ahgAIPlugin/), [`ahgActorManagePlugin`](ahgActorManagePlugin/), [`ahgCorePlugin`](ahgCorePlugin/) | Evidence-based authority resolution for persons, places, and organisations. Replaces name-only matching with an archivist-driven workflow that surfaces neighbourhood-context evidence, ranked candidates, and provenance-tracked decisions. Provenance writes to Fuseki as RDF-Star |
| [`ahgExtendedRightsPlugin`](ahgExtendedRightsPlugin/) | 14 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgRightsPlugin`](ahgRightsPlugin/) | Extended rights management with RightsStatements.org integration, embargo management, Traditional Knowledge labels, and batch rights assignment |
| [`ahgFtpPlugin`](ahgFtpPlugin/) | 0 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgSettingsPlugin`](ahgSettingsPlugin/) | Browser-based FTP/SFTP upload for CSV import digital objects. Provides drag-and-drop upload interface under Import > FTP Upload so users can place files on the server without external FTP client software. Prominently shows the path to use in CSV digitalObjectPath column |
| [`ahgIngestPlugin`](ahgIngestPlugin/) | 7 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgSecurityClearancePlugin`](ahgSecurityClearancePlugin/) | OAIS-aligned multi-stage ingestion pipeline: configure, upload, map, validate, preview, commit with rollback support |
| [`ahgIntegrityPlugin`](ahgIntegrityPlugin/) | 12 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgPreservationPlugin`](ahgPreservationPlugin/) | Enterprise-grade automated integrity assurance: scheduled fixity verification, scoped validation, concurrency controls, append-only ledger, dead-letter queue, retention policies, legal holds, disposition review, threshold alerting |
| [`ahgMiradorPlugin`](ahgMiradorPlugin/) | 0 | [`ahgIiifPlugin`](ahgIiifPlugin/) | Contributes the Mirador IIIF image viewer as an independently installable plugin. Registers a renderer with ahgIiifPlugin's RendererRegistry; install or disable it to choose the viewer |
| [`ahgMuseumPlugin`](ahgMuseumPlugin/) | 36 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgSpectrumPlugin`](ahgSpectrumPlugin/) | Museum cataloging with CCO (Cataloging Cultural Objects), CIDOC-CRM, and Collections Procedures integration |
| [`ahgSeadragonPlugin`](ahgSeadragonPlugin/) | 0 | [`ahgIiifPlugin`](ahgIiifPlugin/) | Contributes the Seadragon IIIF image viewer as an independently installable plugin. Registers a renderer with ahgIiifPlugin's RendererRegistry; install or disable it to choose the viewer |
| [`ahgThemeB5Plugin`](ahgThemeB5Plugin/) | 0 | [`ahgContactPlugin`](ahgContactPlugin/), [`ahgCorePlugin`](ahgCorePlugin/), [`ahgMetadataExtractionPlugin`](ahgMetadataExtractionPlugin/), [`ahgUiOverridesPlugin`](ahgUiOverridesPlugin/) | Modern Bootstrap 5 theme for Access to Memory |
| [`ahgWorkflowPlugin`](ahgWorkflowPlugin/) | 10 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgSecurityClearancePlugin`](ahgSecurityClearancePlugin/) | Configurable approval workflow system for archival submissions with role-based task routing, pool claiming, and email notifications |

### Tier 3 - depends on a plugin in the tier above

| Plugin | Tables | Requires | Description |
|---|---|---|---|
| [`ahgHeritagePlugin`](ahgHeritagePlugin/) | 45 | [`ahgThemeB5Plugin`](ahgThemeB5Plugin/) | Heritage discovery platform with contributor system, custodian management, and analytics |
| [`ahgNARSSAPlugin`](ahgNARSSAPlugin/) | 2 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgExtendedRightsPlugin`](ahgExtendedRightsPlugin/) | South African National Archives and Records Service Act 1996 compliance - generates NARSSA-compliant transfer manifests (METS-EAD2002 XML + digital files + manifest.csv) for records reaching disposal-due via the ahgExtendedRightsPlugin disposal workflow |
| [`ahgResearchPlugin`](ahgResearchPlugin/) | 145 | [`ahgAccessRequestPlugin`](ahgAccessRequestPlugin/), [`ahgCorePlugin`](ahgCorePlugin/) | Research knowledge platform with evidence graphs, source criticism, cross-collection synthesis, W3C annotations, AI extraction orchestration, validation queues, snapshots, assertions, hypotheses, ORCID integration, collaboration, bibliography, reproduction requests, workspaces, journal, reports, notifications, RO-Crate packaging, ODRL rights, reproducibility packs, DOI minting, and REST API access |
| [`ahgScanPlugin`](ahgScanPlugin/) | 2 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgIngestPlugin`](ahgIngestPlugin/) | Watched-folder streaming ingest: configurable watched folders, a scan/watch CLI that detects new files and feeds the ingest pipeline, processed/failed disposition dirs, and dedupe by SHA-256 checksum |
| [`ahgSharePointPlugin`](ahgSharePointPlugin/) | 7 | [`ahgAuditTrailPlugin`](ahgAuditTrailPlugin/), [`ahgCorePlugin`](ahgCorePlugin/), [`ahgIngestPlugin`](ahgIngestPlugin/), [`ahgSettingsPlugin`](ahgSettingsPlugin/) | Microsoft 365 SharePoint integration: tenant config, drive registration, manual delta sync (Phase 1); webhook records handoff (Phase 2); federated search + M365 connector feed (Phase 3). One-way: SharePoint -> AtoM ingest |

### Tier 4 - depends on a plugin in the tier above

| Plugin | Tables | Requires | Description |
|---|---|---|---|
| [`ahgRdmPlugin`](ahgRdmPlugin/) | 4 | [`ahgCorePlugin`](ahgCorePlugin/), [`ahgInformationObjectManagePlugin`](ahgInformationObjectManagePlugin/), [`ahgIngestPlugin`](ahgIngestPlugin/), [`ahgResearchPlugin`](ahgResearchPlugin/) | Sovereign (POPIA-resident) research-data-management module: dataset deposit, AI-assisted human-gated POPIA sensitivity scan, access/embargo + DOI, compliance dashboard. Reverse port of Heratio ahg-rdm (heratio#1337) |

### Independent - no AHG dependency

These need only AtoM, the framework and PHP. They install in any order and are
listed last because nothing else waits on them.

| Plugin | Tables | Description |
|---|---|---|
| [`ahgAPIPlugin`](ahgAPIPlugin/) | 7 | Enhanced REST API v2 with API key management, full CRUD operations, batch processing, webhooks with HMAC signatures and retry logic, rate limiting, and GLAM/DAM sector support |
| [`ahgAccessibilityPlugin`](ahgAccessibilityPlugin/) | 1 | WCAG accessibility tooling for archival descriptions - human-authored image alternative text (WCAG 1.1.1), authoring UI, coverage dashboard and a consumer API |
| [`ahgDataMigrationPlugin`](ahgDataMigrationPlugin/) | 5 | Migrate records from external systems (ArchivesSpace, Vernon CMS, Preservica OPEX/PAX) with field mapping, Gearman background jobs, sector-specific CSV export, rights and provenance import |
| [`ahgExhibitionPlugin`](ahgExhibitionPlugin/) | 15 | Exhibition management for GLAM/DAM sectors - standalone plugin for managing exhibitions, objects, storylines, and events |
| [`ahgFederationPlugin`](ahgFederationPlugin/) | 10 | OAI-PMH Federation plugin for Heritage Platform metadata exchange |
| [`ahgMetadataExportPlugin`](ahgMetadataExportPlugin/) | 2 | Unified export framework supporting 10 metadata standards across GLAM sectors (Galleries, Libraries, Archives, Museums) and digital preservation |
| [`ahgObservabilityPlugin`](ahgObservabilityPlugin/) | 0 | Prometheus-format /metrics exporter for AtoM. Exposes app, HTTP request, DB query and queue-depth metrics through a lightweight, auth-gated (bearer token OR IP allow-list) endpoint. Auto-selecting APCu/Redis/in-memory counter storage with graceful fallback. CLI commands sample queue depth and emit node_exporter textfiles |
| [`ahgRedactionPlugin`](ahgRedactionPlugin/) | 2 | Manual visual redaction of digital objects: draw regions over a PDF or image, save them, and generate a redacted copy. The original file is never modified |
| [`ahgResourceSyncPlugin`](ahgResourceSyncPlugin/) | 1 | ResourceSync 1.1 (NISO Z39.99-2017) Source endpoints: Source Description, Capability List, Resource List and Change List as sitemap-formatted XML. Mirrors the OAI-PMH publication filter so aggregators see the same record + tombstone set across both federation surfaces |
| [`ahgSearchPlugin`](ahgSearchPlugin/) | 6 | Global search, autocomplete, description updates, and search/replace for AtoM Heratio |
| [`ahgTranslationPlugin`](ahgTranslationPlugin/) | 3 | On-prem translation integration for AtoM via local MT endpoint (supports Afrikaans/Dutch and SA languages -> English depending on MT service) |

Totals: 120 plugins - 1 generated prerequisite, 1 core, 107 dependent, 11 independent.

---

## Installation

Verified on 18 August 2026 by installing from a **bare Ubuntu 24.04 server** to a working
site, unattended. Every command below ran on that machine and produced the result stated.
Nothing here is written from intention.

### Base AtoM is not modified

No file under `apps/`, `lib/`, `vendor/` or `config/` is touched, and
`config/ProjectConfiguration.class.php` stays exactly as upstream ships it. The plugins
load through AtoM's own `plugins` setting - the same list Admin > Plugins writes.

### Prerequisites

- AtoM 2.10 installed per the [official instructions](https://www.accesstomemory.org/en/docs/2.10/admin-manual/installation/ubuntu/)
- MySQL 8.0+, Elasticsearch 7.10, Composer 2.x
- **PHP 8.3 from Ubuntu's own repositories.** Do not add the `ondrej/php` PPA: it offers
  8.4 and 8.5 and makes one of them the default. AtoM 2.10 does not run on 8.5.
- **`php8.3-gd`** - required by the framework and **absent from AtoM's documented package
  list**. Without it `composer install` fails with *"the requested PHP extension gd is
  missing"* and the install stops dead. This is the single most common cause of a failed
  AHG install.

  ```bash
  apt install php8.3-gd
  ```

- `php8.3-imagick` is **optional**. It is needed for digital-object derivatives, not for
  installation - the verified install ran without it.

The plugins carry no Composer dependencies of their own; PHP libraries live in
`atom-framework/composer.json`. JavaScript and CSS libraries are vendored and committed,
so there is no npm install at deploy time and nothing is fetched from a CDN at runtime.

### Install the framework

```bash
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

cd atom-framework
composer install --no-dev --optimize-autoloader
bin/build-runtime-plugin
cd ..
```

`ahgRuntimePlugin` is **generated** by `bin/build-runtime-plugin`, not cloned. It is not in
the plugins repository and a `git pull` will never produce it.

If composer falls back to `Cloning ... from cache` because GitHub is rate limiting (HTTP
429), the result carries a `.git` directory per package and is roughly ten times the size.
Either re-run later, or strip them: `find vendor -type d -name .git -prune -exec rm -rf {} +`

### Install a plugin

```bash
ln -sfn /usr/share/nginx/atom/atom-ahg-plugins/<Plugin> plugins/<Plugin>
chown -h www-data:www-data plugins/<Plugin>

php atom-framework/bin/install-plugin-schema.php --plugin=<Plugin> \
    --database=<db> --user=<user> --password=<pw>
```

Read the line it prints. `verified all N declared tables exist` is success. A run that
applies nothing exits 2 rather than claiming success, a plugin whose declared dependency
is not installed is **refused** with the dependency named (`--force` overrides), and a
plugin that legitimately owns no tables says `no schema - nothing to install`.

On a server whose MySQL has no TCP listener, add
`--socket=/var/run/mysqld/mysqld.sock`.

**Never run `mysql < install.sql`.** The client stops at the first error and abandons every
statement after it, leaving a half-built schema and saying nothing.

### Dependencies and install order

Every plugin declares what it needs in its `extension.json`, and the installer
**refuses** to install a plugin whose dependency is absent or only partly installed,
naming what to install first. `--force` overrides, deliberately.

Install in the order of the [plugin catalogue](#plugin-catalogue) above: prerequisites,
then each tier, then the independent plugins. Nothing in a section depends on anything
below it, so top to bottom is always a safe order.

Three things that catch people out:

`ahgRuntimePlugin` is **not in this repository**. It is generated from atom-framework,
so cloning this repo will not produce it. Build it before anything else.

A plugin with 0 tables owns no schema of its own - `ahgUiOverridesPlugin` is templates
and helpers, `ahgThemeB5Plugin` is a theme. The installer reports `no schema - nothing
to install` for those, which is success, not a failure.

`ahgThemeB5Plugin` depends on `ahgMetadataExtractionPlugin`, which needs ExifTool at
runtime to do anything useful - worth knowing before planning a minimal install. And
`ahgHeritagePlugin` is the one functional plugin that depends on a **theme** rather than
the other way round, so installing it pulls in the theme and everything the theme needs.

### Enable

In **Admin > Plugins**, in dependency order: `ahgRuntimePlugin`, then `ahgCorePlugin`, then
the rest. Afterwards:

```bash
php symfony cc
systemctl reload php8.3-fpm
```

The theme is enabled under **Admin > Themes**, not Plugins. AtoM deliberately excludes any
plugin whose `$summary` contains the word "theme" from the plugin list, so
`ahgThemeB5Plugin` will never appear there.

Every plugin configuration class must declare both `$summary` and `$version`. AtoM's plugin
admin renders `$plugin::$version`, and in PHP 8 reading an undeclared static property is a
fatal error - it kills that page part way down, taking the save button with it, with a 200
response and nothing in any log.

### Theme assets must be built after the theme is symlinked

`ahgThemeB5Plugin` has no prebuilt CSS in the repository. Its stylesheet is compiled by
AtoM's own webpack from the plugin's `webpack.entry.js` and `scss/`, and
`_layout_start.php` then looks for the result with:

```php
glob($distPath.'/css/ahgThemeB5Plugin.bundle.*.css');
```

AtoM's documented install runs `npm run build` **before** any AHG plugin exists, so webpack
never sees the theme and the bundle is never produced. The site then renders with no theme
styling at all and nothing reports an error - the glob simply finds nothing.

Symlink the theme first, then build:

```bash
cd /usr/share/nginx/atom
ln -sfn /usr/share/nginx/atom/atom-ahg-plugins/ahgThemeB5Plugin plugins/ahgThemeB5Plugin
npm run build
chown -R www-data:www-data dist
php symfony cc && systemctl reload php8.3-fpm
```

Confirm with `ls dist/css/ | grep ahgTheme` - you want a
`ahgThemeB5Plugin.bundle.<hash>.css`. Re-run `npm run build` after any theme change or
after adding a plugin that ships its own webpack entry.

### Post-install

> **AHG symfony tasks do not exist on a stock AtoM.** Stock `ProjectConfiguration` enables a
> hardcoded plugin list and never reads the `plugins` setting, so symfony discovers no
> plugin tasks. `php symfony display:auto-detect` and `php symfony ahg:refresh-facet-cache`
> produce no output at all. Use `php atom-framework/bin/atom` for plugin management
> (`discover`, `install`, `enable`, `disable`, `update`, `migrate`).

Base AtoM's own tasks work normally and are worth running after enabling plugins:

```bash
sudo -u www-data php symfony propel:build-nested-set
sudo -u www-data php symfony propel:generate-slugs
sudo -u www-data php symfony search:populate
sudo systemctl restart php8.3-fpm nginx
```

### If the AtoM root is under /usr/share/nginx

Some php-fpm packaging sets `ProtectSystem=full`, which mounts `/usr` read-only for the
worker; the site then cannot write its cache or logs and every page returns 500 with an
empty body. Stock Ubuntu 24.04 ships `ProtectSystem=no` and is unaffected. Where it does
apply, grant the paths and **prefix each with `-`**:

```ini
# /etc/systemd/system/php8.3-fpm.service.d/atom-storage.conf
[Service]
ReadWritePaths=-/usr/share/nginx/atom/log
ReadWritePaths=-/usr/share/nginx/atom/cache
ReadWritePaths=-/usr/share/nginx/atom/uploads
```

Without the `-`, a path that does not exist yet makes systemd refuse to start php-fpm at
all (`226/NAMESPACE`) and the web server stays down.

### Verified standalone set

Twenty plugins install onto an empty database, each with exactly one `install.sql` and a
manifest that matches it: ahgRuntime, ahgCore, ahgContact, ahgSecurityClearance, ahgDisplay,
ahgSettings, ahgUiOverrides, ahgAuditTrail, ahgBackup, ahgThemeB5, ahgProvenance,
ahgCondition, ahgCart, ahgRequestToPublish, ahgResearch, ahgUserRegistration,
ahgAccessRequest, ahgMetadataExtraction, ahgSiteRecord, ahgHeritage.

Proven by dropping all 253 tables and reinstalling from zero, then running the end-to-end
suites: 74 checks passed, 4 failed, every failure traced to a plugin deliberately outside
this set.

Plugins outside it may still carry schema that the installer does not create. They are not
yet certified for standalone installation.

> **Note:** `ahgMultiTenantPlugin` is disabled by default and should stay off unless you
> configure tenant to domain mappings - it routes requests by hostname and returns
> "Tenant Not Found" for any host it does not recognise.

---

## Version Compatibility

| Version | AtoM | PHP |
|---------|------|-----|
| 2.x | 2.10+ | 8.3 |
| 1.x | 2.8-2.9 | 7.4+ |

---

## Support

- **Documentation**: [User Guides](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog)
- **Issues**: [GitHub Issues](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/issues)
- **Email**: support@theahg.co.za
- **Website**: [theahg.co.za](https://theahg.co.za)

---

## License

GPL-3.0 - See [LICENSE](LICENSE) file.

## Author

**The Archive and Heritage Group (Pty) Ltd**

Empowering cultural heritage institutions with modern archival solutions.

© 2024-2026 All rights reserved.
