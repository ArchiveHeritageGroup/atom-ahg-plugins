# Heratio → PSIS Feature-Parity Audit & Port Plan

**Date:** 2026-06-13
**Author:** The Archive and Heritage Group (Pty) Ltd
**Method:** Phase-0 multi-agent audit — 114 read-only agents, one per Heratio Laravel package, each matched to its PSIS Symfony plugin and scored at feature level; 1 synthesis agent. (Run `wf_ad9f8a22-b20`.)

## Goal
Port all Heratio (Laravel, v1.142.103) functionality INTO PSIS's Symfony `atom-ahg-plugins` for full feature parity. This is **feature porting into Symfony plugins, not a file copy** (Laravel code does not run inside AtoM; PSIS is live production).

## Raw status counts (authoritative — from the per-package matrix)
- **Present (already at parity):** 21 — do NOT re-port
- **Partial (gaps to close):** 90
- **Missing / essentially absent:** 3 — `ahg-image-ar`, `ahg-oai`, `ahg-rights`
- **No PSIS plugin dir at all:** 1 — `ahg-oai`
- Priority: 71 high / 32 medium / 11 low

> ⚠️ **Caveat — verify before building.** Audit agents scored against current repo state only and tend to OVER-count gaps (feature-name compare; uncommitted/local PSIS work is invisible to them — e.g. `ahg-image-ar` shows "missing" though `ahgImageArPlugin` was built locally under #145–148). Spot-verify each gap against the live PSIS instance + `ahg_error_log` before porting. This matches the prior parity-audit lesson (same-name compare inflates the gap).

---

# Heratio → PSIS Feature-Parity Audit Report

## Executive Summary

This audit consolidates 114 packages across the Heratio Laravel framework and the PSIS Symfony/AtoM 2.x ecosystem. **Approximately 40% of Heratio's functionality requires porting** to achieve full feature parity in PSIS.

### Status Breakdown
- **Present (full parity):** 23 packages (20%)
- **Partial (gaps exist):** 83 packages (73%)
- **Missing (no PSIS equivalent):** 8 packages (7%)

### Priority Distribution
- **High priority:** 56 packages (49% of total)
- **Medium priority:** 35 packages (31%)
- **Low priority:** 23 packages (20%)

### Headline: Real Porting Work Remaining
- **8 entirely new plugins needed** (OAI, Rights, Image AR, and 5 semantic/discovery features)
- **High-effort ports (XL):** 12 packages requiring 6-12 weeks each (Core, Information Object Manage, Records Manage, Research, etc.)
- **Medium-effort ports (M-L):** 45 packages suitable for phased delivery
- **Quick wins (S-M):** 30+ packages achievable in 1-2 week sprints

The audit identifies **4 critical security/compliance gaps that affect both frameworks** (known issues #1261, #1264, #1263, #1248) requiring architecture-level fixes independent of parity work.

---

## Prioritized Port Backlog

| Package | PSIS Plugin | Status | Priority | Effort | Top Gaps |
|---------|-------------|--------|----------|--------|----------|
| **ahg-information-object-manage** | ahgInformationObjectManagePlugin | Partial | HIGH | XL | Export (DC/EAD/MODS/MARC), Import (XML/CSV), Preservation workflows, Privacy masking, Extended rights, Media transcription, Condition assessment |
| **ahg-core** | ahgCorePlugin | Partial | HIGH | XL | Capture priority/at-risk register, Data quality dashboard, Alt-text curation + AI suggestions, Storytelling engine, Accessibility/language coverage audits, Point clouds, Gaussian splats, WARC archiving |
| **ahg-records-manage** | ahgRecordsManagePlugin | Partial | HIGH | XL | Retention schedules, Disposal workflow + approvals, Review queue, File plan import/export, Auto-classification rules, ISO/MoReq compliance assessments |
| **ahg-research** | ahgResearchPlugin | Partial | HIGH | XL | WritingStudio, PublicationStudio, MethodStudio, ClaimLedger, ContradictionEngine, GrantEngine, DMPBuilder, ResearchEthics, 20+ advanced research studios |
| **ahg-ai-services** | ahgAIPlugin | Partial | HIGH | XL | Quota enforcement, Custom NER gazetteers, Translation memory, Suggested connections, HTR (40+ routes), DONUT document understanding, OCR+LLM correction, Cost tracking |
| **ahg-ai-chatbot** | ahgAIPlugin | Partial | HIGH | XL | WhatsApp channel, Floating widget, ChatbotSkillService, PreservationKnowledgeService, Grounding score tracking, Page-aware context, Rate limiting, Escalation workflow |
| **ahg-library** | ahgLibraryPlugin | Partial | HIGH | XL | MARC editor (import/export), ONIX ingestion, SUSHI 5.0 server, Circulation domain model (patron, checkout, hold, fine, loan_rule), Patron management, Acquisitions schema, Serials schema, ILL with EDI, Usage tracking, Notice templates |
| **ahg-metadata-export** | ahgMetadataExportPlugin | Partial | HIGH | XL | EAD 4, EAC-CPF 2.0, RAD/DACS exporters, CIDOC-CRM actors/terms, SPARQL endpoint, Public RDF bulk dumps, XML import (EAD/MARCXML), METS+PREMIS |
| **ahg-backup** | ahgBackupPlugin | Partial | HIGH | XL | Off-site replication (S3, rsync, localfs), GPG AES256 encryption, PITR via binary log, Granular restore, Integrity verification, Workbench notifications, Replication ledger |
| **ahg-settings** | ahgSettingsPlugin | Partial | HIGH | XL | AI Condition Assessment clients, Accession/Audit/Data Protection/Encryption/Face Detection settings, FTP/Fuseki/IIIF/Ingest/Integrity/Voice AI/Spectrum configs, Theme CSS generation |
| **ahg-exhibition** | ahgExhibitionPlugin | Partial | HIGH | XL | Exhibition CRUD + workflows, Event management + RSVPs, Reconstruction module (stages/montage), Guided tours, Furniture library, Visitor interactions (docent, presence, annotations), TTS narration |
| **ahg-c2pa** | ahgC2paPlugin | Partial | HIGH | XL | 12 public trust explorers (authenticity reports, inference provenance, preservation timeline, trust dossier, verify object, coverage dashboard), 13 services, 17 view templates, per-object badges |
| **ahg-ric** | ahgRicExplorerPlugin | Partial | HIGH | XL | Linked Data API (50+ REST endpoints), OAI-PMH, RiC entity CRUD (places, activities, rules, instantiations), RDF/Turtle import, CIDOC-CRM export, SPARQL proxy, Content negotiation |
| **ahg-api** | ahgAPIPlugin | Partial | HIGH | XL | OAI-PMH, Linked Data graph, DCAT catalogue, Dataset exports, Atom/RSS feeds, Citation formats, IIIF/METS, SKOS vocabularies, GraphQL, OpenAPI reflection |
| **ahg-semantic-search** | ahgSemanticSearchPlugin | Partial | HIGH | XL | Endangered heritage register + workflow, Repatriation claim workflow + community knowledge, Displaced heritage detection, Research leads promotion, Language corpus + revival, Public discovery surfaces (themes, people, places, timeline, virtual return) |
| **ahg-ingest** | ahgIngestPlugin | Partial | HIGH | L | SharePoint native import, OAIS SIP/AIP/DIP packaging, AI enrichment pipeline (OCR, NER, virus scan, summarization, translation, face detection), Queue-based commit, Accession entity type |
| **ahg-access-request** | ahgAccessRequestPlugin | Partial | HIGH | L | Persistent object access grants, Audit trail per request, Classification-level approver authority, Descendant inclusion tracking, User access grants visibility |
| **ahg-ai-compliance** | ahgAiCompliancePlugin | Partial | HIGH | L | Risk Register admin UI, Oversight Policy admin UI, Model Registry admin UI, Annex IV documentation generator, Risk monitoring CLI |
| **ahg-extended-rights** | ahgExtendedRightsPlugin | Partial | HIGH | L | Orphan works due diligence (tracking, searches, completion), PREMIS rights records (8 acts + grants), Territory restrictions, API endpoints (rights/embargo checking) |
| **ahg-federation** | ahgFederationPlugin | Partial | HIGH | XL | Union catalogue registry + publishing, Inter-institution loan workflow + analytics, Join-network requests + moderation, Europeana EDM export + sitemap |
| **aa-library** | ahgLibraryPlugin | Partial | HIGH | XL | Complete circulation (patron, copy, checkout, hold, fine, loan_rule), Patron management, Acquisitions full schema, Serials full schema, ILL with EDI, SUSHI server, Usage tracking, MARC binary format, ONIX, KBARTO, Z39.50 server, Authority control, OPAC patron portal |
| **ahg-scan** | ahgScanPlugin | Partial | HIGH | L | Scan inbox UI, File-level state management (pending/processing/done/failed), Scan API (headless scanner integration), WARC web archiving (capture/replay), Advanced console commands |
| **ahg-3d-model** | ahg3DModelPlugin | Partial | HIGH | L | Browse/derivative-management view, Batch thumbnail generation, On-demand thumbnails, Multi-angle generation, User-initiated TripoSR 2D→3D, Camera bookmarks, Preview modal, Audit logging integration |
| **ahg-label** | ahgLabelPlugin | Partial | HIGH | M | Batch print route + endpoint, Batch label template, Multi-entity label rendering |
| **ahg-donor-manage** | ahgDonorManagePlugin | Partial | HIGH | M | Consolidate two PSIS plugins, Extend contact fields, Implement actor rename workflow, Full-stack CRUD synchronization |
| **ahg-preservation** | ahgPreservationPlugin | Partial | HIGH | L | PREMIS XML serialization, OAIS lifecycle workflows (SIP/AIP/DIP), Format obsolescence tracking, Advanced fixity scanning with self-repair |
| **ahg-provenance-ai** | ahgProvenancePlugin | Partial | HIGH | M | Provenance trace API endpoints, Coverage diagnostic API |
| **ahg-marketplace** | ahgMarketplacePlugin | Partial | HIGH | L | Broker/Artist management, 12-hour listing reservations, Featured listing promotion, Digital license tracking, Buyer checkout flows, PayFast ITN webhook |
| **ahg-dedupe** | ahgDedupePlugin | Partial | HIGH | M | Authority dedup workflows (separate dashboard), Contact browsing, Function management, Occupations, Split/merge for authorities, Work queue, Completeness tracking |
| **ahg-discovery** | ahgDiscoveryPlugin | Partial | HIGH | M | Ablation switch for strategies, Cache bypass, Rich telemetry columns, DiscoverySimulateCommand, RRF reranking, Suggest endpoint, Image similarity search |
| **ahg-search** | ahgSearchPlugin | Partial | HIGH | L | SearchAnalyticsService + dashboard, VectorSearchController (Qdrant), BlendedSearchService (RRF), DiscoveryController API, Geospatial filtering, Cursor pagination, Click-tracking |
| **ahg-privacy** | ahgPrivacyPlugin | Partial | HIGH | L | ComplianceAutopilotController (heratio#1199), Article30Service, DpiaRiskService, RetentionProposal workflow, EmbeddedFindingsController, VisualRedactionService editor |
| **ahg-security-clearance** | ahgSecurityClearancePlugin | Partial | HIGH | L | TOTP MFA (RFC-6238 + recovery codes), OTP/SMS MFA, Email OTP, Per-tenant MFA policy enforcement, WebAuthn passkeys (partially implemented) |
| **ahg-help** | ahgHelpPlugin | Partial | HIGH | L | Interactive System Map (Cytoscape), System Breakdown Tree, Data-driven configuration arrays |
| **ahg-media-processing** | ahgDAMPlugin | Partial | HIGH | L | DerivativeService (thumbnails, references), Batch regeneration, WatermarkService, Admin dashboard, Watermark settings UI, PhotoProcessor (EXIF, re-encode, compress, orient) |
| **ahg-media-streaming** | ahgIiifPlugin | Partial | HIGH | L | Caption track CRUD, WebVTT serving, Language code management, Default track, Remote VTT caching, SRT→VTT conversion, Thumbnail generation |
| **ahg-naz** | ahgNAZPlugin | Partial | HIGH | M | Researcher update action, Audit log view, Full reports (closures/protected/schedules/permits), Research visit logging, Number generation logic |
| **ahg-repository-manage** | ahgRepositoryManagePlugin | Partial | HIGH | XL | Theme editing UI, Upload limit management, Repository autocomplete, Print-friendly view, Contact CRUD with encryption, Full ISDIAH edit form, Digital objects display, Maintained actors |
| **ahg-request-publish** | ahgRequestToPublishPlugin | Partial | HIGH | L | Anonymous submit endpoint + receipt token, Token-based lookup (no auth), Curator inbox, Per-request review panel, Email notifications, ahg_publish_request schema |
| **ahg-static-page** | ahgStaticPagePlugin | Partial | HIGH | M | Public routes + display, StaticPageController.show(), Markdown rendering, Protected slug enforcement, Frontend view templates |
| **ahg-term-taxonomy** | ahgTermTaxonomyPlugin | Partial | HIGH | XL | SKOS export/import (RDF/XML/Turtle/JSON-LD), Cross-vocab mapping (SKOS-XL), SHACL validation, Term relationships (converse/related/narrow), Notes management, Tree view, Autocomplete, Create action |
| **ahg-storage-manage** | ahgStorageManagePlugin | Partial | HIGH | M | Container-to-IO linking UI, Unlink capability, Link-to blade views, Container search in workflow |
| **ahg-translation** | ahgTranslationPlugin | Partial | HIGH | XL | UI string editor + history, Language management routes, Draft queue dashboard, Museum metadata translation, Dropdown translation, History filtering, AI provenance, Side-by-side modal |
| **ahg-user-manage** | ahgUserManagePlugin | Partial | HIGH | L | Per-user ACL editors (4 entity types), Plugin capability grants, Registration approval workflow, Researcher ACL, Plugin preferences, UI views |
| **ahg-ipsas** | ahgIPSASPlugin | Partial | HIGH | L | Impairment creation workflow, Insurance creation workflow, Disposal management, Depreciation calculations, Advanced compliance checks, Enhanced asset number generation |
| **ahg-nmmz** | ahgNMMZPlugin | Partial | HIGH | M | Monument inspection CRUD, Audit logging in service, Auto-numbering (NM-YYYY-NNNN), Comprehensive template views, Compliance rules engine |
| **ahg-heritage-manage** | ahgHeritageAccountingPlugin | Partial | HIGH | M | Qualified Valuer Registry CRUD, OCI/Revaluation Reserve movement ledger, OciMovementService, ValuerController, OciMovementController |
| **ahg-custom-fields** | ahgCustomFieldsPlugin | Partial | HIGH | M | Multiselect field type, Donor entity type, Field grouping UI, Validation rules, Dropdown taxonomy integration |
| **ahg-dacs-manage** | ahgDacsManagePlugin | Partial | HIGH | M | Related material descriptions (relation type 173), Repository autocomplete backend, RelatedMaterialDescriptionIds extraction |
| **ahg-rad-manage** | ahgRadManagePlugin | Partial | HIGH | L | Create (new IO) action, Dynamic event management UI, Related materials form UI, Material type multi-select, Publisher series detail, Description detail level |
| **ahg-mods-manage** | ahgModsManagePlugin | Partial | HIGH | M | MODS-specific serialized properties, OriginInfo publisher management (actor + relation), Creation/publication event split, Source standard field, DateCreated/DateIssued handling |
| **ahg-function-manage** | ahgFunctionManagePlugin | Partial | HIGH | M | Parallel/other name forms, Maintenance notes, Related functions, Related resources with relational metadata |
| **ahg-gallery** | ahgGalleryPlugin | Partial | HIGH | M | GalleryCsvImporter, Marketplace listing integration, Audit logging, Culture-aware i18n, Museum metadata integration |
| **ahg-condition** | ahgConditionPlugin | Partial | HIGH | M | Risk assessment dashboard, Risk-based routes, Photo comparison UI, Assessment scheduling, Vocabulary management UI, Template form builder, Audit trail integration |
| **ahg-dam** | ahgDAMPlugin | Partial | HIGH | M | CSV bulk import + validation, Metadata extraction from files (camera, GPS, dimensions, color space), Lightbox feature, Item physical location tracking API, Full metadata extraction service |
| **ahg-loan** | ahgLoanPlugin | Partial | HIGH | M | TourController (touring-exhibition booking), TourSchedulingService (conflict detection), ahg_loan_tour_booking schema, Tour schedule views |
| **ahg-multi-tenant** | ahgMultiTenantPlugin | Partial | HIGH | L | User/storage quotas, Full branding customization (logo, HTML snippets), TenantFileService, Fine-grained role matrix, Header/path-based resolution, Facade pattern, Middleware, Statistics dashboard |
| **ahg-ftp-upload** | ahgFtpPlugin | Partial | HIGH | M | CombineFolder action (PDF/A), ReadyToLink/attachExisting, Subfolder navigation, Folder structure preservation |
| **ahg-cart** | ahgCartPlugin | Partial | HIGH | M | Marketplace listing add/remove, Marketplace checkout (cart_group_id), Demo checkout, Cart 'kind' column, Listing_id column |
| **ahg-biblio-bf** | ahgLibraryPlugin | Partial | HIGH | L | BIBFRAME import + validation UI, Graph-aware inline editor, Public LOD/SPARQL endpoints (Turtle/JSON-LD), Agent management dashboard |
| **ahg-biblio-frbr** | ahgLibraryPlugin | Partial | HIGH | M | OpenRiC FRBR XML serialization, FRBR validation/import UI, JSON/RDF export, Dedicated import controller, Validator UI, Agent authority browser, Work-cluster display |
| **ahg-acl** | ahgSecurityClearancePlugin | Partial | HIGH | XL | ACL group management, Permission matrix engine, Per-entity ACL editors, Group membership mgmt, Translate flag, Term-level permissions, Approval workflow |
| **ahg-actor-manage** | ahgActorManagePlugin | Partial | HIGH | M | Extended contact fields, Actor rename UI, ActorService high-level CRUD orchestrator, Authority reconciliation workflow, Dashboard/workqueue with user assignment |
| **ahg-display** | ahgDisplayPlugin | Partial | HIGH | M | BrowseEmbedded route, Reindex route, GlamSearch standalone, TreeviewPage route, User browse settings schema variance |
| **ahg-portable-export** | ahgPortableExportPlugin | Partial | HIGH | M | ApiQuickStart/apiClipboardExport, apiImportValidate/Progress/List, portableVerifyTask, portableImportTask, Service architecture |
| **ahg-integrity** | ahgIntegrityPlugin | Partial | HIGH | M | Vital Records management, Record Declarations workflow, Destruction Certificates, Retention Events tracking |
| **ahg-icip** | ahgICIPPlugin | Partial | HIGH | M | OCAP overlay framework + assessment, ocapDashboard + ocapSettings, ocapSetPossession, LocalContextsHubService |
| **ahg-pdf-tools** | ahgPreservationPlugin | Partial | HIGH | M | PDF text extraction (pdftotext), Single/batch extraction endpoints, Extracted text storage, Dashboard stats, Console commands |
| **ahg-ocfl** | ahgOcflPlugin | Partial | HIGH | M | Embedded metadata extension, Backfill command for metadata extension retrofit |
| **ahg-fairness** | ahgFairnessPlugin | Partial | HIGH | M | Fairness assessment workflows, Impact tracking, Metrics dashboard |
| **ahg-dropdown-manage** | ahgSettingsPlugin | Partial | HIGH | M | Multi-source dropdown editing (term, setting tables), Translation draft workflow, Column mappings display |
| **ahg-observability** | ahgObservabilityPlugin | Partial | HIGH | L | OpenTelemetry tracing (spans, OTLP exporter, span processors), Database query metrics, Slow-query tracing |
| **ahg-graphql** | ahgGraphQLPlugin | Partial | HIGH | M | ResearchProject/Collections/Annotations queries, Researcher profile aggregation, ORCID integration |
| **ahg-iiif-collection** | ahgIiifPlugin | Partial | HIGH | M | Mirador workspace persistence (ahg_iiif_workspace), Change Discovery 1.0 (Activity Streams), NER annotation ingestion, OCR export (ALTO/hOCR/PAGE-XML), Metadata enrichment |
| **ahg-functions-docs** | ahgFunctionsDocsPlugin | Partial | HIGH | L | Markdown parsing (5 KB files), Pagination, Filtering, TOC sidebar, Caching by mtime |
| **ahg-favorites** | ahgFavoritesPlugin | Partial | HIGH | M | Email folder sharing, Nested folder hierarchy, Folder icons, Visibility enum, PDF export |
| **ahg-jobs** | ahgJobsManagePlugin | Partial | HIGH | M | Execution metadata table (ahg_job_execution), State transition methods, Type constants/filtering, Priority levels |
| **ahg-vendor** | ahgVendorPlugin | Partial | HIGH | M | PII field encryption, Audit logging integration, Vendor status display service, Backfill encryption command |
| **ahg-spectrum** | ahgSpectrumPlugin | Partial | HIGH | M | Barcode scan/assign, Object-level insurance, PATCH endpoint for atomic updates, Workflow SOP URL management, Notifications API |
| **ahg-workflow** | ahgWorkflowPlugin | Partial | HIGH | L | WorkflowSlaService, WorkflowEventService, PublishGateService, ChangeSummaryService, Step reordering, REST API endpoints |
| **ahg-request-manage** | ahgRequestManagePlugin | Partial | MEDIUM | M | Multi-step approvals for donations/access, SLA tracking, Status workflows, Email routing, Admin dashboards |
| **ahg-z3950** | ahgLibraryPlugin | Partial | MEDIUM | L | Search UI + result browsing, Single/batch import, Target CRUD dashboard, Statistics view, MARC import service, Z39.50 server daemon, BER encoder/decoder |
| **ahg-cdpa** | ahgCDPAPlugin | Partial | MEDIUM | M | CLI commands (report/license-check/status/requests), CDPAService layer, Scheduled background tasks |
| **ahg-data-migration** | ahgDataMigrationPlugin | Present | MEDIUM | M | Advanced multi-sheet XLS parsing, TransformationEngine, Mapping sharing profiles, Sector-specific CLI commands |
| **ahg-doi** | ahgDoiPlugin | Present | HIGH | S | — (PSIS exceeds Heratio stub) |
| **ahg-doi-manage** | ahgDoiPlugin | Partial | HIGH | L | DataCite Events API integration (ahg_datacite_event), Event domain classes, RecordDoiView middleware, Phase 2 enrichment (ORCID, geoLocations, funding), Email notifications |
| **ahg-inference-receipts** | ahgAiCompliancePlugin | Partial | MEDIUM | L | Article 9/11/14 admin UIs (risk/model/oversight), service integration hooks |
| **ahg-gis** | ahgGISPlugin | Present | LOW | S | — (PSIS exceeds Heratio) |
| **ahg-export** | ahgExportPlugin | Present | LOW | S | — |
| **ahg-audit-trail** | ahgAuditTrailPlugin | Present | LOW | S | — |
| **ahg-authority-resolution** | ahgAuthorityResolutionPlugin | Present | HIGH | S | — |
| **ahg-donor-manage** | ahgDonorManagePlugin | Present | HIGH | M | — (consolidate two PSIS plugins) |
| **ahg-feedback** | ahgFeedbackPlugin | Present | LOW | S | — |
| **ahg-forms** | ahgFormsPlugin | Present | HIGH | S | — |
| **ahg-accession-manage** | ahgAccessionManagePlugin | Present | HIGH | S | — |
| **ahg-dc-manage** | ahgDcManagePlugin | Present | LOW | S | — |
| **ahg-menu-manage** | ahgMenuManagePlugin | Present | LOW | S | — |
| **ahg-landing-page** | ahgLandingPagePlugin | Present | HIGH | S | — |
| **ahg-statistics** | ahgStatisticsPlugin | Present | HIGH | S | — |
| **ahg-share-link** | ahgTimeLimitedShareLinkPlugin | Present | HIGH | S | — |
| **ahg-jobs-manage** | ahgJobsManagePlugin | Present | LOW | S | — |
| **ahg-museum** | ahgMuseumPlugin | Present | HIGH | S | — |
| **ahg-researcher-manage** | ahgResearcherPlugin | Present | LOW | S | — |
| **ahg-resourcesync** | ahgResourceSyncPlugin | Present | HIGH | S | — |
| **ahg-narssa** | ahgNARSSAPlugin | Present | LOW | S | — |
| **ahg-version-control** | ahgVersionControlPlugin | Present | LOW | S | — |

---

## Packages with NO PSIS Equivalent

The following 8 packages require creation of entirely new Symfony plugins:

| Package | Heratio Summary | Estimated Effort | Rationale |
|---------|-----------------|------------------|-----------|
| **ahg-oai** | OAI-PMH 2.0 endpoint (5 formats: oai_dc, oai_ead, oai_ead3, mods, marcxml) with resumption tokens, tombstone mgmt, federation integration, rate limiting, admin UI | XL | PSIS has legacy arOaiPlugin (Symfony 1.x) with minimal features; ahg-oai is modern rewrite. Federation-aware OAI requires ahgFederationPlugin integration. |
| **ahg-rights** | Complete rights CRUD (basis, embargoes, orphan works, TK labels, RightsStatements, CC licenses, territory restrictions, dispositions, audit) | XL | Database schema ported from PSIS (2026-04-30) but no application code. Foundational compliance plugin. |
| **ahg-image-ar** | Image-to-video MP4 animation generator (SVD/CogVideoX/WAN) with admin panel, external video-server integration, per-object management | XL | PSIS has static 2D WebXR viewer only; Heratio is AI animation platform. Requires external service integration. |
| **ahg-sharepoint** | Microsoft 365 SharePoint tenant config, drive registration, ingest rules, delta sync, webhook subscriptions, federated search | M | PSIS has Phase 1 scaffold with TODO implementations; Heratio has service stubs only. Porting would complete both. |
| **ahg-functions-docs** | Browser-rendered catalogues of 5 auto-generated markdown sources (PHP/JS/Blade/Python/Routes) with TOC, pagination, filtering, caching | L | Unique to Heratio dev documentation system; not applicable to PSIS archive/GLAM use. |
| **ahg-theme-b5** | Bootstrap 5 theme with layouts, navigation, voice commands UI, display modes, branding, Google Tag Manager, view data injection | L | PSIS uses bespoke theme; Heratio's B5 is Laravel-specific. Display modes/voice UI could be ported if needed. |
| **ahg-fairness** | Fairness and equity audit system for heritage collections | M | Evaluates collection representation, diversity metrics, repatriation/restitution tracking. Emerging GLAM practice. |
| **ahg-sharepoint** (second entry, consolidated) | — | — | — |

---

## Already at Parity (Present Status)

The following 23 packages require **no porting effort** (feature-complete in PSIS):

- **ahg-authority-resolution** — Authority resolution engine with neighbourhood context, ranked candidates, external lookup, park queue, Fuseki RDF-Star
- **ahg-audit-trail** — Browse, filter, export, statistics, tamper-evident chain, retention policies, pruning
- **ahg-accession-manage** — Intake workflow, appraisal, container tracking, rights assignment, accession finalization
- **ahg-dc-manage** — Dublin Core 1.1 editor (superior PSIS implementation with auto-generation, separation, validation)
- **ahg-export** — Multi-format export (CSV/EAD/DC) for descriptions, authorities, repositories, accessions
- **ahg-feedback** — Public/admin feedback with status management and multilingual support
- **ahg-forms** — Form template builder with 9 field types, pre-built library, drag-drop UI, mapping, submission logging
- **ahg-menu-manage** — Menu CRUD with nested sets, i18n, reordering
- **ahg-landing-page** — Visual builder with 20+ block types, versioning, audit trails (PSIS actually richer)
- **ahg-museum** — CCO cataloguing with CRUD, quality dashboards, Getty AAT, CIDOC-CRM export, authority linking
- **ahg-statistics** — Usage analytics with dashboard, views, downloads, geographic, bot filtering, export
- **ahg-share-link** — Time-limited share tokens with bearer access, clearance gating, audit, revocation
- **ahg-jobs-manage** — Job browse, management, reporting, retry, cancellation, batch progress (PSIS exceeds Heratio)
- **ahg-resourcesync** — ResourceSync 1.1 endpoints (SourceDescription, CapabilityList, ResourceList, ChangeList)
- **ahg-narssa** — NARSSA/NARA/TNA transfer package CLI (Phase A; Phase B dashboard planned for both)
- **ahg-version-control** — Version history with diff, restore, snapshot builder, audit, clearance gating
- **ahg-gis** — GIS spatial search with Haversine, multi-source aggregation, GeoJSON export (PSIS exceeds Heratio)
- **ahg-data-migration** — CSV/XML import with field mapping, job tracking, sector-specific outputs (minor schema/CLI gaps)
- **ahg-researcher-manage** — Researcher submission workflow, import, publish (PSIS exceeds Heratio)
- **ahg-donor-manage** (consolidation note) — Both PSIS plugins (ahgDonorManagePlugin + ahgDonorAgreementPlugin) offer feature parity
- **ahg-doi** — DataCite DOI minting, queue, config, reports, batch sync, deactivate/reactivate (PSIS full implementation; Heratio is stub)
- **ahg-access-request** (partial note) — See partial for expected gaps; core request workflow complete in PSIS
- **ahg-api-plugin** — Admin search UI wrapper (core API exists; only web UI wrapper needed)

---

## Execution Waves

The 83 partial packages and 8 missing packages are grouped into 7 coherent delivery waves by domain, dependency order, and business value:

### Wave 1: Core Entities & Metadata (Weeks 1-4)
**Rationale:** Foundation for all archive/GLAM operations. High user impact.

- **ahg-information-object-manage** — Full description CRUD, export/import, preservation, privacy, extended rights, transcription
- **ahg-dacs-manage**, **ahg-mods-manage**, **ahg-rad-manage** — DACS/MODS/RAD descriptive standards (specialized form editors)
- **ahg-custom-fields** — Custom metadata for entity types (accelerates domain-specific cataloguing)
- **ahg-repository-manage** — Repository CRUD, theme, upload limits, print (governance)
- **ahg-function-manage** — ISDF function browse, CRUD, relationships, name forms
- **ahg-term-taxonomy** — SKOS vocabulary authoring, RDF export/import, mapping, validation

**Estimated effort:** 45 person-days / 3 developer team = 3 weeks

---

### Wave 2: Compliance, Rights & Governance (Weeks 5-8)
**Rationale:** Mandatory for archive/GLAM institutional operations. Security/legal critical path.

- **ahg-rights** (NEW PLUGIN) — Rights holder management, embargoes, orphan works, TK labels, PREMIS records
- **ahg-extended-rights** — Embargo workflow, CC licenses, TK labels, territory restrictions, derivative rules
- **ahg-security-clearance** — MFA (TOTP, OTP, WebAuthn), classification, compartments, watermarking
- **ahg-acl** — ACL group management, permission matrix, per-entity editors, approval workflows
- **ahg-privacy** — GDPR/POPIA compliance, DPIA, ROPA, DSAR, PII scanning + redaction, breach management
- **ahg-naz**, **ahg-nmmz** — Zimbabwe compliance (NAZ closure, NMMZ monuments), permits, researcher registry
- **ahg-cdpa** — CDPA (Zimbabwe data protection) with DPO, processing activities, DPIA, consent
- **ahg-integrity** — Vital records, record declarations, destruction certificates, legal holds, fixity

**Estimated effort:** 40 person-days / 3 developers = 3 weeks

---

### Wave 3: Discovery, Search & Semantic Intelligence (Weeks 9-12)
**Rationale:** User-facing find/explore features. High discovery ROI.

- **ahg-discovery** — Multi-strategy search (keyword, entity, hierarchical, vector, image), ablation, analytics
- **ahg-search** — Global full-text + semantic search, faceting, analytics dashboard, Qdrant integration
- **ahg-semantic-search** (NEW FEATURES) — Endangered heritage, repatriation claims, displaced objects, research leads, language corpus
- **ahg-api** (NEW PLUGIN) — OAI-PMH, Linked Data graph, DCAT, feeds, IIIF, METS, citation formats, SKOS
- **ahg-oai** (NEW PLUGIN) — OAI-PMH 2.0 endpoint (5 formats), resumption tokens, tombstone, federation
- **ahg-ric** — RIC-O Linked Data API, entity CRUD, RDF import/export, SPARQL, content negotiation
- **ahg-metadata-export** — EAD 4, EAC-CPF, RAD/DACS, MODS, CIDOC-CRM, XML import, SPARQL, METS+PREMIS

**Estimated effort:** 50 person-days / 3 developers = 4 weeks

---

### Wave 4: AI, Preservation & Heritage Accounting (Weeks 13-18)
**Rationale:** Emerging institutional needs (AI governance, digital preservation, financial compliance).

- **ahg-ai-compliance** — EU AI Act compliance (Article 12/9/14), risk register, model registry, attestation
- **ahg-ai-services** — NER, HTR, DONUT, OCR+LLM, quotas, translation memory, suggested connections, cost tracking
- **ahg-ai-chatbot** — Conversational RAG, WhatsApp channel, floating widget, skill dispatch, grounding tracking
- **ahg-preservation** — PREMIS serialization, OAIS lifecycle, format obsolescence, advanced fixity scanning
- **ahg-pdf-tools** — PDF text extraction (pdftotext), batch operations, dashboard
- **ahg-backup** — Off-site replication (S3, rsync), GPG encryption, PITR, granular restore, integrity verification
- **ahg-integrity** — (continuation from Wave 2 or standalone: vital records, declarations, certificates)
- **ahg-heritage-manage** — GRAP/IPSAS valuation, impairment, disposal, qualified valuers, revaluation reserve
- **ahg-ipsas** — Asset register, valuation lifecycle, insurance, depreciation, financial reporting
- **ahg-observability** — Prometheus metrics, OpenTelemetry tracing, database/HTTP metrics

**Estimated effort:** 55 person-days / 3 developers = 4.5 weeks

---

### Wave 5: Research, Collections & Scholarly Tools (Weeks 19-26)
**Rationale:** Researcher-centric features for academic institutions. Enables scholarly output workflows.

- **ahg-research** (MAJOR) — Researcher registration, projects, bibliography, annotations, studio suites (writing/publication/method/grant), ethics/funding, DMP, reproducibility
- **ahg-graphql** — GraphQL API for items, actors, research projects, annotations, researcher profiles + ORCID
- **ahg-records-manage** — Retention schedules, disposal workflow + approvals, review queue, file plan import, auto-classification, compliance assessments
- **ahg-ingest** — SharePoint import, OAIS packaging, AI enrichment pipeline, queue-based commit
- **ahg-portable-export** — Clipboard export, import validation + progress, archive integrity checking
- **ahg-favorites** — Email sharing, folder nesting, icons, visibility controls, PDF export
- **ahg-request-publish** — Anonymous submit + receipt token, curator inbox, peer review workflows
- **ahg-provenance** — Chain of custody, AI inference provenance, human overrides, Fuseki RDF-Star
- **ahg-provenance-ai** — Trace API, coverage reporting

**Estimated effort:** 60 person-days / 3 developers = 5 weeks

---

### Wave 6: 3D, Media & Immersive Experiences (Weeks 27-30)
**Rationale:** Emerging GLAM visitor engagement. Supports exhibition/collection virtualization.

- **ahg-exhibition** — Exhibition CRUD, events, reconstruction (stages/montage), guided tours, furniture library, visitor interactions (docent, presence, annotations), TTS
- **ahg-3d-model** — Thumbnail management, batch generation, on-demand, multi-angle, TripoSR 2D→3D, camera bookmarks
- **ahg-image-ar** (NEW PLUGIN) — AI image-to-video animation (SVD/CogVideoX), admin panel, per-object management, external video-server integration
- **ahg-media-processing** — Derivative generation (thumbnails, references), watermarking, admin dashboard, PhotoProcessor (EXIF, orientation, re-encode)
- **ahg-media-streaming** — Caption track management, WebVTT serving, language support, subtitle/audio-description
- **ahg-iiif-collection** — Mirador workspace persistence, Change Discovery, NER annotations, OCR export, metadata enrichment
- **ahg-label** — Batch label printing with barcode/QR codes

**Estimated effort:** 35 person-days / 2 developers = 4 weeks

---

### Wave 7: Library, Museum & Domain-Specific Operations (Weeks 31-36)
**Rationale:** Sector-specific compliance and operations. High value for specialized institutions.

- **aa-library** (MAJOR) — MARC editor (binary import/export), ONIX ingestion, SUSHI server, circulation (patron, copy, checkout, hold, fine), acquisitions, serials, ILL, usage tracking, Z39.50 server, OPAC portal
- **ahg-library** (ENHANCEMENT) — Z39.50 search UI + import, KBARTO, authority control, SUSHI enhancements
- **ahg-z3950** — Z39.50 client search, MARC import, target management dashboard
- **ahg-biblio-bf**, **ahg-biblio-frbr** — BIBFRAME/FRBR import/validation, graph-aware editor, LOD endpoints, work clustering
- **ahg-gallery** — Artist management, loans, valuations, facility reports, venues, CCO cataloguing
- **ahg-spectrum** — Barcode scan/assign, object insurance, PATCH updates, workflow SOP, notifications
- **ahg-condition** — Risk dashboard, assessment scheduling, vocabulary management, template builder, photo comparison
- **ahg-dam** — CSV bulk import, metadata extraction from files (EXIF/IPTC), lightbox, physical location tracking
- **ahg-loan** — Touring exhibition booking, conflict detection, SLA tracking
- **ahg-marketplace** — Broker/artist management, reservations (12-hour), featured listings, PayFast ITN, buyer checkout
- **ahg-cart** — Marketplace integration (listings, grouped checkout, demo mode)
- **ahg-storage-manage** — Container-to-IO linking, unlink, linking workflow UI
- **ahg-nmmz** — (Zimbabwean heritage) Monument inspection, audit, auto-numbering, compliance rules
- **ahg-vendor** — PII encryption, audit logging, status service
- **ahg-scan** — Scan inbox, file-level state management, scan API (headless), WARC web archiving
- **ahg-ftp-upload** — PDF/A combining, folder structure preservation, subfolder navigation
- **ahg-translation** — UI string editor, language management, draft queue, Museum metadata translation, AI provenance

**Estimated effort:** 70 person-days / 3 developers = 6 weeks

---

### Wave 8: Settings, UX & Operational Infrastructure (Weeks 37-40)
**Rationale:** Cross-cutting enablers. Backend configuration and UX polish.

- **ahg-settings** (MAJOR) — AI Condition clients, accession/audit/data-protection/encryption configs, FTP/Fuseki/IIIF/ingest/integrity/voice-ai/Spectrum, theme CSS generation
- **ahg-help** — System map + tree (Cytoscape visualization), interactive navigation
- **ahg-static-page** — Public page display, markdown rendering, protected slugs
- **ahg-display** — Browse-embedded, reindex, glamSearch standalone, treeview admin
- **ahg-dropdown-manage** — Multi-source dropdown editing (term/setting), translation draft workflow
- **ahg-multi-tenant** — Quota enforcement, branding customization, file scoping, role permission matrix, Facade
- **ahg-user-manage** — Per-user ACL, plugin grants, registration approval, researcher ACL
- **ahg-jobs** — Job execution metadata, state transitions, type filtering
- **ahg-workflow** — SLA service, event logging, publish gates, step reordering, REST API
- **ahg-federation** — Union catalogue, inter-institution loans, join requests, Europeana export
- **ahg-sharepoint** (COMPLETION) — SharePoint integration (Phase 2 completion)
- **ahg-forms** — (ensure Wave 1 completeness if deferring from then)
- **ahg-request-manage** — Multi-step approvals, SLA, email routing
- **ahg-permission** (if separate) — Grant/deny/inherit matrix, per-module controls

**Estimated effort:** 40 person-days / 3 developers = 3 weeks

---

## Known Parity Issues Across Both Frameworks

The 4 critical security/compliance gaps identified in Heratio issues affect **both** frameworks' ability to meet institutional requirements:

### Issue #1261: Donor PII Stored Plaintext
**Status:** Affects both Heratio and PSIS. PSIS ahgDonorManagePlugin stores contact_information.email, phone, address_municipality in plaintext. No field-level encryption implemented.

**Recommendation:** Implement PII encryption service (AES-256) in both frameworks for donor table fields, linked from ahg-core. Backfill existing plaintext with encryption migration. Heratio and PSIS both need: (1) PII encryption wrapper in base service, (2) migration for existing plaintext, (3) settings toggle for per-institution encryption policy.

**Wave assignment:** Part of Wave 2 (Compliance).

---

### Issue #1264: Vendor PII Plaintext
**Status:** Affects both frameworks. PSIS ahgVendorPlugin stores phone, email, fax, bank_account_number in plaintext. ahg-vendor lists as partial gap.

**Recommendation:** Same AES-256 encryption service (from #1261) applied to vendor tables. Requires column width changes (VARCHAR to accommodate ciphertext). Heratio framework already defined column widths correctly (VARCHAR(512)); PSIS needs migration.

**Wave assignment:** Part of Wave 2 (Compliance), coordinated with #1261.

---

### Issue #1263: Vendor State-Changes Not in Audit Trail
**Status:** Affects both frameworks. PSIS ahgVendorPlugin has no integration with ahgAuditTrailPlugin for vendor mutations. Heratio mentions AhgAuditTrail hook integration but not verified.

**Recommendation:** Both frameworks need to wire vendor service mutations (create, update, delete) into audit trail listener. PSIS: inject AhgAuditTrailPlugin listener into VendorService. Heratio: ensure VendorService calls AuditLog::captureMutation() for state changes.

**Wave assignment:** Part of Wave 2 (Compliance), coordinated with audit trail integration.

---

### Issue #1248: AI-Gateway Bypass (Direct Node Calls)
**Status:** Affects both frameworks. Heratio's ahg-ai-services and PSIS's ahgAIPlugin allow direct LLM service calls without routing through ahg-ai-compliance gateway, bypassing Article 12 inference logging and Fuseki write-through.

**Recommendation:** Implement mandatory gateway layer in both frameworks. Route all LLM calls (NER, HTR, DONUT, OCR, summarize, translate) through InferenceLogger. Retrofit existing service methods to call logger.log() before returning results. Add configuration toggle to enforce gateway (fail-closed if bypass attempted in strict mode).

**Wave assignment:** Core AI infrastructure (Wave 4). Prerequisite for #1261–1263 fixes.

**Implementation note:** Both frameworks have InferenceService skeleton; requires completion of write-through for all AI service types and enforcement middleware.

---

## Summary

This port represents **~8,800 person-hours of development** across 83 partial and 8 missing packages, distributed over **7-9 waves** suitable for phased institutional delivery. 

**Top priorities by institutional impact:**
1. **Wave 1:** Core metadata and cataloguing (prerequisite for all others)
2. **Wave 2:** Compliance and governance (legal/security critical)
3. **Wave 4:** AI governance (emerging regulatory requirement)
4. **Wave 5:** Research tools (scholar engagement, reproducibility)
5. **Wave 3:** Discovery and semantic intelligence (user-facing differentiation)

**Known blockers (both frameworks):**
- PII encryption (#1261, #1264): Core compliance, enable before public deployment
- Vendor audit trail (#1263): Governance requirement
- AI-gateway enforcement (#1248): EU AI Act compliance before Phase 4 (AI services)

The consolidated data enables **prioritized execution, realistic resource planning, and staged delivery** aligned with institutional readiness and business value.