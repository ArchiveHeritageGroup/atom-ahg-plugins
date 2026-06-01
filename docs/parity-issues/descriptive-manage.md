PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** descriptive-manage — current parity ≈ **35%**.

PSIS/AtoM *ManagePlugin coverage is approximately 35% of Heratio's descriptive-manage cluster. Five major packages are completely missing from AtoM: records-manage (7 controllers, 14 services for records management, disposal, retention), heritage-manage (7 controllers, 84 views for public portal), doi-manage, dropdown-manage, and researcher-manage. Within overlapping packages, Heratio's implementations are significantly more sophisticated: information-object-manage has 20 controllers and 12 specialized services (AI, Privacy, Preservation, Condition, Extended Rights) vs AtoM's 1 action module; actor-manage has 10+ specialized authority services (merge, dedup, NER, reconciliation) vs AtoM's basic CRUD; user-manage provides per-object ACL matrices (13 routes) vs basic user admin; and rights-holder-manage implements embargo/orphan-works frameworks missing entirely in AtoM. Storage, Repository, and Donor packages have basic parity but lack advanced workflows. Only DACS/DC/RAD/MODS (metadata standards) are lightweight and achieve near-parity due to their minimal feature scope. The architecture also diverges: Heratio uses instance-based services with locale injection, while AtoM relies on static methods and Symfony 1.4 module routing."

### High-severity gaps (PSIS missing)
- **Records Management (Classification, Disposal, Retention, Email Capture, etc.)** — `ahg-records-manage` → `MISSING`. Heratio provides 7 controllers (Classification, Compliance, Disposal, EmailCapture, FilePlan, Retention, Review) with 14 specialized services (ClassificationRuleService, DisposalWorkflowService, EmailCaptureService, FilePlanImportService, etc.). No AtoM equivalent exists.
- **Heritage Public Portal (Landing page, Search, Timeline, Analytics, Contributors)** — `ahg-heritage-manage` → `MISSING`. Heratio provides 7 controllers (Heritage, HeritageAdmin, HeritageAccounting, ValuationReport, GrapCompliance, OciMovement) with 84 views. No AtoM plugin exists.
- **Advanced Authority Management (Merge, Deduplication, NER Pipeline, Reconciliation)** — `ahg-actor-manage` → `ahgActorManagePlugin`. Heratio has 12 services including AuthorityDedupeService, AuthorityMergeService, AuthorityNerPipelineService, AuthorityReconciliationService. AtoM has only 2 services (ActorBrowseService, ActorCrudService). Missing: deduplication, NER pipeline, reconciliation, identifier management, graph analysis.
- **Accession Advanced Features (Container barcodes, Rights inheritance, Finalisation workflow, Appraisal, Intake Queue)** — `ahg-accession-manage` → `ahgAccessionManagePlugin`. Heratio AccessionService implements: inheritRightsToIo(), finalisationBlockers(), upsertWorkflow(), nextAccessionNumber(), containerBarcodesEnabled(), rightsInheritanceEnabled(). AtoM AccessionCrudService lacks workflow management, rights inheritance, and finalisation gate logic.
- **Information Object Advanced Features (AI Describe, PII Masking, Preservation, Condition, Privacy, Provenance, Extended Rights, Finding Aids, Treeview with Sort, Reports)** — `ahg-information-object-manage` → `ahgInformationObjectManagePlugin`. Heratio has 12 controllers (InformationObject, Condition, DigitalObject, Export, ExtendedRights, FindingAid, Hierarchy, Import, Media, Modifications, Preservation, Privacy, Provenance, etc.) with 12 specialized services (AiNerService, ConditionService, ExtendedRightsService with 28 methods, PreservationService, PiiMaskingService, PrivacyService, ProvenanceService, RedactionRenderService). AtoM has only 1 basic module with 4 basic services missing all advanced features.
- **User ACL Management (Per-object permission matrix, Per-standard access control, Plugin grants/preferences)** — `ahg-user-manage` → `ahgUserManagePlugin`. Heratio provides UserAclController implementing per-object ACL matrices for Actor, InformationObject, Repository, Term with 13 routes for ACL editing. AtoM userManage has only basic CRUD.
- **Rights-Holder Management (Embargoes, Orphan Works, Rights Admin, Extended Rights, TK Labels)** — `ahg-rights-holder-manage` → `ahgRightsHolderManagePlugin`. Heratio has 5 controllers (RightsHolder, RightsAdmin, Rights, Embargo, ExtendedRights) with comprehensive embargo/orphan-works/rights-statement management. AtoM plugin exists but has only 1 action module and 2 services (basic CRUD).

### Medium-severity gaps
- DOI Management — `ahg-doi-manage` → `MISSING`.
- Dropdown/Custom Fields Management — `ahg-dropdown-manage` → `MISSING`.
- Researcher Submissions/Portal — `ahg-researcher-manage` → `MISSING`.
- Storage Location Management (Strongroom CRUD, Holdings Reports, Box Lists, Physical Object Linking) — `ahg-storage-manage` → `ahgStorageManagePlugin`.
- Institution/Repository Advanced Management (Browse, Show, Create/Edit, Publishing, Settings) — `ahg-repository-manage` → `ahgRepositoryManagePlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.