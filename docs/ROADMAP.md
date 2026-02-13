# AtoM Heratio Roadmap

> **Last Updated:** 2026-02-13
> **Framework Version:** 2.8.2
> **Plugins:** 78
> **SDKs:** Python (atom-ahg-python) + TypeScript (atom-client-js)
> **Heratio Migration:** Phase 2 complete (12 WriteServices, Propel coupling 223→223)

---

## Executive Summary

The AtoM AHG Framework+ scores **97/100** in comprehensive feature comparison against the 5 major players in the GLAM/DAM (Galleries, Libraries, Archives, Museums / Digital Asset Management) industry. This positions the framework as the **market leader** across most categories.

| Platform | Score | Position |
|----------|-------|----------|
| **AtoM AHG Framework+** | **97/100** | **#1 Leader** |
| Preservica | 69/100 | #2 |
| Axiell Collections | 62/100 | #3 |
| CollectiveAccess | 61/100 | #4 |
| ArchivesSpace | 54/100 | #5 |
| ResourceSpace | 43/100 | #6 |

### Visual Comparison

```
AtoM AHG Framework+    ████████████████████████████████████████████████ 97
Preservica             ██████████████████████████████████████           69
Axiell Collections     ███████████████████████████████████              62
CollectiveAccess       ███████████████████████████████                  61
ArchivesSpace          ███████████████████████████                      54
ResourceSpace          ██████████████████████                           43
```

---

## Repository Structure

