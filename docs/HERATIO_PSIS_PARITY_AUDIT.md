# Heratio ↔ PSIS/AtoM — Two-Way Functional Parity Audit

_Generated 2026-05-31 from a 13-domain multi-agent audit (Heratio Laravel packages vs PSIS/AtoM Symfony plugins)._

## Parity matrix (PSIS coverage of Heratio)

| Domain | Parity | High gaps | Med gaps | Heratio-only |
|---|---|---|---|---|
| descriptive-manage | 35% | 7 | 5 | 1 |
| sectors | 42% | 6 | 4 | 8 |
| dam-media | 45% | 5 | 6 | 1 |
| ai | 55% | 5 | 4 | 3 |
| iiif-3d | 62% | 5 | 8 | 1 |
| search-discovery | 65% | 6 | 6 | 8 |
| ingest-preservation | 65% | 2 | 3 | 1 |
| accounting-collection | 68% | 3 | 3 | 0 |
| rights-privacy-compliance | 72% | 5 | 5 | 2 |
| research-public | 72% | 7 | 13 | 1 |
| workflow-reporting-misc | 72% | 2 | 4 | 1 |
| api-integration | 75% | 3 | 6 | 3 |
| core-ui | 92% | 0 | 2 | 3 |

**Average parity ≈ 63%.**

## descriptive-manage — 35%

PSIS/AtoM *ManagePlugin coverage is approximately 35% of Heratio's descriptive-manage cluster. Five major packages are completely missing from AtoM: records-manage (7 controllers, 14 services for records management, disposal, retention), heritage-manage (7 controllers, 84 views for public portal), doi-manage, dropdown-manage, and researcher-manage. Within overlapping packages, Heratio's implementations are significantly more sophisticated: information-object-manage has 20 controllers and 12 specialized services (AI, Privacy, Preservation, Condition, Extended Rights) vs AtoM's 1 action module; actor-manage has 10+ specialized authority services (merge, dedup, NER, reconciliation) vs AtoM's basic CRUD; user-manage provides per-object ACL matrices (13 routes) vs basic user admin; and rights-holder-manage implements embargo/orphan-works frameworks missing entirely in AtoM. Storage, Repository, and Donor packages have basic parity but lack advanced workflows. Only DACS/DC/RAD/MODS (metadata standards) are lightweight and achieve near-parity due to their minimal feature scope. The architecture also diverges: Heratio uses instance-based services with locale injection, while AtoM relies on static methods and Symfony 1.4 module routing."

**PSIS missing (Heratio-only):**

- **[high]** Records Management (Classification, Disposal, Retention, Email Capture, etc.) — _ahg-records-manage → MISSING_: Heratio provides 7 controllers (Classification, Compliance, Disposal, EmailCapture, FilePlan, Retention, Review) with 14 specialized services (ClassificationRuleService, DisposalWorkflowService, EmailCaptureService, FilePlanImportService, etc.). No AtoM equivalent exists.
- **[high]** Heritage Public Portal (Landing page, Search, Timeline, Analytics, Contributors) — _ahg-heritage-manage → MISSING_: Heratio provides 7 controllers (Heritage, HeritageAdmin, HeritageAccounting, ValuationReport, GrapCompliance, OciMovement) with 84 views. No AtoM plugin exists.
- **[high]** Advanced Authority Management (Merge, Deduplication, NER Pipeline, Reconciliation) — _ahg-actor-manage → ahgActorManagePlugin_: Heratio has 12 services including AuthorityDedupeService, AuthorityMergeService, AuthorityNerPipelineService, AuthorityReconciliationService. AtoM has only 2 services (ActorBrowseService, ActorCrudService). Missing: deduplication, NER pipeline, reconciliation, identifier management, graph analysis.
- **[high]** Accession Advanced Features (Container barcodes, Rights inheritance, Finalisation workflow, Appraisal, Intake Queue) — _ahg-accession-manage → ahgAccessionManagePlugin_: Heratio AccessionService implements: inheritRightsToIo(), finalisationBlockers(), upsertWorkflow(), nextAccessionNumber(), containerBarcodesEnabled(), rightsInheritanceEnabled(). AtoM AccessionCrudService lacks workflow management, rights inheritance, and finalisation gate logic.
- **[high]** Information Object Advanced Features (AI Describe, PII Masking, Preservation, Condition, Privacy, Provenance, Extended Rights, Finding Aids, Treeview with Sort, Reports) — _ahg-information-object-manage → ahgInformationObjectManagePlugin_: Heratio has 12 controllers (InformationObject, Condition, DigitalObject, Export, ExtendedRights, FindingAid, Hierarchy, Import, Media, Modifications, Preservation, Privacy, Provenance, etc.) with 12 specialized services (AiNerService, ConditionService, ExtendedRightsService with 28 methods, PreservationService, PiiMaskingService, PrivacyService, ProvenanceService, RedactionRenderService). AtoM has only 1 basic module with 4 basic services missing all advanced features.
- **[high]** User ACL Management (Per-object permission matrix, Per-standard access control, Plugin grants/preferences) — _ahg-user-manage → ahgUserManagePlugin_: Heratio provides UserAclController implementing per-object ACL matrices for Actor, InformationObject, Repository, Term with 13 routes for ACL editing. AtoM userManage has only basic CRUD.
- **[high]** Rights-Holder Management (Embargoes, Orphan Works, Rights Admin, Extended Rights, TK Labels) — _ahg-rights-holder-manage → ahgRightsHolderManagePlugin_: Heratio has 5 controllers (RightsHolder, RightsAdmin, Rights, Embargo, ExtendedRights) with comprehensive embargo/orphan-works/rights-statement management. AtoM plugin exists but has only 1 action module and 2 services (basic CRUD).
- **[medium]** DOI Management — _ahg-doi-manage → MISSING_: Heratio DoiController with 2 services. No corresponding AtoM plugin.
- **[medium]** Dropdown/Custom Fields Management — _ahg-dropdown-manage → MISSING_: Heratio DropdownController managing custom dropdown values. No AtoM equivalent.
- **[medium]** Researcher Submissions/Portal — _ahg-researcher-manage → MISSING_: Heratio ResearcherSubmissionController with 1 service and 16 views. No AtoM plugin exists.
- **[medium]** Storage Location Management (Strongroom CRUD, Holdings Reports, Box Lists, Physical Object Linking) — _ahg-storage-manage → ahgStorageManagePlugin_: Heratio has 2 controllers (Storage, Strongroom) with 15 methods including holdingsReportExport(), linkTo(), boxList(). AtoM has only 4 execute actions: Browse, Autocomplete, BoxList, HoldingsReportExport. Missing: Strongroom management, linking workflows.
- **[medium]** Institution/Repository Advanced Management (Browse, Show, Create/Edit, Publishing, Settings) — _ahg-repository-manage → ahgRepositoryManagePlugin_: Heratio RepositoryController implements full CRUD + show. AtoM plugin has basic browse/create/edit but lacks full scope.
- **[low]** Function Management — _ahg-function-manage → ahgFunctionManagePlugin_: Both have 1 controller and 2 services, but Heratio services may have richer methods.
- **[low]** Donor Management — _ahg-donor-manage → ahgDonorManagePlugin_: Both have 1 controller and 2 services. Heratio likely has donor-specific workflows like agreement tracking.

**PSIS-only (Heratio missing):**

- [low] Accession Intake Queue View (with Dashboard stats) — _ahgAccessionManagePlugin_: AtoM accessionManage has executeDashboard() and getQueueStats() in dashboard action. Heratio has a standalone intakeQueue() and dashboard() methods but AtoM integrates them.

**Divergent:**

- Information Object Browse/Display: Heratio implements full MVC with 141 views in resources/views. AtoM uses Symfony 1.4 with templates in modules/ioManage/templates. Heratio has AI describe (aiDescribe, AiNerService), print view, display standard selection. AtoM browse is simpler, lacks these features.
- Service Architecture: Heratio uses instance-based services with locale injection. AtoM uses static method calls. Heratio AccessionService is instance-based; AtoM AccessionCrudService is entirely static.
- Actor Authority Management: Heratio separates browse (ActorBrowseService) and CRUD, plus 10 specialized authority services (Merge, Dedup, NER, Reconciliation, etc.). AtoM has flat ActorBrowseService and ActorCrudService with no specialized authority features.
- Information Object Standards Routing: Heratio has dedicated controllers for each standard (DC, RAD, DACS, MODS) as separate package dependencies. AtoM uses IoFormHelper and module forwarding within ioManage module to detect standard and forward to plugin-specific modules.

