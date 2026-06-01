PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** research-public — current parity ≈ **72%**.

Heratio implements 72% of PSIS/AtoM research-public functionality. Core gaps are knowledge graph assertions, conflict detection, advanced snapshot comparison, custody handoff tracking, request lifecycle management (SLA/triage), and institutional sharing. AtoM research plugin is significantly more feature-complete with 48 services vs Heratio's 20 research-domain services. Heratio strengths: modern Laravel architecture, marketplace integration, cleaner separation of concerns (Services vs Actions). AtoM strengths: comprehensive knowledge management (assertions, hypotheses, snapshots), request/custody workflows, real-time collaboration, and broader institutional controls. High-severity gaps in reproducibility packages (RO-Crate), extraction orchestration, and material request lifecycle would require substantial new services in Heratio to reach parity.

### High-severity gaps (PSIS missing)
- **Knowledge Graph Assertions & Evidence Tracking** — `ahg-research` → `ahgResearchPlugin`. AssertionService.php in AtoM with createAssertion(), addEvidence(), viewAssertion() methods; 9 usages in research actions. Heratio has zero assertion functionality despite having assertion-related classes in ahg-c2pa.
- **Conflict Detection & Resolution for Assertions** — `ahg-research` → `ahgResearchPlugin`. AssertionConflicts action in AtoM (modules/research/actions/); identifies contradictory claims in knowledge graph. Not present in Heratio ResearchController.
- **Snapshot Comparison & Versioning** — `ahg-research` → `ahgResearchPlugin`. CreateSnapshot, ViewSnapshot, CompareSnapshots, DeleteSnapshot actions in AtoM. Heratio has viewSnapshot but missing createSnapshot and compareSnapshots methods.
- **Reproducibility Packages (RO-Crate, BagIt)** — `ahg-research` → `ahgResearchPlugin`. ReproducibilityPack, RoCrateService, SnapshotSearchResults actions in AtoM; RoCrateService generates ResearchObject Crate bundles. Heratio ReproductionService exists but lacks RO-Crate generation.
- **Extraction Job Orchestration & Validation** — `ahg-research` → `ahgResearchPlugin`. ExtractionJobs, CreateExtractionJob, ViewExtractionJob, BulkValidate actions in AtoM; ExtractionOrchestrationService. Heratio extractionJobs and validationQueue exist but lack orchestration service and bulk validation.
- **Custody Handoff & Material Request Lifecycle** — `ahg-research` → `ahgResearchPlugin`. CustodyCheckout, CustodyCheckin, CustodyConfirm, CustodyReturnVerify, CustodyChain, BatchCheckout, BatchReturn actions in AtoM; CustodyHandoffService (1 usage), 003_custody_handoff.sql. Heratio has checkIn/checkOut but no custody chain tracking, batch operations, or handoff service.
- **Request Lifecycle Management (SLA, Triage, Correspondence)** — `ahg-research` → `ahgResearchPlugin`. RequestTriage, RequestAssign, RequestCorrespond, RequestClose, RequestSla actions in AtoM; RequestLifecycleService, 002_request_lifecycle.sql. Heratio has no request lifecycle management; ahg-request-publish only handles publication requests, not material requests.

### Medium-severity gaps
- Timeline Builder & Event API — `ahg-research` → `ahgResearchPlugin`.
- Map Builder & Geospatial Visualization — `ahg-research` → `ahgResearchPlugin`.
- Network Graph Visualization & Export — `ahg-research` → `ahgResearchPlugin`.
- Web Annotation Protocol (WAP) Compliance — `ahg-annotations` → `ahgResearchPlugin`.
- Hypothesis Creation & Management — `ahg-research` → `ahgResearchPlugin`.
- Institutional Sharing & Access Control — `ahg-research` → `ahgResearchPlugin`.
- Favorites Folder Sharing & Import/Export — `ahg-favorites` → `ahgFavoritesPlugin`.
- Publication Request State Machine & Workflow — `ahg-request-publish` → `ahgRequestToPublishPlugin`.
- Manuscript Submission & Author Workflow — `ahg-researcher-manage` → `ahgResearcherPlugin`.
- Collaboration Real-time Panel & Comments — `ahg-research` → `ahgResearchPlugin`.
- Compliance Dashboard & Ethics Milestones — `ahg-research` → `ahgResearchPlugin`.
- Seat Booking & Equipment Management with History — `ahg-research` → `ahgResearchPlugin`.
- Retrieval Queue & Walk-In Management — `ahg-research` → `ahgResearchPlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.