| Repository | Purpose | Status |
|------------|---------|--------|
| [atom-framework](https://github.com/ArchiveHeritageGroup/atom-framework) | Core Laravel foundation, CLI, services | v2.8.2 |
| [atom-ahg-plugins](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins) | 78 AHG plugins | v1.7.30 |
| [atom-extensions-catalog](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog) | Documentation & registry | v2.1.12 |
| [atom-ahg-python](https://github.com/ArchiveHeritageGroup/atom-ahg-python) | Python SDK | v1.0.0 |
| [atom-client-js](https://github.com/ArchiveHeritageGroup/atom-client-js) | TypeScript SDK | v1.0.0 |

---

## Progress Tracker (Gap Analysis)

**Current Score:** 97/100 | **Target:** 100/100 | **Gap:** 3 points

| # | Gap | Status | Points | Category |
|---|-----|--------|--------|----------|
| 1 | Speech-to-Text (Whisper) | **Complete** | +1 | AI & ML |
| 2 | Published SDK (Python/JS) | **Complete** | +1 | API & Integrations |
| 3 | PII Detection (AI) | **Complete** | +1 | AI & ML |
| 4 | Semantic Search | **Complete** | +1 | Search & Discovery |
| 5 | Format Migration Pathways | Not Started | +1 | Digital Preservation |
| 6 | JSON-LD Export | Not Started | +1 | Linked Data |
| 7 | IIIF Auth API | Not Started | +1 | IIIF & Media |

**Status Legend:** Not Started | In Progress | **Complete**

---

## Completed Features (2026)

### GAP 1: Speech-to-Text (Whisper) - COMPLETE
**Completed:** 2026-01-20 | **Category:** AI & ML

- Whisper API integration via OpenAI
- Generate Transcript button on video player
- View Transcript panel with clickable timestamps
- Download VTT/SRT subtitle formats
- Language detection
- Works across all GLAM/DAM sectors

**Files:** `ahgThemeB5Plugin/modules/digitalobject/templates/_showVideo.php`

---

### GAP 2: Published SDKs - COMPLETE
**Completed:** 2026-01-22 | **Category:** API & Integrations

**Python SDK (atom-ahg-python):**
```bash
pip install atom-ahg  # Coming to PyPI
```
- Authentication (API key, session)
- Descriptions CRUD
- Authorities CRUD
- Search operations
- Batch operations
- File upload
- Async support with httpx

**TypeScript SDK (atom-client-js):**
```bash
npm install @ahg/atom-client  # Coming to npm
```
- Full TypeScript types
- Browser and Node.js support
- Async/await patterns
- Same operations as Python

**Repositories:**
- `github.com/ArchiveHeritageGroup/atom-ahg-python`
- `github.com/ArchiveHeritageGroup/atom-client-js`

---

### GAP 3: PII Detection (AI-Powered) - COMPLETE
**Completed:** 2026-01-21 | **Category:** AI & ML + Compliance

**Features:**
- PiiDetectionService with regex patterns
- NER integration (PERSON, ORG, GPE, DATE)
- South African ID validation (Luhn)
- Risk level classification
- PII Scanner admin dashboard
- Review queue workflow
- ISAD Access Points scanning
- PDF Redaction with viewer integration
- Visual Redaction Editor

**PII Types Detected:**
| Type | Risk Level | Method |
|------|-----------|--------|
| CREDIT_CARD | Critical | Regex + Luhn |
| SA_ID | High | Regex + SA Luhn |
| NG_NIN | High | Regex |
| PASSPORT | High | Regex |
| BANK_ACCOUNT | High | Regex |
| PERSON | Medium | NER (spaCy) |
| EMAIL | Medium | Regex |
| PHONE | Medium | Regex |

**Files:** `ahgPrivacyPlugin/lib/Service/PiiDetectionService.php`

---

### GAP 4: Semantic Search - COMPLETE
**Completed:** 2026-01-22 | **Category:** Search & Discovery

**Plugin:** ahgSemanticSearchPlugin

**Features:**
- Thesaurus management with domain-specific synonyms
- WordNet sync via Datamuse API
- Wikidata SPARQL integration
- Local JSON synonym import
- Elasticsearch synonym export
- Query expansion for enhanced search
- Vector embeddings via Ollama
- Scheduled sync via cron jobs

**Tables:**
- `semantic_synonym` - Term/synonym relationships
- `semantic_embedding` - Vector embeddings
- `ahg_semantic_search_settings` - Configuration
- `semantic_query_log` - Analytics

**CLI:**
```bash
php bin/semantic-search-cron.php all          # Full sync
php bin/semantic-search-cron.php sync-wordnet # WordNet only
php bin/semantic-search-cron.php sync-wikidata # Wikidata only
php bin/semantic-search-cron.php update-embeddings # Embeddings
php bin/semantic-search-cron.php export-es    # ES export
```

---

## Remaining Gaps (3 points to 100/100)

### GAP 5: Format Migration Pathways
**Category:** Digital Preservation | **Priority:** Medium | **Effort:** 4 weeks

| Attribute | Details |
|-----------|---------|
| **Current State** | 4 tools configured (ImageMagick, FFmpeg, LibreOffice, Pandoc) |
| **Target State** | 100+ documented migration pathways with risk assessment |
| **Plugin** | ahgPreservationPlugin |

**Tasks:**
- [ ] Document ImageMagick pathways (30+)
- [ ] Document FFmpeg pathways (30+)
- [ ] Document LibreOffice pathways (20+)
- [ ] Document Pandoc pathways (20+)
- [ ] Create migration risk matrix
- [ ] Admin UI for pathway selection

---

### GAP 6: JSON-LD Export
**Category:** Linked Data | **Priority:** Low | **Effort:** 2 weeks

| Attribute | Details |
|-----------|---------|
| **Current State** | Ontologies configured, no export |
| **Target State** | Full Schema.org JSON-LD output |
| **Plugin** | ahgAPIPlugin |

**Tasks:**
- [ ] Create JsonLdExportService
- [ ] Map ISAD(G) to Schema.org
- [ ] Add JSON-LD to public pages
- [ ] API endpoint: `/api/v2/descriptions/:id.jsonld`
- [ ] Validate with Google Structured Data Tool

---

### GAP 7: IIIF Auth API
**Category:** IIIF & Media | **Priority:** Low | **Effort:** 2 weeks

| Attribute | Details |
|-----------|---------|
| **Current State** | IIIF Presentation 3.0, no auth |
| **Target State** | IIIF Authentication API 1.0 |
| **Plugin** | atom-framework IiifViewer |

**Tasks:**
- [ ] Implement login/clickthrough/kiosk patterns
- [ ] Add auth services to manifests
- [ ] Integrate with Security Clearance
- [ ] Degraded access (watermarked)

---

## Overall Ratings by Category

| Category | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|----------|----------|---------------|------------|------------------|---------------|--------|
| Core Archives | **10** | 9 | 6 | 7 | 2 | 9 |
| Digital Preservation | **9** | 5 | **10** | 4 | 3 | 2 |
| API & Integrations | **10** | 8 | **9** | 6 | 6 | 7 |
| AI & ML | **9** | 2 | **9** | 2 | 6 | 2 |
| IIIF & Media | **10** | 4 | 8 | 6 | 4 | 7 |
| Compliance & Security | **10** | 5 | 8 | 4 | 5 | 6 |
| Museum Standards | **9** | 2 | 1 | 9 | 1 | **10** |
| Data Migration | **10** | 8 | 8 | 8 | 6 | 8 |
| Public Access | **10** | 7 | 7 | 7 | 8 | 7 |
| Linked Data | **9** | 4 | 3 | 8 | 2 | 4 |
| **TOTAL** | **97/100** | **54/100** | **69/100** | **61/100** | **43/100** | **62/100** |

---

## Unique Advantages (No Competitor Has)

| Feature | Plugin | Description |
|---------|--------|-------------|
| **Self-Healing Preservation** | ahgPreservationPlugin | Automatic fixity repair from backup |
| **RiC with Fuseki** | ahgRicExplorerPlugin | Full Records in Contexts with SPARQL |
| **3D IIIF Manifests** | ahg3DModelPlugin | IIIF 3.0 for 3D models with AR |
| **Multi-Jurisdiction Privacy** | ahgPrivacyPlugin | 7 privacy frameworks in one plugin |
| **Traditional Knowledge Labels** | ahgExtendedRightsPlugin | Local Contexts integration |
| **Getty Auto-Linking** | ahgMuseumPlugin | Confidence-scored vocabulary matching |
| **SHACL Validation** | ahgRicExplorerPlugin | RiC shape validation |
| **Mobile Condition Capture** | ahgConditionPlugin | Field assessments with photo upload |
| **Integrated E-Commerce** | ahgCartPlugin | PayFast/Stripe in archives |
| **Heritage Accounting** | ahgHeritageAccountingPlugin | GRAP 103, IPSAS 45, FRS 102, GASB 34 |

---

## Plugin Inventory (78 Plugins)

### Core Required (Locked)
| Plugin | Purpose |
|--------|---------|
| ahgThemeB5Plugin | Bootstrap 5 theme |
| ahgSecurityClearancePlugin | Security classification |

### Sector-Specific
| Plugin | Purpose |
|--------|---------|
| ahgLibraryPlugin | MARC-inspired cataloging |
| ahgMuseumPlugin | CCO/SPECTRUM/CIDOC-CRM |
| ahgGalleryPlugin | Gallery/exhibition management |
| ahgDAMPlugin | Digital Asset Management |

### AI & Advanced
| Plugin | Purpose |
|--------|---------|
| ahgNerPlugin | Named Entity Recognition |
| ahgSemanticSearchPlugin | Semantic search, thesaurus, embeddings |
| ahgMetadataExtractionPlugin | EXIF/IPTC/XMP extraction |
| ahg3DModelPlugin | 3D viewer with AR |
| ahgRicExplorerPlugin | Records in Contexts |

### Preservation & Conservation
| Plugin | Purpose |
|--------|---------|
| ahgPreservationPlugin | Fixity, PRONOM, SIP/AIP/DIP |
| ahgConditionPlugin | Condition assessment |
| ahgProvenancePlugin | Chain of custody |

### Compliance
| Plugin | Purpose |
|--------|---------|
| ahgPrivacyPlugin | Multi-jurisdiction privacy |
| ahgAuditTrailPlugin | Comprehensive logging |
| ahgHeritageAccountingPlugin | Heritage asset accounting |
| ahgExtendedRightsPlugin | RightsStatements.org, TK Labels |
| ahgRightsPlugin | PREMIS rights |

### Research & Access
| Plugin | Purpose |
|--------|---------|
| ahgResearchPlugin | Reading room, researcher portal |
| ahgAccessRequestPlugin | Access request workflow |
| ahgRequestToPublishPlugin | Publication requests |

### Commerce & Operations
| Plugin | Purpose |
|--------|---------|
| ahgCartPlugin | Shopping cart, payments |
| ahgVendorPlugin | Vendor management |
| ahgDonorAgreementPlugin | Donor tracking |
| ahgLoanPlugin | Object loan management |

### Data & Integration
| Plugin | Purpose |
|--------|---------|
| ahgAPIPlugin | REST API v2 |
| ahgDataMigrationPlugin | Import/export tools |
| ahgMigrationPlugin | Data migration |
| ahgReportBuilderPlugin | Custom reports |
| ahgBackupPlugin | Automated backups |

### User Experience
| Plugin | Purpose |
|--------|---------|
| ahgDisplayPlugin | Display profiles, ZoomPan |
| ahgFavoritesPlugin | User bookmarks |
| ahgFeedbackPlugin | User feedback |
| ahgIiifCollectionPlugin | IIIF collections |
| ahgSpectrumPlugin | SPECTRUM 5.0 |

### Ingestion & Import
| Plugin | Purpose |
|--------|---------|
| ahgIngestPlugin | OAIS-aligned 6-step ingest wizard |
| ahgDataMigrationPlugin | GLAM/DAM CSV import/export |

### Administration
| Plugin | Purpose |
|--------|---------|
| ahgSettingsPlugin | Centralized settings hub |
| ahgJobsManagePlugin | Background job management |
| ahgMenuManagePlugin | Menu/navigation management |
| ahgStaticPagePlugin | Static page management |
| ahgInformationObjectManagePlugin | Information object management |

### Browse & Discovery
| Plugin | Purpose |
|--------|---------|
| ahgDisplayPlugin | GLAM display profiles, ZoomPan |
| ahgSearchPlugin | Advanced search |
| ahgUiOverridesPlugin | UI overrides, viewer dispatch |
| ahgAccessionManagePlugin | Accession browse |
| ahgActorManagePlugin | Actor browse, autocomplete |
| ahgDonorManagePlugin | Donor browse |
| ahgRepositoryManagePlugin | Repository browse |
| ahgRightsHolderManagePlugin | Rights holder browse |
| ahgStorageManagePlugin | Physical storage browse |
| ahgTermTaxonomyPlugin | Term & taxonomy browse |

---

## Compliance Support

### Privacy Regulations
| Jurisdiction | Regulation | Status |
|--------------|------------|--------|
| South Africa | POPIA, PAIA | Full |
| European Union | GDPR | Full |
| United Kingdom | UK GDPR | Full |
| Canada | PIPEDA | Full |
| Nigeria | NDPA | Full |
| Kenya | DPA | Full |
| California | CCPA | Full |

### Heritage Accounting Standards
| Standard | Region |
|----------|--------|
| GRAP 103 | South Africa |
| IPSAS 45 | International |
| FRS 102 | United Kingdom |
| GASB 34 | USA State/Local |
| FASAB | USA Federal |
| AASB 116 | Australia |
| PSAB/PS 3150 | Canada |

---

## Milestones

| Milestone | Score | Date |
|-----------|-------|------|
| Initial Framework | 94/100 | 2026-01-01 |
| Speech-to-Text | 95/100 | 2026-01-20 |
| PII Detection | 96/100 | 2026-01-21 |
| SDKs + Semantic Search | 97/100 | 2026-01-22 |
| Format Migration | 98/100 | TBD |
| JSON-LD + IIIF Auth | 100/100 | TBD |

---

## Document History

| Date | Version | Changes |
|------|---------|---------|
| 2026-01-20 | 1.0 | Initial analysis |
| 2026-01-20 | 1.5 | Speech-to-Text complete (95/100) |
| 2026-01-21 | 1.6 | PII Detection complete (96/100) |
| 2026-01-22 | 2.0 | SDKs created, Semantic Search plugin, renamed to ROADMAP.md (97/100) |
| 2026-02-13 | 3.0 | Updated to 78 plugins, Heratio migration status, ahgIngestPlugin |

---

*The Archive and Heritage Group (Pty) Ltd*
*https://theahg.co.za*