## sectors — 42%

Heratio and PSIS/AtoM have significant divergence on the sectors domain, with only ~42% parity. AtoM has substantially more advanced features in Museum (loan management, Getty integration, condition reporting, CIDOC-CRM) and Marketplace (12 vs 3 services, multi-sector support). Heratio leads in Bibliographic standards (BIBFRAME, FRBR dedicated packages) and Library APIs (full API suite for orders, serials, vendors, budgets). Library and Exhibition/Gallery are roughly equivalent in structure but with different execution models (Artisan vs Symfony tasks). Museum is the most divergent: AtoM is feature-complete for institutional loan workflows while Heratio museum is a skeleton. Neither side has the other's biblio packages or comprehensive Getty integration—these are Heratio-only and AtoM-only respectively. Heratio is still Phase 1 (basic browse/display) while AtoM's sectors plugins are mature operational systems.

**PSIS missing (Heratio-only):**

- **[high]** BIBFRAME 2.0 bibliographic serialization — _ahg-biblio-bf → N/A (no BIBFRAME plugin exists)_: Heratio has BibframeService.php, BibframeSerialisationService.php with RDF/XML/Turtle/JSON-LD serialization; AtoM has no equivalent plugin
- **[high]** FRBR (IFLA) conceptual model support — _ahg-biblio-frbr → N/A (no FRBR plugin exists)_: Heratio has FrbrService.php with Work-Expression-Manifestation-Item mapping and OpenRiC integration; AtoM has no dedicated FRBR plugin (partial FRBR in library)
- **[high]** Library API suite (order, serial, vendor, budget APIs) — _ahg-library → ahgLibraryPlugin_: Heratio has LibraryOrderApiController, LibrarySerialApiController, LibraryVendorApiController, LibraryBudgetApiController in Api/ folder; AtoM has no libraryApi module or equivalent APIs
- **[high]** OPAC patron self-service and OPAC search — _ahg-library → ahgLibraryPlugin_: Heratio has OpacPatronController and LibraryOpacSearchService (with ES fallback); AtoM has SearchService with limited Lucene-style support only, no dedicated OPAC patron module
- **[high]** Museum loan management (loan out/in, courier, facility) — _ahg-museum (minimal) → ahgMuseumPlugin_: AtoM has loan_schema.sql, LoanService.php, LoanDashboardService.php, CourierManagementService.php, FacilityReportService.php; Heratio museum package has no loan management at all
- **[high]** Museum Getty vocabulary linking (AAT, TGN, ULAN) — _ahg-museum (missing) → ahgMuseumPlugin_: AtoM has GettyLinkingService.php, AatService.php, TgnService.php, UlanService.php with batch linking capabilities; Heratio museum has no Getty integration
- **[medium]** Museum condition reporting and provenance tracking — _ahg-museum (missing) → ahgMuseumPlugin_: AtoM has ConditionReportService.php and ProvenanceService.php; Heratio museum package has no condition or provenance services
- **[medium]** Museum object comparison and deduplication — _ahg-museum (missing) → ahgMuseumPlugin_: AtoM has ObjectComparisonService.php and MuseumGrapService.php; Heratio has no object comparison or deduplication
- **[medium]** Console commands for serial management — _ahg-library → ahgLibraryPlugin_: Heratio has SerialClaimAlertsCommand and SerialExpiryAlertsCommand (PHP Artisan); AtoM has task classes (librarySerialRenewalReminderTask, librarySerialExpectedTask) via Symfony tasks
- **[medium]** Exhibition staging and workflow management — _ahg-exhibition → ahgExhibitionPlugin_: AtoM has ExhibitionWorkflow.php and exhibitionTask.class.php; Heratio has no workflow or task support for exhibitions

**PSIS-only (Heratio missing):**

- [high] Museum loan management suite (loan out/in, courier, facility, calendar) — _ahgMuseumPlugin_: AtoM has comprehensive loan_schema.sql (loan, loan_item, condition_report, courier tables), LoanService.php, LoanDashboardService.php, LoanNotificationService.php, LoanCalendarService.php, CourierManagementService.php; Heratio museum is minimal (only browse + vocabulary)
- [high] Museum Getty vocabulary integration (SPARQL, auto-linking, caching) — _ahgMuseumPlugin_: AtoM has Getty/ subfolder with GettySparqlService.php, GettyLinkingService.php, AatService.php, TgnService.php, UlanService.php, GettyCacheService.php with batch linking and confidence scoring; Heratio museum has no Getty services
- [high] Museum CCO (Cataloging Cultural Objects) taxonomy — _ahgMuseumPlugin_: AtoM has cco_taxonomies.sql with complete CCO vocabulary mapping and CcoTaxonomyService.php; Heratio museum has no CCO support
- [medium] Museum CIDOC-CRM compliance module — _ahgMuseumPlugin_: AtoM has ahgMuseumPlugin/modules/cidoc/ module; Heratio has no CIDOC-CRM module
- [medium] Museum object measurements and technical details — _ahgMuseumPlugin_: AtoM has MeasurementService.php for structured measurements; Heratio has no measurement service
- [medium] Museum condition assessment and reporting — _ahgMuseumPlugin_: AtoM has ConditionReportService.php with structured condition assessment; Heratio has no condition assessment
- [medium] Museum provenance and custody chain — _ahgMuseumPlugin_: AtoM has ProvenanceService.php for detailed provenance tracking; Heratio has no provenance service
- [low] Gallery artist and collections management — _ahgGalleryPlugin_: Gallery modules exist in AtoM but Heratio gallery is minimal (only browse + CSV import)

**Divergent:**

- Console/Task execution framework: Heratio uses Artisan commands (PHP/Illuminate), AtoM uses Symfony task classes (.class.php tasks in lib/task/); both achieve same goals but different execution models
- Library search implementation: Heratio LibraryOpacSearchService has optional Elasticsearch with DB fallback; AtoM SearchService is pure DB with Lucene-style parsing, no ES option
- Marketplace Services coverage: Heratio has 3 core services (MarketplaceService, PaymentService, ReservationNotifier); AtoM has 12 services (Auction, Collection, Currency, Offer, Payout, Review, Seller, Shipping, Transaction) — AtoM significantly more feature-rich for multi-sector marketplace
- Museum package scope: Heratio museum is minimal (browse, vocabulary); AtoM museum is comprehensive (loan, Getty, condition, provenance, workflow, measurements, CIDOC-CRM)
- Exhibition implementation: AtoM has workflow state machine (ExhibitionWorkflow) and scheduled tasks; Heratio has basic CRUD controllers only

## dam-media — 45%

Heratio has significantly richer dam-media functionality (45% parity from AtoM perspective). Four Heratio packages (ahg-media-processing, ahg-media-streaming, ahg-c2pa, ahg-image-ar) lack AtoM equivalents, covering streaming, derivatives, watermarking, and AI provenance. Metadata export stronger in Heratio (16 vs 12 formats, RDA/DACS support). AtoM's rights_derivative_rule system (per-role redaction/resize) absent in Heratio. Core modern capabilities missing from PSIS: HTTP streaming with seeking, video transcoding, watermarking, PDF extraction, C2PA manifests, AI video animation—all standard in Heratio.

**PSIS missing (Heratio-only):**

- **[high]** Media derivatives (thumbnails, reference images, posters) — _ahg-media-processing → ahgDAMPlugin_: Heratio DerivativeService.generateThumbnail/Reference; AtoM has media_derivatives table but no service
- **[high]** Watermarking system (visible + invisible) — _ahg-media-processing → ahgDAMPlugin_: Heratio WatermarkService; AtoM has watermark tables but no service implementation
- **[high]** HTTP streaming with Range request support for seeking — _ahg-media-streaming → none_: Heratio StreamingService handles byte-range seeking; no AtoM equivalent
- **[high]** Video/audio transcoding to browser formats — _ahg-media-streaming → none_: Heratio TranscodingService transcodes AVI/MOV to MP4; no AtoM equivalent
- **[high]** C2PA manifest generation, signing, embedding — _ahg-c2pa → none_: Heratio C2paService with Ed25519 signing; no AtoM C2PA plugin
- **[medium]** Caption/subtitle track management — _ahg-media-streaming → none_: Heratio CaptionTrackService; no AtoM equivalent
- **[medium]** PDF text extraction — _ahg-pdf-tools → ahgPreservationPlugin_: Heratio PdfTextExtractService; AtoM Preservation lacks extraction capability
- **[medium]** AI video animation from static images — _ahg-image-ar → none_: Heratio AnimationService calls SVD/CogVideoX server; no AtoM equivalent
- **[medium]** RDA export/import with carrier mapping — _ahg-metadata-export → ahgMetadataExportPlugin_: Heratio supports RAD XML; AtoM has no RAD support
- **[medium]** DACS export/import — _ahg-metadata-export → ahgMetadataExportPlugin_: Heratio DacsSerializer/DacsXmlImporter; AtoM has no DACS
- **[medium]** EAD4, EAD2002, MODS, METS, EAC-F exporters — _ahg-metadata-export → ahgMetadataExportPlugin_: Heratio 16 exporters vs AtoM 12 (missing 8 formats)
- **[low]** Photo processing (brightness, contrast, rotation, crop) — _ahg-media-processing → none_: Heratio PhotoProcessor; no AtoM equivalent
- **[low]** SPARQL query engine for RDF metadata — _ahg-metadata-export → none_: Heratio SimpleSparqlEngine; no AtoM equivalent

