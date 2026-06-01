PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** iiif-3d — current parity ≈ **62%**.

PSIS/AtoM (62% parity) covers core IIIF manifest generation (v2/v3), 3D viewer/thumbnail basics, and RiC sync listening, but is functionally incomplete for production IIIF workflows. Missing are: (1) OCR export in 4 formats (TXT/ALTO/hOCR/PAGE-XML), critical for digital libraries; (2) RFC 8947 content-state + Auth 2.0 flow for viewer interop; (3) Mirador workspace persistence for multi-user workflows; (4) NER->annotation bridging; (5) Change Discovery 1.0; (6) full RiC-O entity serialization + SHACL validation + SPARQL query engine (AtoM has only event listening, no graph query); (7) RDF import with dry-run; (8) CIDOC-CRM export. Heratio's 3 packages (ahg-iiif-collection, ahg-3d-model, ahg-ric) provide enterprise-grade IIIF/3D/RIC functionality with comprehensive tooling (Fuseki integration, Python semantic search, TripoSR orchestration) absent from AtoM. AtoM plugin architecture remains via 8 modules but Heratio's services are substantially richer. Code evidence: Heratio RicEntityService=76KB + RicSerializationService vs. AtoM RicPlace=235 bytes.

### High-severity gaps (PSIS missing)
- **OCR Export (TXT, ALTO, hOCR, PAGE-XML)** — `ahg-iiif-collection/Services/OcrExportService.php` → `ahgIiifPlugin`. Heratio has comprehensive OcrExportService with four export formats (lines 31-51); AtoM plugin has no equivalent service or database tables (iiif_ocr_text, iiif_ocr_block missing from install.sql)
- **IIIF Content State (RFC 8947) + Auth 2.0 Flow** — `ahg-iiif-collection/Services/IiifContentStateService.php + IiifAuthFlow2Service.php` → `ahgIiifPlugin/lib/Services/IiifAuthService.php`. Heratio implements RFC 8947 content state encoding/decoding and Auth 2.0 probe/access/token endpoints (routes web.php lines 68-78); AtoM only has Auth 1.0/2.0 base classes without content-state; no IiifContentStateService equivalent
- **RiC Serialization (full RIC-O JSON-LD -> Turtle)** — `ahg-ric/Services/RicSerializationService.php` → `ahgRicExplorerPlugin`. Heratio RicSerializationService (58KB) transforms entity models to RIC-O JSON-LD -> Turtle for Fuseki; AtoM RicExplorerPlugin has only RicSyncListener + model stubs (RicPlace, RicActivity, RicRule, RicInstantiation are 200-235 bytes each, likely just class_alias shims)
- **SHACL Validation (RiC-O Shapes)** — `ahg-ric/Services/ShaclValidationService.php` → `ahgRicExplorerPlugin`. Heratio ShaclValidationService validates entities against RIC-O SHACL shapes (routes /admin/ric/shacl-validate, /admin/ric/validate/{type}/{id}); AtoM plugin has no SHACL validator, only basic sync queue management
- **RiC Entity CRUD + SPARQL Query Service** — `ahg-ric/Services/RicEntityService.php (76KB) + SparqlQueryService.php (10KB)` → `ahgRicExplorerPlugin/lib/RicSyncListener.class.php`. Heratio has full entity lifecycle with Fuseki sync hooks (dispatchSave, cascadeDelete) + SparqlQueryService wrapping Python ric_semantic_search.py; AtoM RicSyncListener only handles sync events with hardcoded entity list, no query service or semantic search

### Medium-severity gaps
- Mirador Workspace Persistence (Issue #699) — `ahg-iiif-collection/Services/WorkspaceService.php + Controller + routes` → `ahgIiifPlugin`.
- IIIF Metadata Enrichment (IPTC/EXIF/XMP -> Manifest) — `ahg-iiif-collection/Services/IiifMetadataEnricher.php` → `ahgIiifPlugin`.
- NER -> IIIF Annotation Surface (Issue #697) — `ahg-iiif-collection/Controllers/IiifNerAnnotationsController.php + Job` → `ahgIiifPlugin`.
- IIIF Change Discovery 1.0 (Activity Streams) — `ahg-iiif-collection/Controllers/IiifChangeDiscoveryController.php` → `ahgIiifPlugin`.
- TripoSR 2D->3D Auto-configuration — `ahg-3d-model/src/Services/ThreeDAutoConfigService.php (referenced in lib)` → `ahg3DModelPlugin/lib/Services/ThreeDAutoConfigService.php`.
- RDF Import with Dry-run (TTL/JSON-LD/RDF-XML) — `ahg-ric/Controllers/RdfImportController.php + Services/RdfImportService.php` → `ahgRicExplorerPlugin`.
- CIDOC-CRM v7.1.3 Per-Record Export (Issue #659) — `ahg-ric/Controllers/CrmExportController.php` → `ahgRicExplorerPlugin`.
- SPARQL Proxy (Read-only Federated Queries) — `ahg-ric/Controllers/RdfImportController.php (sparqlProxy method)` → `ahgRicExplorerPlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.