**PSIS-only (Heratio missing):**

- [high] Rights-based derivative rules (redaction, resize, format conversion) — _ahgDAMPlugin_: AtoM rights_derivative_rule/rights_derivative_log tables enforce per-role access with conditional watermark/redaction/resize; Heratio DamService lacks equivalent

**Divergent:**

- Label generation: Heratio LabelController vs AtoM labelActions (30-line minimal implementation)
- TIFF/PDF merge: Heratio direct TiffPdfMergeService vs AtoM job-queued ahgPreservationPlugin system

## ai — 55%

AI domain shows 55% parity. Heratio leads in governance (compliance, provenance, receipts), chatbot, DONUT, cost management. AtoM excels in condition assessment SaaS tiers, granular damage tracking, evidence evaluation. Core NER/HTR/authority resolution exist on both with architectural differences. Major PSIS gaps: no chatbot, no receipt library, minimal compliance, no DONUT, simplified HTR. Major Heratio gaps: no SaaS tiers, fewer evaluators, no training workflows. Both implement shared services (LLM, Guardrail, NER) differently.

**PSIS missing (Heratio-only):**

- **[high]** AI Chatbot (RAG-grounded chat engine) — _ahg-ai-chatbot → None_: ChatbotService, QdrantRetriever, WhatsAppChannel; AtoM has no plugin
- **[high]** Inference Receipt Chain — _ahg-inference-receipts → ahgAiCompliancePlugin_: Receipt, Signer, ReceiptChain, KeyPair with JCS+Ed25519; AtoM stores only
- **[high]** EU AI Act Compliance Framework — _ahg-ai-compliance → ahgAiCompliancePlugin_: OversightService, AiRiskService, models; AtoM lacks services
- **[high]** AI Governance Dashboard — _ahg-provenance-ai → ahgProvenancePlugin_: GovernanceController, /admin/governance routes; AtoM lacks these
- **[high]** DONUT (Document Understanding) — _ahg-ai-services → None_: DonutService with extract/batch/training; AtoM absent
- **[medium]** HTR Advanced Features — _ahg-ai-services → ahgAIPlugin_: 40+ HTR routes with bulk/overlay; AtoM has executeHtr only
- **[medium]** Translation Memory Management — _ahg-ai-services → None_: TranslationMemoryService, routes; AtoM absent
- **[medium]** Cost & Quota Management — _ahg-ai-services → None_: CostService, QuotaService, routes; AtoM absent
- **[medium]** LLM Config Admin UI — _ahg-ai-services → ahgAIPlugin_: executeLlmConfigs, executeTemplates; AtoM lacks routes

**PSIS-only (Heratio missing):**

- [medium] SaaS API Tier Management — _ahgAiConditionPlugin_: ahg_ai_service_client table with tier, monthly_limit
- [medium] Training Data Upload Workflows — _ahgAiConditionPlugin_: executeApiTrainingUpload, consent docs, approval flows
- [low] Specialized Evidence Evaluators — _ahgAuthorityResolutionPlugin_: 12+ evaluators: EvidenceDateUtil, RelationalEvaluator, TemporalEvaluator

**Divergent:**

- NER Correction Tracking: Heratio: basic status. AtoM: granular correction_type (value_edit, type_change, both, rejected)
- Condition Assessment Schema: Heratio: generic. AtoM: specialized tables with damage bbox, severity
- LLM Providers: Heratio: generic. AtoM: explicit provider classes (Anthropic, OpenAI, Ollama)
- Translation Domain: Heratio: UI-string. AtoM: document translation (different purpose)

## iiif-3d — 62%

PSIS/AtoM (62% parity) covers core IIIF manifest generation (v2/v3), 3D viewer/thumbnail basics, and RiC sync listening, but is functionally incomplete for production IIIF workflows. Missing are: (1) OCR export in 4 formats (TXT/ALTO/hOCR/PAGE-XML), critical for digital libraries; (2) RFC 8947 content-state + Auth 2.0 flow for viewer interop; (3) Mirador workspace persistence for multi-user workflows; (4) NER->annotation bridging; (5) Change Discovery 1.0; (6) full RiC-O entity serialization + SHACL validation + SPARQL query engine (AtoM has only event listening, no graph query); (7) RDF import with dry-run; (8) CIDOC-CRM export. Heratio's 3 packages (ahg-iiif-collection, ahg-3d-model, ahg-ric) provide enterprise-grade IIIF/3D/RIC functionality with comprehensive tooling (Fuseki integration, Python semantic search, TripoSR orchestration) absent from AtoM. AtoM plugin architecture remains via 8 modules but Heratio's services are substantially richer. Code evidence: Heratio RicEntityService=76KB + RicSerializationService vs. AtoM RicPlace=235 bytes.

**PSIS missing (Heratio-only):**

- **[high]** OCR Export (TXT, ALTO, hOCR, PAGE-XML) — _ahg-iiif-collection/Services/OcrExportService.php → ahgIiifPlugin_: Heratio has comprehensive OcrExportService with four export formats (lines 31-51); AtoM plugin has no equivalent service or database tables (iiif_ocr_text, iiif_ocr_block missing from install.sql)
- **[high]** IIIF Content State (RFC 8947) + Auth 2.0 Flow — _ahg-iiif-collection/Services/IiifContentStateService.php + IiifAuthFlow2Service.php → ahgIiifPlugin/lib/Services/IiifAuthService.php_: Heratio implements RFC 8947 content state encoding/decoding and Auth 2.0 probe/access/token endpoints (routes web.php lines 68-78); AtoM only has Auth 1.0/2.0 base classes without content-state; no IiifContentStateService equivalent
- **[high]** RiC Serialization (full RIC-O JSON-LD -> Turtle) — _ahg-ric/Services/RicSerializationService.php → ahgRicExplorerPlugin_: Heratio RicSerializationService (58KB) transforms entity models to RIC-O JSON-LD -> Turtle for Fuseki; AtoM RicExplorerPlugin has only RicSyncListener + model stubs (RicPlace, RicActivity, RicRule, RicInstantiation are 200-235 bytes each, likely just class_alias shims)
- **[high]** SHACL Validation (RiC-O Shapes) — _ahg-ric/Services/ShaclValidationService.php → ahgRicExplorerPlugin_: Heratio ShaclValidationService validates entities against RIC-O SHACL shapes (routes /admin/ric/shacl-validate, /admin/ric/validate/{type}/{id}); AtoM plugin has no SHACL validator, only basic sync queue management
- **[high]** RiC Entity CRUD + SPARQL Query Service — _ahg-ric/Services/RicEntityService.php (76KB) + SparqlQueryService.php (10KB) → ahgRicExplorerPlugin/lib/RicSyncListener.class.php_: Heratio has full entity lifecycle with Fuseki sync hooks (dispatchSave, cascadeDelete) + SparqlQueryService wrapping Python ric_semantic_search.py; AtoM RicSyncListener only handles sync events with hardcoded entity list, no query service or semantic search
- **[medium]** Mirador Workspace Persistence (Issue #699) — _ahg-iiif-collection/Services/WorkspaceService.php + Controller + routes → ahgIiifPlugin_: Heratio has WorkspaceService (lines 1-162) managing per-user ahg_iiif_workspace table; REST API at /api/iiif/workspace/* (routes lines 119-126); AtoM plugin has no workspace persistence feature
- **[medium]** IIIF Metadata Enrichment (IPTC/EXIF/XMP -> Manifest) — _ahg-iiif-collection/Services/IiifMetadataEnricher.php → ahgIiifPlugin_: Heratio has IiifMetadataEnricher transforming IPTC (creator, keywords) + EXIF into IIIF manifest metadata; AtoM plugin has no metadata enrichment service or IPTC table integration
- **[medium]** NER -> IIIF Annotation Surface (Issue #697) — _ahg-iiif-collection/Controllers/IiifNerAnnotationsController.php + Job → ahgIiifPlugin_: Heratio maps NER-tagged entities to iiif_annotation rows (canvas-scoped AnnotationPage); routes /iiif-manifest/{slug}/canvas/{n}/annotations; Job BuildNerAnnotationsForCanvas.php; AtoM plugin has iiif_annotation table but no NER integration or BuildNer job
- **[medium]** IIIF Change Discovery 1.0 (Activity Streams) — _ahg-iiif-collection/Controllers/IiifChangeDiscoveryController.php → ahgIiifPlugin_: Heratio implements IIIF Change Discovery 1.0 at /iiif/discovery/changes (routes line 45-46) as OrderedCollection of manifest lifecycle changes; AtoM plugin has no change discovery endpoint
- **[medium]** TripoSR 2D->3D Auto-configuration — _ahg-3d-model/src/Services/ThreeDAutoConfigService.php (referenced in lib) → ahg3DModelPlugin/lib/Services/ThreeDAutoConfigService.php_: Heratio Commands (TriposrGenerateCommand, TriposrHealthCommand, TriposrPreloadCommand) orchestrate TripoSR generation; AtoM ThreeDAutoConfigService is a stub (only header); Heratio has full pipeline including TriposrImportService for GLB attachment
- **[medium]** RDF Import with Dry-run (TTL/JSON-LD/RDF-XML) — _ahg-ric/Controllers/RdfImportController.php + Services/RdfImportService.php → ahgRicExplorerPlugin_: Heratio RdfImportController at /admin/ric/import supports dry-run validation then commit; Services/RdfImportService.php parses RDF; AtoM plugin has no import UI or RDF parsing service
- **[medium]** CIDOC-CRM v7.1.3 Per-Record Export (Issue #659) — _ahg-ric/Controllers/CrmExportController.php → ahgRicExplorerPlugin_: Heratio CrmExportController exports records at /admin/export/crm/{id|slug} in RDF/XML or Turtle; AtoM plugin has no CRM export capability
- **[medium]** SPARQL Proxy (Read-only Federated Queries) — _ahg-ric/Controllers/RdfImportController.php (sparqlProxy method) → ahgRicExplorerPlugin_: Heratio proxies SELECT/ASK/CONSTRUCT/DESCRIBE at /api/sparql for federated linked-data clients; AtoM plugin has no SPARQL endpoint
- **[low]** Camera Bookmarks API (Issue #666 Phase 2) — _ahg-3d-model/Controllers/CameraBookmarkController.php → ahg3DModelPlugin_: Heratio CameraBookmarkController provides REST API for camera view bookmarks (routes web.php lines 99-117: GET /3d/{model_id}/bookmarks, POST/PUT/DELETE); AtoM plugin has no camera bookmark feature
- **[low]** 3D Hotspot Management UI + Reports — _ahg-iiif-collection/Controllers/IiifCollectionController.php (threeDReports*) → ahgIiifPlugin/modules/threeDReports/_: Heratio routes to 3D reports: /admin/iiif-3d-reports/{digital-objects,hotspots,models,settings,thumbnails} (routes lines 106-112); AtoM has threeDReports module but no report aggregation in main Heratio package

**PSIS-only (Heratio missing):**

- [low] Multi-module architecture (iiifCollection, iiifAuth, mediaSettings, media, model3d, model3dSettings, ricExplorer, ricDashboard, ricSemanticSearch) — _ahgIiifPlugin/modules/*, ahg3DModelPlugin/modules/*, ahgRicExplorerPlugin/modules/*_: AtoM plugins organize functionality into multiple Symfony 1.4 modules (iiif, iiifAuth, iiifContent, iiifCollection, mediaSettings, media, threeDReports) with separate action classes and templates; Heratio uses single package with Controllers structure

**Divergent:**

- IIIF Manifest Caching Strategy: Heratio uses IiifViewerService with per-culture cache invalidation; AtoM checks cache on every request without culture awareness (lines 146-152 in actions.class.php vs. Heratio's service-level TTL)
- 3D Model Deprecation Path: Heratio ThreeDThumbnailService is native (15KB); AtoM lib/Services/ThreeDThumbnailService.php is a deprecated stub (22 lines) pointing to Heratio class via class_alias, showing migration mid-flight
- TripoSR Configuration: Heratio stores settings in viewer_3d_settings table (local/remote API URL, timeout, bake_texture flags); AtoM uses same triposr_schema.sql table but Heratio's TriposrImportService is the actual import handler, not TripoSRService
- RiC Sync Event Handling: Heratio RicEntityService dispatches FusekiSyncService per create/update/delete with gates (fuseki_sync_enabled, fuseki_sync_on_save, fuseki_cascade_delete); AtoM RicSyncListener is a static Symfony event handler with hardcoded entity types (lines 23-29)
- Database Table Schema Divergence: Heratio iiif_ocr_text/iiif_ocr_block/ahg_iiif_workspace absent from AtoM; AtoM iiif_annotation/iiif_annotation_body present in Heratio but no NER integration; ric_* tables in both but Heratio has additional ric_* entity tables for RIC-O native storage

## search-discovery — 65%

Search-discovery: 65% parity. PSIS gaps: ResourceSync endpoint (high), search analytics/CTR (high), vector+RRF blending (high), 6 PSIS facets (high), Z3950 server mode (high), PageIndex LLM extraction (high), plus 7 medium/low. Heratio gaps: federated search (high), OAI harvest (high), VIAF/Wikidata (high), plus 5 medium/low. Architectural difference: Heratio is modern Laravel + vector search + analytics; AtoM is mature Symfony 1.4 + federated/peer infrastructure. Both support multi-strategy discovery (keyword/entity/hierarchical/vector/image) but with different fusion (RRF vs weights). Heratio covers cutting-edge discovery, AtoM covers distributed infrastructure.

**PSIS missing (Heratio-only):**

- **[high]** ResourceSync 1.1 Source endpoint — _ahg-resourcesync → none_: Heratio ResourceSyncController /.well-known/resourcesync + /resourcesync/*.xml; AtoM has no ResourceSync plugin
- **[high]** Search analytics dashboard with CTR tracking — _ahg-search → ahgSearchPlugin_: Heratio SearchAnalyticsService + ahg_search_query_log.click_position + analyticsDashboard; AtoM lacks dashboard
- **[high]** Vector search with Qdrant + RRF blending — _ahg-search → ahgSearchPlugin_: Heratio VectorSearchService + BlendedSearchService with RRF formula; AtoM delegates to semantic plugin
- **[high]** Faceted search with 6 PSIS-parity facets — _ahg-search → ahgSearchPlugin_: Heratio buildActiveFilters() handles languages/places/subjects/genres/names/collection; SearchService.php lacks facet aggs
- **[high]** Z3950 server mode (incoming client queries) — _ahg-z3950 → ahgLibraryPlugin_: Heratio Z3950ServerService + Z3950ServerCommand; AtoM Z3950Service is client-only
- **[high]** PageIndex LLM hierarchical tree extraction — _ahg-discovery → ahgDiscoveryPlugin_: Heratio PageIndexService + OllamaPageIndexClient + ahg_pageindex_tree table; AtoM PageIndexMigration incomplete
- **[medium]** Search suggestion (Did you mean) — _ahg-search → ahgSearchPlugin_: Heratio elasticsearch.suggest() at <5 result threshold; AtoM has no suggestion
- **[medium]** Advanced search form with filter UI — _ahg-search → ahgSearchPlugin_: Heratio /search/advanced route; AtoM has indexAction only
- **[medium]** Cursor-based pagination — _ahg-search → ahgSearchPlugin_: Heratio ?cursor= param alongside ?page=N; AtoM uses offset only
- **[medium]** Geospatial search filtering — _ahg-search → ahgSearchPlugin_: Heratio geo={center,radius} or geo={box}; AtoM lacks geo filtering
- **[medium]** SRU server endpoint controller — _ahg-z3950 → ahgLibraryPlugin_: Heratio SruController exposes endpoint; AtoM has SruService but no controller
- **[medium]** Discovery simulated query corpus — _ahg-discovery → ahgDiscoveryPlugin_: Heratio DiscoverySimulatedQueryService generates 4 query types; AtoM lacks test harness
- **[low]** Discovery log pruning command — _ahg-discovery → ahgDiscoveryPlugin_: Heratio php artisan ahg:discovery-prune with --keep-days; AtoM has no cleanup

**PSIS-only (Heratio missing):**

- [high] Federated search across multiple peers — _ahgFederationPlugin_: AtoM FederatedSearchService + federation module for peer discovery/queries; Heratio has no peer capability
- [high] OAI harvesting client (inbound) — _ahgFederationPlugin_: AtoM HarvestService + HarvestClient; Heratio ahg-oai is provider-only
- [high] VIAF/Wikidata actor linking — _ahgSemanticSearchPlugin_: AtoM ViafLinkingService + WikidataActorLinkingService; Heratio lacks authority linking
- [medium] WordNet sync via Datamuse — _ahgSemanticSearchPlugin_: AtoM WordNetSyncService with rate limiting; Heratio doesn't expose
- [medium] Wikidata SPARQL sync — _ahgSemanticSearchPlugin_: AtoM WikidataSyncService; Heratio lacks
- [low] Query expansion testing UI — _ahgSemanticSearchPlugin_: AtoM testExpand action for debugging; Heratio has no public test endpoint
- [low] Search template management — _ahgSemanticSearchPlugin_: AtoM adminTemplates/adminTemplateEdit for reusable templates; Heratio lacks
- [high] MARC21 decoding for Z3950 records — _ahgLibraryPlugin_: AtoM Marc21DecoderService converts to library_biblio_*; Heratio integration unclear

**Divergent:**

- OAI-PMH role: Heratio: provider endpoint (OaiPmhController). AtoM: harvester via OaiPmhConnector in ahgFederationPlugin
- Result merging formula: Heratio: RRF (Reciprocal Rank Fusion). AtoM Discovery: configurable weights for keyword/entity/hierarchical
- Query expansion mechanism: Heratio: embedding-based via Ollama. AtoM: synonym-based via ahg_thesaurus_synonym
- Analytics implementation: Heratio: full trackClick POST + CTR calculation. AtoM: log only, no click tracking

## ingest-preservation — 65%

PSIS/AtoM achieves ~65% feature parity with Heratio reference implementation across the ingest-preservation domain. AtoM strengths: comprehensive preservation format management (conversion, identification, migration planning via MigrationPathwayService), replication to external preservation targets (rsync/S3/Azure), virus scanning (ClamAV), PRONOM sync. Critical AtoM gaps: NO OCFL support (blocks modern archival storage standardization), NO watched-folder streaming ingest (forces manual batch-only workflow), format conversion not operationalized (schema exists but no execution commands). Heratio strengths: OCFL v1.1 with embedded metadata extension and PII gating, streaming ingest pipeline (watched folders), unified data migration. Heratio gaps: format conversion commands missing (tables only), no virus scanning, no format migration planning. Both adequately support fixity scheduling, PREMIS logging, and backup replication. The absence of OCFL in PSIS/AtoM is the single largest parity blocker for adoption of modern archival preservation standards.

**PSIS missing (Heratio-only):**

- **[high]** OCFL (Oxford Common File Layout) storage implementation — _ahg-ocfl → MISSING - no ahgOcflPlugin_: Heratio has complete OCFL v1.1 implementation with OcflInitCommand, OcflVerifyCommand, OcflIngestCommand, OcflExportCommand, plus StorageRoot, OcflObject, Version, Inventory, and EmbeddedMetadataExtension classes. AtoM/PSIS has zero OCFL support.
- **[high]** Watched-folder streaming ingest pipeline — _ahg-scan → MISSING - no ahgScanPlugin_: Heratio has ScanWatchCommand, ScanProcessCommand, WatchedFolderService for continuous folder monitoring with auto-commit. AtoM lacks this; only manual batch ingest via wizard.
- **[medium]** Format conversion/normalization execution commands — _ahg-preservation → ahgPreservationPlugin_: AtoM has preservationConvertTask + convertFormat() in PreservationService supporting ImageMagick/FFmpeg/Ghostscript/LibreOffice. Heratio has preservation_format_conversion schema table but NO console command/service to execute conversions.
- **[medium]** Format identification commands (Siegfried/DROID) — _ahg-preservation → ahgPreservationPlugin_: AtoM has preservationIdentifyTask with batch identification, reidentify, confidence levels, logging. Heratio has PronomIdentificationService but no dedicated console command for identification execution.
- **[medium]** Digital object file replication to preservation targets — _ahg-preservation → ahgPreservationPlugin_: AtoM has preservationReplicateTask with incremental/full sync to rsync/S3/Azure targets. Heratio ReplicateBackupCommand only replicates database backups, not digital object files.
- **[low]** Virus scanning task (ClamAV integration) — _MISSING → ahgPreservationPlugin_: AtoM has preservationVirusScanTask with ClamAV daemon, quarantine, status reporting. Heratio has process_virus_scan flag in schema but no implementation.
- **[low]** Format migration planning services — _MISSING → ahgPreservationPlugin_: AtoM has preservationMigrationTask with MigrationPlanService, MigrationPathwayService, obsolescence reporting, risk assessment. Heratio has no migration planning infrastructure.
- **[low]** PRONOM signature sync scheduler — _MISSING → ahgPreservationPlugin_: AtoM has preservationPronomSyncTask for automated PRONOM registry refresh. Heratio has static seed data, no automated sync.

**PSIS-only (Heratio missing):**

- [medium] Sector-specific CSV import tasks (museum/archive/library/gallery/dam) — _ahgDataMigrationPlugin_: AtoM has museumCsvImportTask, archivesCsvImportTask, libraryCsvImportTask, galleryCsvImportTask, damCsvImportTask. Heratio uses unified DataMigrationService.php instead of separate sector-specific tasks.

**Divergent:**

- Fixity verification architecture: AtoM: scheduled via preservationFixityTask with auto-repair capability. Heratio: RunFixitySchedulesCommand in ahg-preservation + FixityService in ahg-integrity; two-package architecture.
- Database backup replication scope: AtoM preservationReplicateTask: handles both database AND digital object replication. Heratio ReplicateBackupCommand: database backups only, not object files.
- OAIS packaging design: Heratio: OCFL-based storage via ahg-ocfl. AtoM: database-driven with replication targets for AIP persistence.

## accounting-collection — 68%

Heratio accounting-collection achieves 68% parity with PSIS/AtoM. Major gaps: (1) IPSAS CLI reporting (medium), (2) Insurance/Impairment service methods (high), (3) Heritage Accounting plugin entirely missing (high), (4) AI provenance inference (medium), (5) Vendor CRUD service (medium). Database schemas identical. Spectrum and Loan near-parity.

**PSIS missing (Heratio-only):**

- **[high]** Create Insurance Policy — _ahg-ipsas → ahgIPSASPlugin_: AtoM IPSASService.createInsurance(); Heratio method missing.
- **[high]** Create Impairment Assessment — _ahg-ipsas → ahgIPSASPlugin_: AtoM IPSASService.createImpairment(); Heratio method missing.
- **[high]** Heritage Accounting Multi-Standard — _N/A → ahgHeritageAccountingPlugin_: AtoM has entire plugin; no Heratio equivalent.
- **[medium]** IPSAS Report Command — _ahg-ipsas → ahgIPSASPlugin_: AtoM ReportCommand.php generates reports; Heratio lacks CLI command.
- **[medium]** Provenance AI Inference — _ahg-provenance → ahgProvenancePlugin_: AtoM InferenceService; Heratio lacks AI.
- **[medium]** Vendor CRUD Service — _ahg-vendor → ahgVendorPlugin_: AtoM VendorService CRUD; Heratio raw DB queries.

**Divergent:**

- Financial Year Summary: Heratio: 5 fields. AtoM: 20+.
- Vendor Service: Heratio: Status-only. AtoM: Full CRUD.
- Condition Annotation: Heratio: Basic. AtoM: JSON detail.

## rights-privacy-compliance — 72%

Heratio achieves 72% functional parity with PSIS/AtoM rights-privacy-compliance domain. Core databases and service layers map well (rights, embargo, privacy, security_clearance, audit_trail all present in both). However, Heratio introduces significant UX and security improvements: WebAuthn/FIDO2 passpkeys (high), visual redaction editor for image/PDF privacy (high), cryptographic audit trail chaining (high), and structured GDPR Article 30 export (medium). Enhanced PII scanning (embedded metadata backfill), retention schedule + multi-stage disposal workflow, and per-role MFA policy enforcement are also Heratio-only. AtoM maintains some legacy backward-compat (privacy_breach_incident table) and tighter AI integration (NER-based PII detection via ahgAIPlugin) which Heratio does not yet expose. Nine packages align 1:1 with plugins; NARSSA transfer tracking identical at schema level. Estimated effort to close gaps: WebAuthn ~2-3 weeks, visual redaction ~3-4 weeks, chained audit verification ~2 weeks, disposal workflow UI ~3 weeks.

**PSIS missing (Heratio-only):**

- **[high]** WebAuthn/FIDO2 passkey MFA (issue #721) — _ahg-security-clearance → ahgSecurityClearancePlugin_: Heratio has WebAuthnService with full FIDO2/passkey support; AtoM plugin only has TOTP and basic 2FA - ahg_webauthn_credential table and WebAuthnService.php only exist in Heratio
- **[high]** Visual redaction editor for privacy (images/PDF) — _ahg-privacy → ahgPrivacyPlugin_: Heratio has visual-redaction-editor.blade.php and VisualRedactionService; AtoM has only PDF/text redaction - no pixel-level image redaction UI
- **[high]** Embedded metadata PII scanning — _ahg-privacy → ahgPrivacyPlugin_: Heratio has ScanEmbeddedBackfillCommand, ScanIoCommand, EmbeddedMetadataPiiService; AtoM has privacyScanEmbeddedTask but weaker implementation without backfill
- **[high]** Audit trail chaining (cryptographic verification) — _ahg-audit-trail → ahgAuditTrailPlugin_: Heratio has ChainedAuditWriter with install-chain.sql, VerifyChainCommand; AtoM plugin lacks blockchain/chain verification - no cryptographic integrity
- **[high]** Retention schedule + disposal workflow (records management) — _ahg-extended-rights → ahgExtendedRightsPlugin_: Heratio has retention_schedule, retention_assignment, disposal_action tables (2026-05-17 migration) with multi-stage signoff; AtoM plugin migration file exists but lacks full workflow UI
- **[medium]** Article 30 ROPA export (GDPR compliance) — _ahg-privacy → ahgPrivacyPlugin_: Heratio has Article30ExportCommand and Article30Service with full GDPR ROPA export; AtoM plugin lacks dedicated Article 30 export feature
- **[medium]** DPIA with risk scoring — _ahg-privacy → ahgPrivacyPlugin_: Heratio has DpiaController + DpiaService with structured risk assessment; AtoM plugin has dpia-create/edit views but minimal backend
- **[medium]** MFA policy enforcement per role (issue #738) — _ahg-security-clearance → ahgSecurityClearancePlugin_: Heratio has MfaPolicyController, MfaPolicyService, EnforceMfaPolicy middleware; AtoM plugin lacks per-role MFA policy configuration
- **[medium]** Watermark application to derivatives (security classification) — _ahg-security-clearance → ahgSecurityClearancePlugin_: Heratio has WatermarkService, WatermarkApplyDerivativesCommand; AtoM plugin has task but less sophisticated watermarking
- **[medium]** PII scan & redaction review workflow — _ahg-privacy → ahgPrivacyPlugin_: Heratio has pii-review.blade.php, pii-scan-object.blade.php; AtoM has basic scan but no review/redaction UI workflow

**PSIS-only (Heratio missing):**

- [low] Dual embargo/rights_embargo table consolidation option — _ahgExtendedRightsPlugin_: AtoM maintains separate embargo and rights_embargo tables with fallback logic in lib/Services/EmbargoService.php; Heratio unified to embargo table only
- [low] Legacy privacy_breach_incident table (backward compat) — _ahgPrivacyPlugin_: AtoM keeps privacy_breach_incident table in install.sql for legacy intake; Heratio moved fully to privacy_breach table

**Divergent:**

- PII detection approach: Heratio uses pure regex + Luhn validation (PiiScanService.php, offline); AtoM integrates with ahgAIPlugin for NER-based entity extraction (PiiDetectionService.php leverages LLM)
- Security classification inheritance: Heratio has inherit_to_children flag in object_security_classification table; AtoM plugin propagates via filter logic but no explicit table flag
- Embargo access exception granularity: Heratio embargo_exception table supports user/group/ip_range/repository exception types; AtoM uses simpler access_exceptions with weaker type enforcement
- NARSSA transfer workflow: Heratio narssa_transfer.status uses draft→packaged→transmitted→accepted→rejected; AtoM plugin uses similar but lacks intermediate packaging state visibility

## research-public — 72%

Heratio implements 72% of PSIS/AtoM research-public functionality. Core gaps are knowledge graph assertions, conflict detection, advanced snapshot comparison, custody handoff tracking, request lifecycle management (SLA/triage), and institutional sharing. AtoM research plugin is significantly more feature-complete with 48 services vs Heratio's 20 research-domain services. Heratio strengths: modern Laravel architecture, marketplace integration, cleaner separation of concerns (Services vs Actions). AtoM strengths: comprehensive knowledge management (assertions, hypotheses, snapshots), request/custody workflows, real-time collaboration, and broader institutional controls. High-severity gaps in reproducibility packages (RO-Crate), extraction orchestration, and material request lifecycle would require substantial new services in Heratio to reach parity.

**PSIS missing (Heratio-only):**

- **[high]** Knowledge Graph Assertions & Evidence Tracking — _ahg-research → ahgResearchPlugin_: AssertionService.php in AtoM with createAssertion(), addEvidence(), viewAssertion() methods; 9 usages in research actions. Heratio has zero assertion functionality despite having assertion-related classes in ahg-c2pa.
- **[high]** Conflict Detection & Resolution for Assertions — _ahg-research → ahgResearchPlugin_: AssertionConflicts action in AtoM (modules/research/actions/); identifies contradictory claims in knowledge graph. Not present in Heratio ResearchController.
- **[high]** Snapshot Comparison & Versioning — _ahg-research → ahgResearchPlugin_: CreateSnapshot, ViewSnapshot, CompareSnapshots, DeleteSnapshot actions in AtoM. Heratio has viewSnapshot but missing createSnapshot and compareSnapshots methods.
- **[high]** Reproducibility Packages (RO-Crate, BagIt) — _ahg-research → ahgResearchPlugin_: ReproducibilityPack, RoCrateService, SnapshotSearchResults actions in AtoM; RoCrateService generates ResearchObject Crate bundles. Heratio ReproductionService exists but lacks RO-Crate generation.
- **[high]** Extraction Job Orchestration & Validation — _ahg-research → ahgResearchPlugin_: ExtractionJobs, CreateExtractionJob, ViewExtractionJob, BulkValidate actions in AtoM; ExtractionOrchestrationService. Heratio extractionJobs and validationQueue exist but lack orchestration service and bulk validation.
- **[high]** Custody Handoff & Material Request Lifecycle — _ahg-research → ahgResearchPlugin_: CustodyCheckout, CustodyCheckin, CustodyConfirm, CustodyReturnVerify, CustodyChain, BatchCheckout, BatchReturn actions in AtoM; CustodyHandoffService (1 usage), 003_custody_handoff.sql. Heratio has checkIn/checkOut but no custody chain tracking, batch operations, or handoff service.
- **[high]** Request Lifecycle Management (SLA, Triage, Correspondence) — _ahg-research → ahgResearchPlugin_: RequestTriage, RequestAssign, RequestCorrespond, RequestClose, RequestSla actions in AtoM; RequestLifecycleService, 002_request_lifecycle.sql. Heratio has no request lifecycle management; ahg-request-publish only handles publication requests, not material requests.
- **[medium]** Timeline Builder & Event API — _ahg-research → ahgResearchPlugin_: TimelineBuilder, TimelineEventApi, TimelineData actions in AtoM; TimelineService in lib/Services/. Heratio timelineBuilder controller method exists but no dedicated TimelineService or timeline event CRUD.
- **[medium]** Map Builder & Geospatial Visualization — _ahg-research → ahgResearchPlugin_: MapBuilder, MapPointApi, MapData, MapService in AtoM. Heratio mapBuilder method exists but no MapService or point/data API.
- **[medium]** Network Graph Visualization & Export — _ahg-research → ahgResearchPlugin_: NetworkGraph, NetworkGraphData, ExportGraphGEXF, ExportGraphML actions in AtoM; GraphService. Heratio networkGraph method present but no GraphService, GEXF/GraphML export.
- **[medium]** Web Annotation Protocol (WAP) Compliance — _ahg-annotations → ahgResearchPlugin_: WebAnnotationService in AtoM (7 usages); full W3C WAP header support (Content-Type, Link, Accept-Post, Vary, Allow), Prefer header handling. Heratio AnnotationsController has basic W3C support but lacks full WAP compliance headers.
- **[medium]** Hypothesis Creation & Management — _ahg-research → ahgResearchPlugin_: ViewHypothesis, UpdateHypothesis actions in AtoM; HypothesisService (4 usages). Heratio has no hypothesis functionality.
- **[medium]** Institutional Sharing & Access Control — _ahg-research → ahgResearchPlugin_: InstitutionalShareService, AcceptShare action in AtoM. Heratio has no institutional sharing or role-based folder access.
- **[medium]** Favorites Folder Sharing & Import/Export — _ahg-favorites → ahgFavoritesPlugin_: FavoritesImportService, FavoritesShareService, ShareFolderAction, RevokeSharingAction in AtoM. Heratio FavoritesService only covers basic CRUD; lacks folder sharing, import, and share revocation.
- **[medium]** Publication Request State Machine & Workflow — _ahg-request-publish → ahgRequestToPublishPlugin_: RequestToPublishService in AtoM with full CRUD and status workflows (draft, submitted, approved, published, rejected). Heratio RequestPublishController exists but ahg-request-publish/src/Services/ is empty (readme.md only).
- **[medium]** Manuscript Submission & Author Workflow — _ahg-researcher-manage → ahgResearcherPlugin_: SubmissionService, ExchangeImportService, PublishService in AtoM. Heratio ahg-researcher-manage has only ResearcherSubmissionController (12 methods) without dedicated submission/publish services.
- **[medium]** Collaboration Real-time Panel & Comments — _ahg-research → ahgResearchPlugin_: CollabPanel, CollabComment, CollabCommentResolve, CollabJoin, CollabPoll, CommentApi, CommentService actions in AtoM. Heratio CollaborationService exists but lacks comment thread, poll, and real-time panel actions.
- **[medium]** Compliance Dashboard & Ethics Milestones — _ahg-research → ahgResearchPlugin_: ComplianceDashboard, EthicsMilestones actions in AtoM. Heratio has no compliance or ethics tracking.
- **[medium]** Seat Booking & Equipment Management with History — _ahg-research → ahgResearchPlugin_: SeatMap, AssignSeat, BookEquipment, EquipmentService (3 usages), SeatService (4 usages) in AtoM. Heratio has book, viewBooking, checkIn/checkOut but lacks equipment service, seat assignment, and equipment history tracking.
- **[medium]** Retrieval Queue & Walk-In Management — _ahg-research → ahgResearchPlugin_: RetrievalQueue, WalkIn, RetrievalService (3 usages) in AtoM. Heratio retrievalQueue and walkIn methods exist but no RetrievalService for queue management.
- **[low]** Trust Scoring & Peer Review — _ahg-research → ahgResearchPlugin_: TrustScore, PeerReviewService (5 usages) in AtoM. Heratio has no peer review or trust scoring system.
- **[low]** Analytics & Statistics Dashboard — _ahg-research → ahgResearchPlugin_: Analytics, AdminStatistics, StatisticsService actions in AtoM. Heratio has adminStatistics but no StatisticsService backend.
- **[low]** Offline Sync & Device Storage — _ahg-research → ahgResearchPlugin_: OfflineSyncService in AtoM. Heratio has no offline sync capability.

**PSIS-only (Heratio missing):**

- [high] Password Reset & Account Recovery Workflow — _ahgResearchPlugin_: PasswordResetRequest, PasswordReset, AdminResetPassword actions in AtoM. These methods exist in AtoM ResearchActions but are NOT in Heratio ResearchController; likely delegated to app-wide auth instead.

**Divergent:**

- Annotation Storage & W3C Web Annotation Compliance: Heratio AnnotationsController implements W3C Web Annotation Protocol but AtoM WebAnnotationService appears to be an additional wrapper layer; both support IIIF/Mirador but differ in response shape normalization.
- Cart & Ecommerce Integration: Both have CartService and EcommerceService, but Heratio CartController adds marketplace listing checkout (PayFast integration) which AtoM cart actions don't expose; Heratio more modern.
- Research Journal & Notebook: Both have journal functionality; Heratio ResearchJournalService, AtoM JournalService; both support import/export but Heratio view structure may differ.
- Target Journal Directory: Both implement target journal seeding; Heratio ResearchTargetJournalService focuses on DHET accreditation; AtoM TargetJournalService (6 usages) similar but integration may differ.
- Feedback Form Submission: Heratio FeedbackController stores feedback directly in feedback/feedback_i18n tables; AtoM ahgFeedbackPlugin has only Actions, no Service layer. Heratio more structured.

## workflow-reporting-misc — 72%

12 Heratio <-> AtoM plugin pairs audited across workflow-reporting-misc domain. Observability is entirely missing from PSIS (whole ahg-observability package absent). Workflow domain critically lacks SLA policy enforcement, mailing infrastructure, and scheduled notifications. Reports/statistics have similar coverage but ReportBuilder less comprehensive on AtoM. Version control, multi-tenant, custom fields, forms, GIS, and dedupe implementations substantially aligned. Heritage-Manage and Forms packages weaker on AtoM. Estimated parity 72% for PSIS coverage of Heratio functionality in this domain.

**PSIS missing (Heratio-only):**

- **[high]** Observability infrastructure (Prometheus metrics, tracing, APCu/Redis metrics storage) — _ahg-observability → none_: ahg-observability has 15 .php files including MetricsRegistry, TracerProvider, Trace, Prometheus middleware; no corresponding plugin exists in atom-ahg-plugins
- **[high]** SLA policies with escalation actions and warning thresholds — _ahg-workflow → ahgWorkflowPlugin_: Heratio ahg_workflow_sla_policy table with warning_days/due_days; AtoM has WorkflowSlaService but minimal implementation vs Heratio's integration with WorkflowService
- **[medium]** Mailable notification classes for workflow tasks (Approved, Rejected, Overdue) — _ahg-workflow → ahgWorkflowPlugin_: Heratio has 3 Mail classes: WorkflowTaskApprovedMail, WorkflowTaskRejectedMail, WorkflowTaskOverdueMail; AtoM plugin lacks mailing infrastructure
- **[medium]** Console command: WorkflowNotifyOverdueCommand for scheduled overdue notifications — _ahg-workflow → ahgWorkflowPlugin_: Heratio has WorkflowNotifyOverdueCommand; AtoM only has StatusCommand and ProcessCommand
- **[medium]** ReportBuilder service layer with template/section/binding management — _ahg-reports → ahgReportBuilderPlugin_: Heratio has ReportBuilderController with create/store/update; AtoM ReportBuilderPlugin has services but less comprehensive
- **[medium]** Heritage admin layer (contributor marketplace, analytics, embargoes) — _ahg-heritage-manage → ahgHeritagePlugin_: Heratio ahg-heritage-manage has views for contributor login, access requests, analytics alerts, embargoes; AtoM HeritagePlugin has heritage module but lacks full admin layer
- **[low]** Form console commands (ImportCommand, ExportCommand, ListCommand) — _ahg-forms → ahgFormsPlugin_: AtoM has 3 CLI commands; Heratio ahg-forms has no Console directory

**PSIS-only (Heratio missing):**

- [medium] WorkflowBulkService with bulk transition/assign/note/priority operations — _ahgWorkflowPlugin_: AtoM ahgWorkflowPlugin/lib/Services/WorkflowBulkService.php with bulkTransition/bulkAssign methods; Heratio WorkflowService lacks dedicated bulk operations layer

**Divergent:**

- Landing page versioning: AtoM LandingPageService includes getPageVersions method and version history tracking; Heratio LandingPageService focuses on block management without explicit version layer
- Custom field rendering and UI separation: AtoM CustomFieldRenderService is separate from CustomFieldService; Heratio combines business logic in single CustomFieldService
- Statistics configuration storage: AtoM stores config in ahg_statistics_config table with setting_type (string/integer/json); Heratio statistics config approach not explicitly found

## api-integration — 75%

The api-integration domain shows 75% parity between Heratio and PSIS/AtoM. Heratio excels in modern API design patterns (OpenAPI specs, idempotency, ETags, rate-limit headers) and introduces Europeana EDM export, marketplace REST endpoints, and EXIF/IPTC metadata aggregation — all absent from PSIS. However, AtoM's GraphQL implementation is more mature (using graphql-core library with proper schema builders and security analyzers), and provides better SharePoint integration via PostIngestHookService. The core REST v2 API endpoints (descriptions, authorities, assets, webhooks, sync, privacy, publish) are functional in both systems with comparable coverage. The critical gaps are: (1) Heratio's OpenAPI/Idempotency/ETag features are enterprise-grade but missing from AtoM; (2) AtoM's GraphQL properly uses a schema library vs Heratio's regex-based hand-parsing; (3) Marketplace API is partially wired only in Heratio; (4) Europeana export is Heratio-only. The remaining 25% gap is split between missing advanced Heratio features in PSIS (10-12%) and some AtoM-specific enhancements like GraphQL security rules (8-10%).

**PSIS missing (Heratio-only):**

- **[high]** OpenAPI 3.1 Specification Generation — _ahg-api/src/Services/OpenApiGenerator.php → ahgAPIPlugin_: Heratio has reflective OpenAPI spec generation (line 40+), AtoM has no OpenApiGenerator equivalent; no OpenAPI generation found in ahgAPIPlugin
- **[high]** Idempotency-Key Request Deduplication (RFC 7231 + custom) — _ahg-api/src/Middleware/IdempotencyKeyMiddleware.php → ahgAPIPlugin_: Heratio middleware caches POST/PUT/PATCH responses by client-provided Idempotency-Key header; no idempotency support found in AtoM
- **[high]** ETag Conditional Request Support — _ahg-api/src/Middleware/ETagMiddleware.php → ahgAPIPlugin_: Heratio generates ETag headers on GET responses and honours If-None-Match (304 Not Modified); no ETag handling in AtoM API
- **[medium]** Marketplace REST API Endpoints — _ahg-api/routes/api.php:234-239 → ahgAPIPlugin/modules/apiv2_: Heratio exposes marketplace endpoints (search, bid, auction status, favourite, currencies, categories) in REST API v2; AtoM has marketplace actions (apiSearchAction, apiBidAction, etc.) but they are not wired into the apiv2 REST module routing
- **[medium]** Marketplace Service & Payment Integration — _ahg-api/src/Controllers/V2/MarketplaceController.php → ahgAPIPlugin_: Heratio has dedicated MarketplaceController with domain logic; AtoM has marketplace plugin but no corresponding API integration layer
- **[medium]** Europeana EDM RDF/XML Export Command — _ahg-federation/src/Console/EuropeanaExportCommand.php + src/Edm/EdmSerializer.php → ahgFederationPlugin_: Heratio console command (php artisan europeana:export) serializes records to Europeana EDM 1.0 RDF/XML; AtoM has no Europeana export task
- **[medium]** API Rate Limiting Headers (X-RateLimit-*) — _ahg-api/src/Middleware/ApiRateLimit.php:50-51 → ahgAPIPlugin_: Heratio returns X-RateLimit-Limit and X-RateLimit-Remaining headers; no such headers implemented in AtoM
- **[medium]** Digital Object Embedded Metadata (EXIF/IPTC/ffprobe) — _ahg-api/src/Services/EmbeddedMetadataService.php → ahgAPIPlugin_: Heratio consolidates EXIF, IPTC, and ffprobe metadata from three tables into a single REST response field; AtoM has no service for this aggregation
- **[medium]** Batch Operation Support (max 100 ops) — _ahg-api/src/Controllers/V2/BatchController.php → ahgAPIPlugin/modules/apiv2/actions/batchAction.class.php_: Heratio batch endpoint supports create/update/delete on description entities; AtoM batchAction is present but feature parity unclear and integration incomplete
- **[low]** ISBN/Barcode Lookup Integration — _ahg-api/src/Services/IsbnLookupService.php → ahgAPIPlugin_: Heratio has dedicated ISBN/barcode lookup service using Open Library and Google Books APIs; not present in AtoM
- **[low]** SharePoint Post-Ingest Hooks — _ahg-sharepoint/src/Services (no PostIngestHookService) → ahgSharePointPlugin/lib/Services/PostIngestHookService.php_: AtoM has SharePointPostIngestHookService; Heratio lacks equivalent
- **[low]** GraphQL Schema Introspection & Playground — _ahg-graphql/src/Controllers/GraphqlController.php (line 116-121 manual schema via __schema query) → ahgGraphQLPlugin/lib/GraphQL/Schema/SchemaBuilder.php_: Heratio GraphQL controller provides only manual regex-based query parsing and schema; AtoM has full GraphQL-core library schema builder with proper introspection support

**PSIS-only (Heratio missing):**

- [medium] Full GraphQL Schema Builder with Type System — _ahgGraphQLPlugin/lib/GraphQL/Schema/SchemaBuilder.php + Types/*_: AtoM uses graphql-core library with proper ObjectType/InterfaceType/ConnectionTypes; Heratio GraphqlController hand-parses queries with regex patterns, no schema builder
- [low] GraphQL Query Complexity & Depth Analysis — _ahgGraphQLPlugin/lib/GraphQL/Security/ComplexityAnalyzer.php + DepthLimitRule.php_: AtoM enforces query complexity limits and depth restrictions; Heratio has no equivalent security layer
- [low] Spectrum Collections Events Feed (timeline) — _ahgSpectrumPlugin (inferred from SpectrumApiController reference)_: Heratio SpectrumApiController exists but is partially implemented; AtoM Spectrum integration is complete

**Divergent:**

- GraphQL Query Resolution Strategy: Heratio uses manual regex pattern matching for simple queries (line 73+); AtoM uses standard GraphQL query parser with proper schema validation and nested resolution
- Idempotency Cache TTL: Heratio uses 24-hour sliding window per (user_id, key, route); AtoM (if implemented) would likely use shorter windows for PSIS audit trails
- SharePoint Ingest Event Handling: Heratio has SharePointIngestEventCommand (console-driven); AtoM uses both tasks and PostIngestHookService for more granular integration points
- Federation Harvest & OAI-PMH Support: Both have HarvestService and HarvestClient; Heratio has additional FederatedSearchService for peer searches, AtoM has OaiPmhConnector + PeerConnector for multi-protocol support

## core-ui — 92%

Heratio core-ui achieves 92% functional parity with PSIS/AtoM. All major UI features present: ACL (11 permission types), Display (collection types, facets), Settings (88 config pages), Menus, Static pages, Help, Jobs. Key gaps: (1) AtoM's ahgUiOverridesPlugin allows per-repository theme—Heratio global-only; (2) Heratio dropdown-manage is standalone with better multi-source editor—AtoM embeds in settings; (3) AtoM has Elasticsearch search service—Heratio uses DB. Architecturally divergent (single-controller vs modular handlers, explicit routes vs query params) but functionally equivalent. All 10 domain packages have counterparts with schema successfully ported and migrations idempotent.

**PSIS missing (Heratio-only):**

- **[medium]** Inline dropdown manager with multi-source support (ahg_dropdown, term, setting) — _ahg-dropdown-manage → ahgSettingsPlugin/ahgDropdown_: Heratio has standalone package with DropdownController dispatching 'ahg_dropdown', 'term', 'setting' sources. AtoM ahgDropdown embedded in settings, limited multi-source editor.
- **[medium]** Layout service with culture-aware menu data aggregation — _ahg-theme-b5 → ahgThemeB5Plugin_: Heratio ThemeService::getLayoutData() aggregates menus, user, plugins, branding in one call. AtoM has 351 scattered template files, no unified service.
- **[low]** Help article full-text search with FULLTEXT indices — _ahg-help → ahgHelpPlugin_: Heratio help_article table has FULLTEXT index on (title, body_text). AtoM lacks FULLTEXT indices, uses LIKE queries.
- **[low]** Job management CSV export and multi-status filtering — _ahg-jobs-manage → ahgJobsManagePlugin_: Heratio JobController::download() exports CSV. AtoM jobsManage has limited filtering.

**PSIS-only (Heratio missing):**

- [medium] Per-repository theme customization via editThemeAction — _ahgUiOverridesPlugin_: AtoM provides per-repository override. Heratio theming is global-only.
- [medium] Elasticsearch integration service for search optimization — _ahgCorePlugin_: AtoM ahgCorePlugin includes ElasticsearchService.php. Heratio uses pure DB search.
- [low] Event sourcing services (EventService, RelationService, TermRelationService) — _ahgCorePlugin_: AtoM ahgCorePlugin has data graph services. Heratio ahg-core lacks equivalent.

**Divergent:**

- Settings routing architecture: Heratio: 88 methods in single SettingsController. AtoM: 55 handler actions in separate directory. Functionally equivalent, architecturally different.
- Menu CRUD and reordering UX: Heratio: explicit routes (browse/show/store). AtoM: inline ?move=ID&before/after query params. Same functionality, different UX paradigm.
- ACL editing scope patterns: Heratio: entity-scoped routes (editInformationObjectAcl). AtoM: single editXxxAclAction pattern. Equivalent features, different flow.
- Static page CRUD method naming: Identical schema (static_page, static_page_i18n, slug). Different action names but equivalent feature coverage.
