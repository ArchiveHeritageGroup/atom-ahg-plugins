# AtoM AHG Framework+ Industry Comparison

> **Last Updated:** 2026-01-20
> **Framework Version:** 2.1.8
> **Analysis Scope:** 5 Major GLAM/DAM Competitors

---

## Executive Summary

The AtoM AHG Framework+ scores **96/100** in comprehensive feature comparison against the 5 major players in the GLAM/DAM (Galleries, Libraries, Archives, Museums / Digital Asset Management) industry. This positions the framework as the **market leader** across most categories.

| Platform | Score | Position |
|----------|-------|----------|
| **AtoM AHG Framework+** | **96/100** | **#1 Leader** |
| Preservica | 69/100 | #2 |
| Axiell Collections | 62/100 | #3 |
| CollectiveAccess | 61/100 | #4 |
| ArchivesSpace | 54/100 | #5 |
| ResourceSpace | 43/100 | #6 |

---

## Competitors Analyzed

### 1. ArchivesSpace
- **Type:** Archives Management System
- **License:** Open Source (ECL 2.0)
- **Primary Market:** Academic archives, universities, special collections
- **Strengths:** Strong archival description (DACS), large community, Aeon integration
- **Website:** https://archivesspace.org/

### 2. Preservica
- **Type:** Digital Preservation Platform
- **License:** Commercial (SaaS)
- **Primary Market:** Enterprise, government, regulated industries
- **Strengths:** OAIS compliance, 816 format migration pathways, AI capabilities
- **Website:** https://preservica.com/

### 3. CollectiveAccess
- **Type:** Collections Management System
- **License:** Open Source (GPL)
- **Primary Market:** Museums, archives, special collections
- **Strengths:** Flexible metadata, Getty integration, Linked Open Data
- **Website:** https://www.collectiveaccess.org/

### 4. ResourceSpace
- **Type:** Digital Asset Management
- **License:** Open Source (with commercial tiers)
- **Primary Market:** Media, marketing, museums, education
- **Strengths:** AI tagging, facial recognition, marketing integrations
- **Website:** https://www.resourcespace.com/

### 5. Axiell Collections
- **Type:** Collections Management System
- **License:** Commercial
- **Primary Market:** National museums, large archives, libraries
- **Strengths:** SPECTRUM native, enterprise features, multi-discipline
- **Website:** https://www.axiell.com/solutions/product/axiell-collections/

---

## Feature Comparison Matrices

### 1. Core Archives Management

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| ISAD(G) Support | ✅ Native | ✅ Via DACS | ⚠️ Basic | ⚠️ Configurable | ❌ | ✅ Native |
| DACS Support | ✅ | ✅ Native | ⚠️ Basic | ✅ | ❌ | ✅ |
| RAD Support | ✅ | ⚠️ Limited | ❌ | ⚠️ | ❌ | ✅ |
| EAD Import/Export | ✅ 2002 + EAD3 | ✅ Excellent | ✅ | ✅ | ❌ | ✅ |
| RiC Support | ✅ Full Fuseki | ❌ | ❌ | ❌ | ❌ | ❌ |
| Hierarchical Records | ✅ | ✅ | ✅ | ✅ | ⚠️ | ✅ |
| Authority Records (ISAAR-CPF) | ✅ | ✅ | ⚠️ | ✅ | ❌ | ✅ |
| Functions (ISDF) | ✅ | ⚠️ | ❌ | ❌ | ❌ | ✅ |
| Institutions (ISDIAH) | ✅ | ✅ | ⚠️ | ⚠️ | ❌ | ✅ |
| Rights (PREMIS) | ✅ | ⚠️ | ✅ | ⚠️ | ❌ | ⚠️ |
| **Rating** | **10/10** | **9/10** | **6/10** | **7/10** | **2/10** | **9/10** |

### 2. Digital Preservation

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| Checksum Generation | ✅ MD5/SHA1/256/512 | ⚠️ Via plugin | ✅ | ⚠️ Basic | ✅ | ⚠️ |
| Fixity Verification | ✅ Scheduled | ⚠️ Via Archivematica | ✅ Automatic | ❌ | ❌ | ❌ |
| Self-Healing Repair | ✅ **Unique** | ❌ | ⚠️ Manual | ❌ | ❌ | ❌ |
| Format Identification | ✅ Siegfried/PRONOM | ⚠️ Via tools | ✅ Native | ❌ | ❌ | ❌ |
| PRONOM Registry Sync | ✅ Automatic | ❌ | ✅ Native | ❌ | ❌ | ❌ |
| Format Migration | ✅ 4 tools | ❌ | ✅ 816 pathways | ❌ | ⚠️ Basic | ❌ |
| Virus Scanning | ✅ ClamAV | ❌ | ✅ | ❌ | ❌ | ❌ |
| BagIt Support | ✅ Create/Validate | ✅ | ✅ | ✅ v2.0 | ❌ | ❌ |
| SIP/AIP/DIP Workflow | ✅ Full | ❌ | ✅ Native | ❌ | ❌ | ❌ |
| Workflow Scheduler | ✅ Cron-based | ❌ | ✅ | ❌ | ❌ | ❌ |
| Backup Verification | ✅ Local/SFTP/S3 | ❌ | ✅ | ❌ | ❌ | ❌ |
| PREMIS Events | ✅ Logged | ⚠️ Via integration | ✅ | ❌ | ❌ | ❌ |
| **Rating** | **9/10** | **5/10** | **10/10** | **4/10** | **3/10** | **2/10** |

### 3. API & Integrations

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| REST API | ✅ v2 Comprehensive | ✅ Full | ✅ | ✅ | ✅ | ✅ |
| Batch Operations | ✅ Up to 100 | ✅ Bulk updater | ✅ | ✅ | ✅ | ✅ |
| API Key Management | ✅ Full | ⚠️ Basic | ✅ | ⚠️ | ⚠️ | ⚠️ |
| Webhooks | ✅ | ⚠️ Limited | ✅ | ⚠️ | ⚠️ | ⚠️ |
| Mobile Sync | ✅ Offline support | ❌ | ⚠️ | ❌ | ❌ | ⚠️ |
| File Upload API | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Condition Photos API | ✅ Mobile capture | ❌ | ❌ | ⚠️ | ❌ | ⚠️ |
| Heritage Assets API | ✅ GRAP/IPSAS | ❌ | ❌ | ⚠️ | ❌ | ⚠️ |
| Privacy/DSAR API | ✅ Full CRUD | ❌ | ⚠️ | ❌ | ❌ | ❌ |
| External Services | ✅ 15+ configured | ⚠️ Via plugins | ✅ | ⚠️ | ⚠️ | ⚠️ |
| **Rating** | **9/10** | **8/10** | **9/10** | **6/10** | **6/10** | **7/10** |

### 4. AI & Machine Learning

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| NER Extraction | ✅ With review workflow | ❌ | ⚠️ Via integration | ❌ | ❌ | ❌ |
| Confidence Scoring | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Face Detection | ✅ ahgFaceDetectionService | ❌ | ⚠️ Via partner | ❌ | ✅ | ❌ |
| Auto-tagging | ✅ Via NER + Getty | ❌ | ✅ | ❌ | ✅ AI-powered | ⚠️ |
| OCR Integration | ✅ IiifViewer OCR | ⚠️ Via tools | ✅ | ⚠️ | ❌ | ⚠️ |
| AI Summarization | ✅ NER plugin | ❌ | ✅ | ❌ | ❌ | ❌ |
| OpenAI Integration | ✅ Configured | ❌ | ✅ | ❌ | ❌ | ❌ |
| Anthropic Integration | ✅ Configured | ❌ | ❌ | ❌ | ❌ | ❌ |
| Speech-to-Text | ✅ Whisper integrated | ❌ | ✅ | ❌ | ✅ | ❌ |
| PII Detection | ✅ AI + Regex patterns | ❌ | ✅ AI-powered | ❌ | ❌ | ❌ |
| **Rating** | **10/10** | **2/10** | **9/10** | **2/10** | **6/10** | **2/10** |

### 5. IIIF & Media

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| IIIF Presentation 3.0 | ✅ | ⚠️ Via plugin | ✅ Via partner | ⚠️ | ❌ | ✅ |
| IIIF Image API | ✅ Via Cantaloupe | ⚠️ | ✅ | ⚠️ | ❌ | ✅ |
| Manifest Generation | ✅ IiifManifestService | ⚠️ Limited | ✅ | ⚠️ | ❌ | ✅ |
| Annotations | ✅ AnnotationService | ❌ | ✅ | ❌ | ❌ | ⚠️ |
| OCR Layer | ✅ OcrService | ❌ | ✅ | ❌ | ❌ | ⚠️ |
| Transcriptions | ✅ TranscriptionService | ❌ | ✅ | ❌ | ❌ | ⚠️ |
| Mirador/UV Viewer | ✅ | ⚠️ | ✅ | ⚠️ | ❌ | ⚠️ |
| 3D Model Viewing | ✅ Google Model Viewer + AR | ❌ | ⚠️ | ✅ | ❌ | ⚠️ |
| 3D IIIF Manifests | ✅ **Unique** | ❌ | ❌ | ❌ | ❌ | ❌ |
| Waveform/Audio | ✅ | ⚠️ | ✅ | ✅ | ✅ | ✅ |
| Video Players | ✅ | ⚠️ | ✅ | ✅ | ✅ | ✅ |
| **Rating** | **10/10** | **4/10** | **8/10** | **6/10** | **4/10** | **7/10** |

### 6. Compliance & Security

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| Multi-Jurisdiction Privacy | ✅ 7 jurisdictions | ❌ | ⚠️ GDPR only | ❌ | ⚠️ | ⚠️ |
| DSAR Management | ✅ Full workflow | ❌ | ⚠️ | ❌ | ❌ | ❌ |
| Breach Register | ✅ With notifications | ❌ | ✅ | ❌ | ❌ | ❌ |
| Consent Tracking | ✅ | ❌ | ⚠️ | ❌ | ⚠️ | ⚠️ |
| ROPA | ✅ | ❌ | ⚠️ | ❌ | ❌ | ❌ |
| Audit Trail | ✅ Comprehensive | ✅ | ✅ | ⚠️ | ✅ | ✅ |
| Security Clearance | ✅ Multi-level | ⚠️ Basic ACL | ✅ | ⚠️ | ⚠️ | ✅ |
| Embargo Management | ✅ ahgExtendedRightsPlugin | ⚠️ | ✅ | ⚠️ | ❌ | ⚠️ |
| Traditional Knowledge | ✅ Local Contexts labels | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Rating** | **10/10** | **5/10** | **8/10** | **4/10** | **5/10** | **6/10** |

**Supported Privacy Jurisdictions:**
| Jurisdiction | Framework | Response Timeline |
|--------------|-----------|-------------------|
| South Africa | POPIA | 30 days / 72hr breach |
| European Union | GDPR | 30 days / 72hr breach |
| United Kingdom | UK GDPR | 30 days / 72hr breach |
| Canada | PIPEDA | 30 days / immediate breach |
| Nigeria | NDPA | 30 days / 72hr breach |
| Kenya | DPA | 30 days / 72hr breach |
| California, US | CCPA | 45 days / variable |

### 7. Museum Standards

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| SPECTRUM 5.0 | ✅ 14+ procedures | ❌ | ❌ | ✅ | ❌ | ✅ Native |
| CCO (Cataloging Cultural Objects) | ✅ Full module | ❌ | ❌ | ✅ | ❌ | ✅ |
| CIDOC-CRM | ✅ Export service | ❌ | ❌ | ✅ | ❌ | ⚠️ |
| Condition Reports | ✅ With mobile capture | ❌ | ❌ | ✅ | ❌ | ✅ |
| Loan Management | ✅ Full workflow | ❌ | ❌ | ✅ | ❌ | ✅ |
| Exhibition Management | ✅ With checklists | ❌ | ❌ | ✅ | ❌ | ✅ |
| Location Tracking | ✅ | ✅ | ⚠️ | ✅ | ❌ | ✅ |
| Valuation/Insurance | ✅ ahgHeritageAccountingPlugin | ❌ | ❌ | ✅ | ❌ | ✅ |
| Provenance Tracking | ✅ ahgProvenancePlugin | ❌ | ❌ | ✅ | ❌ | ✅ |
| Object ID (Getty) | ✅ | ❌ | ❌ | ✅ | ❌ | ✅ |
| **Rating** | **9/10** | **2/10** | **1/10** | **9/10** | **1/10** | **10/10** |

### 8. Data Migration

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| CSV/Excel Import | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| XML Import | ✅ | ✅ EAD/MARCXML | ✅ | ✅ | ⚠️ | ✅ |
| OPEX Format | ✅ | ❌ | ✅ Native | ❌ | ❌ | ❌ |
| PAX/XIP Format | ✅ | ❌ | ✅ Native | ❌ | ❌ | ❌ |
| ArchivesSpace Import | ✅ | N/A | ✅ | ⚠️ | ❌ | ⚠️ |
| Vernon CMS Import | ✅ | ❌ | ❌ | ⚠️ | ❌ | ⚠️ |
| Field Mapping UI | ✅ Interactive | ✅ | ⚠️ | ✅ | ✅ | ✅ |
| Background Jobs | ✅ Gearman | ✅ | ✅ | ✅ v2.0 | ⚠️ | ✅ |
| Sector-Specific Export | ✅ GLAM/DAM | ❌ | ⚠️ | ⚠️ | ⚠️ | ⚠️ |
| **Rating** | **10/10** | **8/10** | **8/10** | **8/10** | **6/10** | **8/10** |

### 9. Public Access & Discovery

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| Public Portal | ✅ | ✅ PUI | ✅ Universal Access | ✅ Pawtucket2 | ✅ | ✅ |
| Research Portal | ✅ ahgResearchPlugin | ⚠️ Via Aeon | ✅ | ⚠️ | ❌ | ⚠️ |
| Reading Room Booking | ✅ With check-in/out | ✅ Via Aeon | ⚠️ | ❌ | ❌ | ⚠️ |
| Shopping Cart | ✅ ahgCartPlugin | ✅ Via Aeon | ⚠️ | ⚠️ | ✅ | ⚠️ |
| E-Commerce | ✅ PayFast/Stripe | ❌ | ❌ | ❌ | ✅ | ⚠️ |
| Favorites/Bookmarks | ✅ ahgFavoritesPlugin | ⚠️ | ⚠️ | ✅ | ✅ | ✅ |
| Citation Generation | ✅ | ⚠️ | ⚠️ | ⚠️ | ❌ | ⚠️ |
| Elasticsearch | ✅ | ✅ Solr | ✅ | ⚠️ | ✅ | ⚠️ |
| Publication Requests | ✅ ahgRequestToPublishPlugin | ❌ | ⚠️ | ❌ | ✅ | ⚠️ |
| **Rating** | **10/10** | **7/10** | **7/10** | **7/10** | **8/10** | **7/10** |

### 10. Linked Data

| Feature | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|---------|----------|---------------|------------|------------------|---------------|--------|
| Getty AAT | ✅ Full SPARQL | ⚠️ Basic | ❌ | ✅ Native | ❌ | ⚠️ |
| Getty TGN | ✅ Full SPARQL | ❌ | ❌ | ✅ | ❌ | ⚠️ |
| Getty ULAN | ✅ Full SPARQL | ⚠️ Limited | ❌ | ✅ | ❌ | ⚠️ |
| Auto-Linking | ✅ Confidence scoring | ❌ | ❌ | ⚠️ Manual | ❌ | ❌ |
| VIAF | ✅ Configured | ⚠️ Limited | ❌ | ✅ | ❌ | ⚠️ |
| Wikidata | ✅ Configured | ❌ | ❌ | ⚠️ Via plugin | ❌ | ❌ |
| RiC-O Ontology | ✅ Full Fuseki | ❌ | ❌ | ❌ | ❌ | ❌ |
| CIDOC-CRM Export | ✅ Museum module | ❌ | ❌ | ✅ | ❌ | ⚠️ |
| SPARQL Endpoint | ✅ Fuseki | ❌ | ❌ | ⚠️ Limited | ❌ | ❌ |
| SHACL Validation | ✅ RiC shapes | ❌ | ❌ | ❌ | ❌ | ❌ |
| OAI-PMH | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Rating** | **9/10** | **4/10** | **3/10** | **8/10** | **2/10** | **4/10** |

---

## Overall Ratings Summary

| Category | AtoM AHG | ArchivesSpace | Preservica | CollectiveAccess | ResourceSpace | Axiell |
|----------|----------|---------------|------------|------------------|---------------|--------|
| Core Archives | **10** | 9 | 6 | 7 | 2 | 9 |
| Digital Preservation | **9** | 5 | **10** | 4 | 3 | 2 |
| API & Integrations | **9** | 8 | **9** | 6 | 6 | 7 |
| AI & ML | **10** | 2 | **9** | 2 | 6 | 2 |
| IIIF & Media | **10** | 4 | 8 | 6 | 4 | 7 |
| Compliance & Security | **10** | 5 | 8 | 4 | 5 | 6 |
| Museum Standards | **9** | 2 | 1 | 9 | 1 | **10** |
| Data Migration | **10** | 8 | 8 | 8 | 6 | 8 |
| Public Access | **10** | 7 | 7 | 7 | 8 | 7 |
| Linked Data | **9** | 4 | 3 | 8 | 2 | 4 |
| **TOTAL** | **96/100** | **54/100** | **69/100** | **61/100** | **43/100** | **62/100** |

### Visual Comparison

```
AtoM AHG Framework+    ██████████████████████████████████████████████████ 96
Preservica             ██████████████████████████████████████           69
Axiell Collections     ███████████████████████████████████              62
CollectiveAccess       ███████████████████████████████                  61
ArchivesSpace          ███████████████████████████                      54
ResourceSpace          ██████████████████████                           43
```

---

## Unique Advantages (No Competitor Has)

These features are exclusive to AtoM AHG Framework+:

| Feature | Plugin/Service | Description |
|---------|---------------|-------------|
| **Self-Healing Preservation** | ahgPreservationPlugin | Automatic fixity repair from backup (local/SFTP/S3) |
| **RiC with Fuseki** | ahgRicExplorerPlugin | Full Records in Contexts ontology with SPARQL triplestore |
| **3D IIIF Manifests** | ahg3DModelPlugin | IIIF Presentation 3.0 support for 3D models with AR |
| **Multi-Jurisdiction Privacy** | ahgPrivacyPlugin | 7 privacy frameworks (POPIA, GDPR, CCPA, etc.) in single plugin |
| **Traditional Knowledge Labels** | ahgExtendedRightsPlugin | Local Contexts integration for indigenous heritage |
| **Getty Auto-Linking** | ahgMuseumPlugin | Confidence-scored automatic vocabulary matching |
| **SHACL Validation** | ahgRicExplorerPlugin | RiC shape validation for data quality |
| **Mobile Condition Capture** | ahgAPIPlugin + ahgConditionPlugin | API for field condition assessments with photo upload |
| **Integrated E-Commerce** | ahgCartPlugin | Full cart/checkout with PayFast/Stripe in archives system |
| **CIDOC-CRM + SPECTRUM** | ahgMuseumPlugin + ahgSpectrumPlugin | Combined museum standards in archives platform |
| **Heritage Accounting** | ahgHeritageAccountingPlugin | Multi-standard (GRAP 103, IPSAS 45, FRS 102, GASB 34) |

---

## AtoM AHG Plugins Inventory

### Core Required (Locked)
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgThemeB5Plugin | - | Bootstrap 5 theme, framework initialization |
| ahgSecurityClearancePlugin | - | Security classification, user clearance |

### Sector-Specific
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgLibraryPlugin | v1.9.14 | MARC-inspired cataloging, ISBN lookup |
| ahgMuseumPlugin | v1.4.16 | CCO/SPECTRUM/CIDOC-CRM, Getty integration |
| ahgGalleryPlugin | v1.2.10 | Gallery/exhibition management |
| ahgDAMPlugin | v1.3.13 | Digital Asset Management |

### Preservation & Conservation
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgPreservationPlugin | v1.0.4 | Fixity, PRONOM, virus scan, SIP/AIP/DIP |
| ahgConditionPlugin | v1.2.6 | Condition assessment |
| ahgProvenancePlugin | v1.0.3 | Chain of custody |

### Rights & Compliance
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgRightsPlugin | v1.1.2 | PREMIS rights management |
| ahgExtendedRightsPlugin | v1.2.9 | RightsStatements.org, TK Labels |
| ahgPrivacyPlugin | v1.1.11 | Multi-jurisdiction privacy compliance |
| ahgAuditTrailPlugin | v1.2.10 | Comprehensive audit logging |
| ahgDonorAgreementPlugin | v1.3.6 | Donor agreements |

### Research & Access
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgResearchPlugin | v1.3.7 | Reading room booking, researcher portal |
| ahgAccessRequestPlugin | v1.1.4 | Access request management |
| ahgRequestToPublishPlugin | v1.0.3 | Publication requests |

### Commerce
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgCartPlugin | v2.0.2 | Shopping cart, PayFast/Stripe |
| ahgVendorPlugin | v1.0.9 | Vendor management |

### Standards & Integration
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgSpectrumPlugin | v1.1.9 | SPECTRUM 5.0 procedures |
| ahgRicExplorerPlugin | v1.1.4 | RiC visualization, Fuseki |
| ahgHeritageAccountingPlugin | v1.2.4 | Multi-standard heritage accounting |
| ahgAPIPlugin | v1.1.1 | REST API v2 |
| ahgIiifCollectionPlugin | v1.1.2 | IIIF collections |

### AI & Metadata
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgNerPlugin | v1.0.17 | Named Entity Recognition |
| ahg3DModelPlugin | v1.1.7 | 3D model viewing with AR |

### Data & Operations
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgDataMigrationPlugin | v1.2.11 | Import from ArchivesSpace/Vernon/Preservica |
| ahgBackupPlugin | v1.1.0 | Backup scheduling |
| ahgDisplayPlugin | v1.2.11 | GLAM browser, display modes |

### User Experience
| Plugin | Version | Purpose |
|--------|---------|---------|
| ahgFavoritesPlugin | v1.0.7 | User bookmarks |
| ahgFeedbackPlugin | v1.0.5 | User feedback |

---

## Remaining Gaps to 100/100 (4% Outstanding)

**Current Score:** 96/100 | **Target:** 100/100 | **Gap:** 4 points | **Est. Effort:** ~11 weeks

### Progress Tracker

| # | Gap | Status | Points | Effort |
|---|-----|--------|--------|--------|
| 1 | Speech-to-Text (Whisper) | ✅ Complete | +1 | - |
| 2 | Published SDK (Python/JS) | ⬜ Not Started | +1 | 3 weeks |
| 3 | PII Detection (AI) | ✅ Complete | +1 | - |
| 4 | Format Migration Pathways | ⬜ Not Started | +1 | 4 weeks |
| 5 | JSON-LD Export | ⬜ Not Started | +1 | 2 weeks |
| 6 | IIIF Auth API | ⬜ Not Started | +1 | 2 weeks |

**Status Legend:** ⬜ Not Started | 🟡 In Progress | ✅ Complete

---

### GAP 1: Speech-to-Text (Whisper Integration) ✅ COMPLETE
**Category:** AI & ML | **Points:** +1 | **Status:** Complete | **Completed:** 2026-01-20

| Attribute | Details |
|-----------|---------|
| **Implementation** | Integrated into enhanced video player (`_showVideo.php`) |
| **Table** | `media_transcription` |
| **Endpoints** | `/media/transcribe/{id}`, `/media/transcription/{id}` |
| **Features** | Generate, view, download (VTT/SRT), re-transcribe, delete |

**Implementation Details:**
- ✅ Whisper API integration via OpenAI
- ✅ Generate Transcript button on video player
- ✅ View Transcript collapsible panel with clickable timestamps
- ✅ Download VTT/SRT subtitle formats
- ✅ Re-transcribe and delete options
- ✅ Language detection
- ✅ Segment-level timestamps for video sync
- ✅ Works across all GLAM/DAM sectors (shared via `_showVideo.php`)

**Files:**
```
ahgThemeB5Plugin/modules/digitalobject/templates/_showVideo.php (lines 64-165)
ahgThemeB5Plugin/modules/media/actions/actions.class.php
Database: media_transcription table
```

---

### GAP 2: Published SDK (Python & JavaScript)
**Category:** API & Integrations | **Points:** +1 | **Priority:** High

| Attribute | Details |
|-----------|---------|
| **Current State** | Comprehensive REST API v2 exists, no client libraries |
| **Target State** | Published SDKs on PyPI and npm |
| **Effort** | 3 weeks |
| **Dependencies** | ahgAPIPlugin v1.1.1 ✅ |

**Implementation Tasks:**
- [ ] Create `atom-ahg-python` package
  - [ ] Authentication (API key, session)
  - [ ] Descriptions CRUD
  - [ ] Authorities CRUD
  - [ ] Search operations
  - [ ] Batch operations
  - [ ] File upload
  - [ ] Async support
- [ ] Create `atom-ahg-js` package (TypeScript)
  - [ ] Same operations as Python
  - [ ] Browser and Node.js support
- [ ] Generate OpenAPI 3.0 specification
- [ ] Create Postman collection
- [ ] Write documentation with examples

**Acceptance Criteria:**
- [ ] `pip install atom-ahg` works
- [ ] `npm install @ahg/atom-client` works
- [ ] README with quickstart examples
- [ ] 80%+ test coverage
- [ ] Published to PyPI and npm

**Repositories to Create:**
```
github.com/ArchiveHeritageGroup/atom-ahg-python
github.com/ArchiveHeritageGroup/atom-ahg-js
github.com/ArchiveHeritageGroup/atom-openapi-spec
```

---

### GAP 3: PII Detection (AI-Powered) ✅ COMPLETE
**Category:** AI & ML + Compliance | **Points:** +1 | **Status:** Complete | **Completed:** 2026-01-21

| Attribute | Details |
|-----------|---------|
| **Implementation** | PiiDetectionService + NER integration + ISAD Access Points + PDF Redaction + Privacy module UI |
| **Plugin** | ahgPrivacyPlugin |
| **Tables** | Uses existing `ahg_ner_entity`, `privacy_data_inventory` |
| **CLI** | `php symfony privacy:scan-pii` |

**Implementation Details:**
- ✅ PiiDetectionService.php with regex patterns for SA-specific PII
- ✅ NER integration (PERSON, ORG, GPE, DATE entities)
- ✅ Regex patterns for: SA_ID, NG_NIN, PASSPORT, EMAIL, PHONE_SA, PHONE_INTL, BANK_ACCOUNT, TAX_NUMBER, CREDIT_CARD
- ✅ South African ID validation using Luhn algorithm
- ✅ Risk level classification (critical, high, medium, low)
- ✅ Batch scanning with repository filters
- ✅ PII Scanner admin dashboard with statistics
- ✅ Review queue with approve/redact/reject workflow
- ✅ Privacy data inventory integration
- ✅ CLI command: `php symfony privacy:scan-pii [--id=X] [--repository=X] [--limit=X] [--stats]`
- ✅ **ISAD Access Points Integration** - Scans Subject, Place, Name, and Date access points
- ✅ **PDF Redaction** - Automatic redaction of PII in PDF digital objects
- ✅ **Public Redacted PDF Access** - Viewers display redacted PDFs when PII is flagged

**PII Types Detected:**
| Type | Risk Level | Method | Source |
|------|-----------|--------|--------|
| CREDIT_CARD | Critical | Regex + Luhn | Metadata |
| SA_ID | High | Regex + SA Luhn | Metadata |
| NG_NIN | High | Regex | Metadata |
| PASSPORT | High | Regex | Metadata |
| BANK_ACCOUNT | High | Regex | Metadata |
| TAX_NUMBER | High | Regex | Metadata |
| PERSON | Medium | NER (spaCy) | OCR/Text |
| EMAIL | Medium | Regex | Metadata |
| PHONE_SA | Medium | Regex | Metadata |
| PHONE_INTL | Medium | Regex | Metadata |
| ORG | Low | NER (spaCy) | OCR/Text |
| GPE | Low | NER (spaCy) | OCR/Text |
| ISAD_SUBJECT | Medium | ISAD Access Point | Taxonomy 35 |
| ISAD_PLACE | Medium | ISAD Access Point | Taxonomy 42 |
| ISAD_NAME | Medium | ISAD Access Point | Events/Actors |
| ISAD_DATE | Low | ISAD Access Point | Events |

**ISAD Access Points Integration:**

The PII scanner now extracts entities from ISAD(G) access points:
- **Subjects** (Taxonomy 35): Topic/subject access points linked to records
- **Places** (Taxonomy 42): Geographic/place access points
- **Names**: People and organizations from events (creation, accumulation)
- **Dates**: Date ranges from events

These are displayed with purple badges in the review queue to distinguish them from NER-extracted entities.

**PDF Redaction System:**

The system automatically generates redacted PDF versions when PII is flagged:

```
┌─────────────────────────────────────────────────────────────────┐
│                    PDF Redaction Flow                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   Original PDF ──▶ PII Detection ──▶ Review Queue              │
│        │                                     │                   │
│        │                              User marks "Redact"        │
│        │                                     │                   │
│        ▼                                     ▼                   │
│   ┌─────────────┐                   ┌─────────────────┐         │
│   │ PDF.js/IIIF │                   │ PyMuPDF (fitz)  │         │
│   │   Viewer    │◀──────────────────│ Redaction       │         │
│   └─────────────┘                   └─────────────────┘         │
│        │                                     │                   │
│        │                                     ▼                   │
│        │                            cache/pii_redacted/          │
│        │                                     │                   │
│        ▼                                     │                   │
│   Public sees ◀──────────────────────────────┘                  │
│   redacted PDF                                                   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Key Components:**
- `PdfRedactionService.php`: Generates redacted PDFs using PyMuPDF
- `IiifManifestService.php`: Serves redacted PDF path in IIIF manifests
- `ViewerService.php`: Loads redacted PDF in PDF.js/IIIF viewers
- `DigitalObjectViewerHelper.php`: Handles direct iframe embedding with redaction
- `actions.class.php`: Public `downloadPdf` action for unauthenticated access

**Redaction Storage:**
- Original PDF: `uploads/r/{repo_id}/digitalobjects/{id}/`
- Redacted PDF: `cache/pii_redacted/redacted_{object_id}_{filename}`
- Symlink for IIIF: `uploads/pii_redacted/` (for Cantaloupe access)

**Files Created/Modified:**
```
ahgPrivacyPlugin/lib/Service/PiiDetectionService.php (ISAD access points)
ahgPrivacyPlugin/lib/Service/PdfRedactionService.php (PDF redaction)
ahgPrivacyPlugin/lib/task/privacyScanPiiTask.class.php
ahgPrivacyPlugin/modules/privacyAdmin/templates/piiScanSuccess.php
ahgPrivacyPlugin/modules/privacyAdmin/templates/piiReviewSuccess.php (ISAD badges)
ahgPrivacyPlugin/modules/privacyAdmin/actions/actions.class.php (downloadPdf)
atom-framework/src/Extensions/IiifViewer/Services/IiifManifestService.php
atom-framework/src/Extensions/IiifViewer/Services/ViewerService.php
atom-framework/src/Extensions/IiifViewer/public/js/iiif-viewer-manager.js
lib/helper/DigitalObjectViewerHelper.php
```

---

### FEATURE: Visual Redaction Editor ✅ COMPLETE
**Category:** Privacy & Compliance | **Status:** Complete | **Started:** 2026-01-21

| Attribute | Details |
|-----------|---------|
| **Implementation** | Visual Redaction Editor with coordinate-based PDF/Image redaction |
| **Plugin** | ahgPrivacyPlugin |
| **Tables** | `privacy_visual_redaction`, `privacy_redaction_cache` |
| **Route** | `/admin/privacy/redaction/{object_id}` |

**Purpose:**
Allow archivists to visually draw redaction rectangles on PDF pages or images to mark sensitive content for redaction. Complements the automated PII detection by allowing manual redaction of content that automated systems may miss (faces, signatures, handwritten notes, etc.).

**Key Feature: Unified Redaction Workflow**
Visual redactions work exactly like PII Review redactions:
1. Draw regions → Save to database → Click "Apply" → Regions marked as 'applied'
2. When users view the document, PdfRedactionService combines text-based (NER) AND visual redactions
3. Cache is cleared on apply, regenerated on next view with black boxes applied

**Workflow:**
```
┌─────────────────────────────────────────────────────────────────┐
│                Visual Redaction Workflow                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   1. Navigate to record with digital object                     │
│      └── Click "Visual Redaction Editor" in Privacy section     │
│                                                                 │
│   2. Visual Redaction Editor opens                              │
│      ├── PDF: PDF.js + Fabric.js canvas overlay                │
│      └── Image: OpenSeadragon + Annotorious                     │
│                                                                 │
│   3. Draw redaction rectangles                                  │
│      ├── Click "Draw Redaction" button (or press 'r')           │
│      ├── Click and drag to create rectangle                     │
│      └── Regions stored with normalized coordinates (0-1)       │
│                                                                 │
│   4. Save regions to database                                   │
│      └── Stored in privacy_visual_redaction table               │
│                                                                 │
│   5. Click "Apply Redactions"                                   │
│      ├── Marks all pending/approved regions as 'applied'        │
│      ├── Clears PdfRedactionService cache                       │
│      └── Clears VisualRedactionService cache                    │
│                                                                 │
│   6. View redacted document                                     │
│      ├── PdfRedactionService checks for visual redactions       │
│      ├── Combines text-based (NER) + visual redactions          │
│      ├── PyMuPDF applies black boxes at coordinates             │
│      └── Cached redacted PDF served to users                    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Database Schema:**
```sql
CREATE TABLE privacy_visual_redaction (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    digital_object_id INT DEFAULT NULL,
    page_number INT DEFAULT 1,
    region_type ENUM('rectangle','polygon','freehand') DEFAULT 'rectangle',
    coordinates JSON NOT NULL,  -- {x, y, width, height} normalized 0-1
    normalized TINYINT(1) DEFAULT 1,
    source ENUM('manual','auto_ner','auto_pii','imported') DEFAULT 'manual',
    linked_entity_id BIGINT UNSIGNED DEFAULT NULL,
    label VARCHAR(255) DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#000000',
    status ENUM('pending','approved','applied','rejected') DEFAULT 'pending',
    created_by INT,
    reviewed_by INT,
    reviewed_at DATETIME,
    applied_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Integration Points:**
- ✅ Button in record sidebar (ahgThemeB5Plugin `_actionIcons.php`)
- ✅ Button in digital object actions toolbar
- ✅ Link from PII Review queue to Visual Editor
- ✅ Integration with PdfRedactionService for unified redaction
- ✅ PdfRedactionService.getVisualRedactionRegions() fetches visual regions
- ✅ PdfRedactionService.getRedactedPdf() combines text + visual redactions
- ✅ Two-pass redaction: text terms first, then coordinate regions
- ✅ Cache invalidation on apply ensures fresh redacted output

**Files:**
```
ahgPrivacyPlugin/data/migrations/add_visual_redaction_table.sql
ahgPrivacyPlugin/lib/Service/VisualRedactionService.php
ahgPrivacyPlugin/lib/Service/PdfRedactionService.php (visual integration)
ahgPrivacyPlugin/lib/python/pdf_redactor.py (--regions flag for coordinates)
ahgPrivacyPlugin/lib/python/image_redactor.py
ahgPrivacyPlugin/modules/privacyAdmin/templates/visualRedactionEditorSuccess.php
ahgPrivacyPlugin/modules/privacyAdmin/actions/actions.class.php
ahgThemeB5Plugin/modules/informationobject/templates/_actionIcons.php
```

---

### GAP 4: Format Migration Pathways (Documentation)
**Category:** Digital Preservation | **Points:** +1 | **Priority:** Medium

| Attribute | Details |
|-----------|---------|
| **Current State** | 4 tools configured (ImageMagick, FFmpeg, LibreOffice, Pandoc) |
| **Target State** | 100+ documented migration pathways with risk assessment |
| **Plugin** | ahgPreservationPlugin |
| **Effort** | 4 weeks |
| **Dependencies** | PreservationService ✅, PRONOM sync ✅ |

**Implementation Tasks:**
- [ ] Document all ImageMagick pathways (30+)
  - [ ] TIFF → JPEG2000, PNG, WebP
  - [ ] RAW formats → TIFF
  - [ ] Legacy formats (BMP, PCX, TGA) → modern
- [ ] Document all FFmpeg pathways (30+)
  - [ ] Legacy video → MP4/H.264
  - [ ] Legacy audio → MP3/FLAC
  - [ ] Container conversions
- [ ] Document LibreOffice pathways (20+)
  - [ ] DOC/XLS/PPT → PDF/A
  - [ ] Legacy formats → ODF
- [ ] Document Pandoc pathways (20+)
  - [ ] Markup conversions
  - [ ] Document to HTML/PDF
- [ ] Create migration risk matrix
- [ ] Add quality verification checks
- [ ] Create admin UI for pathway selection

**Acceptance Criteria:**
- [ ] Database table `preservation_migration_pathway` populated
- [ ] Each pathway has: source_puid, target_puid, tool, command, quality_loss_rating
- [ ] Admin can view/edit pathways
- [ ] Conversion logs pathway used
- [ ] Risk warnings for lossy conversions

**Files to Create/Modify:**
```
atom-ahg-plugins/ahgPreservationPlugin/data/migration_pathways.sql (new)
atom-ahg-plugins/ahgPreservationPlugin/modules/preservation/templates/pathwaysSuccess.php (new)
atom-ahg-plugins/ahgPreservationPlugin/modules/preservation/actions/pathwaysAction.class.php (new)
```

---

### GAP 5: JSON-LD Export
**Category:** Linked Data | **Points:** +1 | **Priority:** Low

| Attribute | Details |
|-----------|---------|
| **Current State** | Ontologies configured in external_services.php, no export |
| **Target State** | Full Schema.org JSON-LD output for SEO and interoperability |
| **Plugin** | Core AtoM or ahgAPIPlugin |
| **Effort** | 2 weeks |
| **Dependencies** | External services config ✅ |

**Implementation Tasks:**
- [ ] Create JsonLdExportService
- [ ] Map ISAD(G) fields to Schema.org:
  - [ ] ArchiveComponent
  - [ ] Collection
  - [ ] CreativeWork
  - [ ] Organization
  - [ ] Person
  - [ ] Place
- [ ] Add JSON-LD to public view pages (embedded)
- [ ] Add JSON-LD API endpoint: `/api/v2/descriptions/:id.jsonld`
- [ ] Add bulk export option
- [ ] Validate with Google Structured Data Testing Tool

**Acceptance Criteria:**
- [ ] Every public description page has JSON-LD in `<script type="application/ld+json">`
- [ ] Google can index structured data
- [ ] API returns valid JSON-LD
- [ ] Passes Schema.org validator

**Files to Create/Modify:**
```
atom-framework/src/Services/JsonLdExportService.php (new)
atom-ahg-plugins/ahgThemeB5Plugin/templates/layout.php (modify - add JSON-LD)
atom-ahg-plugins/ahgAPIPlugin/modules/apiv2/actions/descriptionsReadAction.class.php (modify)
```

---

### GAP 6: IIIF Auth API
**Category:** IIIF & Media | **Points:** +1 | **Priority:** Low

| Attribute | Details |
|-----------|---------|
| **Current State** | IIIF Presentation 3.0 implemented, no auth layer |
| **Target State** | IIIF Authentication API 1.0 for protected content |
| **Plugin** | atom-framework IiifViewer extension |
| **Effort** | 2 weeks |
| **Dependencies** | IiifManifestService ✅, ahgSecurityClearancePlugin ✅ |

**Implementation Tasks:**
- [ ] Implement IIIF Auth API 1.0 patterns:
  - [ ] Login interaction pattern
  - [ ] Clickthrough pattern
  - [ ] Kiosk pattern
  - [ ] External pattern
- [ ] Add auth services to manifests
- [ ] Integrate with AtoM ACL
- [ ] Integrate with Security Clearance levels
- [ ] Add token-based image access
- [ ] Handle degraded access (watermarked versions)

**Acceptance Criteria:**
- [ ] Restricted images show login prompt in Mirador/UV
- [ ] After login, full resolution available
- [ ] Clearance-based access works
- [ ] Embargo dates respected
- [ ] Degraded/watermarked fallback for unauthorized

**Files to Create/Modify:**
```
atom-framework/src/Extensions/IiifViewer/Services/IiifAuthService.php (new)
atom-framework/src/Extensions/IiifViewer/Controllers/IiifAuthController.php (new)
atom-framework/src/Extensions/IiifViewer/Services/IiifManifestService.php (modify)
```

---

### Implementation Priority Order

| Order | Gap | Rationale |
|-------|-----|-----------|
| 1 | Published SDK | Unlocks community contributions and integrations |
| 2 | Speech-to-Text | High demand for AV archives, AI differentiator |
| 3 | PII Detection | Privacy compliance requirement, builds on NER |
| 4 | Format Migration | Preservation maturity, mostly documentation |
| 5 | JSON-LD Export | SEO benefit, relatively simple |
| 6 | IIIF Auth API | Niche requirement, complex |

---

### Milestone Targets

| Milestone | Gaps Completed | Score | Target Date |
|-----------|----------------|-------|-------------|
| Current | 1 (Whisper) | 95/100 | 2026-01-20 ✅ |
| Alpha | 2 (SDK) | 96/100 | TBD |
| Beta | 3-4 (PII + Migration) | 98/100 | TBD |
| Release | 5-6 (JSON-LD + IIIF Auth) | 100/100 | TBD |

---

### Completion Checklist

When a gap is completed:
1. Update status in Progress Tracker (⬜ → ✅)
2. Add completion date
3. Update score in Executive Summary
4. Add to Document History
5. Tag release version

---

## Future Features / Roadmap

Features planned for development after the 100/100 score is achieved.

### FEATURE: Semantic Search with Fuzzy Logic
**Category:** Search & Discovery | **Priority:** High | **Status:** ⬜ Planned

Build a semantic search system for the AtoM AHG Framework with fuzzy logic and natural language search capabilities.

| Attribute | Details |
|-----------|---------|
| **Environment** | Server 192.168.0.112, PHP 8.3, MySQL 8, Elasticsearch 7.10 |
| **Database** | Uses Laravel Query Builder (Illuminate\Database), not Eloquent |

#### Requirements

**1. Thesaurus Database**

Create MySQL tables to store synonyms from multiple sources:
- `ahg_thesaurus_term` (source, term, language, domain)
- `ahg_thesaurus_synonym` (term_id, synonym, language, relation_type, confidence)
- `ahg_thesaurus_sync_log` (tracking sync jobs)

**2. Thesaurus Sync Services**

Create PHP services in `atom-framework/src/Services/Thesaurus/` that pull synonyms from:

| Source | API | Auth | Purpose |
|--------|-----|------|---------|
| **WordNet** | Datamuse API (https://api.datamuse.com/words?ml=term) | None | Free synonym lookup |
| **Wikidata** | SPARQL (https://query.wikidata.org/sparql) | None | Archival concepts (Q166118), multilingual labels |
| **Local Overlay** | JSON file | N/A | Region-specific terms not in WordNet/Wikidata |

**Seed Terms (GLAM Domain):**
```
archive, document, manuscript, photograph, correspondence, letter, record,
collection, fonds, museum, artifact, gallery, library, heritage, preservation,
colonial, war, government, church, land, immigration, slavery, labour
```

**Local Overlay Examples (South Africa):**
```
dompas, lokasie, bantustan, kofifi, apartheid, township, voortrekker,
boer, zulu, xhosa, afrikaans
```

**Wikidata Languages:** en, af, zu, xh, de, fr, pt, nl

**3. Elasticsearch Integration**

- Export synonyms to ES format: `/etc/elasticsearch/analysis/ahg_synonyms.txt`
- Create ES analyzer config with synonym filter
- PHP method to regenerate and reload synonyms

**4. CLI Commands**

Create Symfony CLI tasks in `atom-framework/lib/task/`:

| Command | Purpose |
|---------|---------|
| `thesaurus:sync-wordnet` | Pull synonyms from Datamuse/WordNet |
| `thesaurus:sync-wikidata` | Pull terms from Wikidata SPARQL |
| `thesaurus:export-elasticsearch` | Generate ES synonym file |
| `thesaurus:stats` | Show thesaurus statistics |

**5. Embedding Service (Phase 2 Prep)**

Create `EmbeddingService.php`:
- Connect to Ollama API (localhost:11434)
- Generate embeddings using nomic-embed-text model
- Normalize text (handle diacritics: ë→e, ê→e, etc.)
- Store embeddings for future vector search

**6. Search Service**

Create `SemanticSearchService.php`:
- Accept natural language queries
- Expand query using thesaurus synonyms
- Build ES query with fuzzy matching + synonyms
- Return ranked results

#### Deliverables

| # | Deliverable | Path |
|---|-------------|------|
| 1 | Database migration SQL | `atom-framework/data/sql/thesaurus_tables.sql` |
| 2 | ThesaurusService.php | `atom-framework/src/Services/Thesaurus/ThesaurusService.php` |
| 3 | WordNetSyncService.php | `atom-framework/src/Services/Thesaurus/WordNetSyncService.php` |
| 4 | WikidataSyncService.php | `atom-framework/src/Services/Thesaurus/WikidataSyncService.php` |
| 5 | EmbeddingService.php | `atom-framework/src/Services/Search/EmbeddingService.php` |
| 6 | SemanticSearchService.php | `atom-framework/src/Services/Search/SemanticSearchService.php` |
| 7 | CLI tasks | `atom-framework/lib/task/thesaurus*.class.php` |
| 8 | ES configuration | `atom-framework/config/elasticsearch/semantic_analyzer.json` |
| 9 | Local synonyms JSON | `atom-framework/data/thesaurus/local_synonyms.json` |
| 10 | README | `atom-framework/docs/SEMANTIC_SEARCH.md` |

#### Constraints

- Use GuzzleHttp for API calls
- Rate limit external API calls (100ms delay)
- Log all sync operations
- Handle API failures gracefully
- Code must be PHP CS Fixer compliant

#### Acceptance Criteria

- [ ] Thesaurus tables created with migrations
- [ ] WordNet sync populates 1000+ terms
- [ ] Wikidata sync adds multilingual labels
- [ ] Local overlay supports SA-specific terms
- [ ] ES synonyms file generated correctly
- [ ] Search expands queries using synonyms
- [ ] Fuzzy matching handles typos
- [ ] Embedding service connects to Ollama
- [ ] All CLI commands functional
- [ ] README documents usage

---

## International Standards Compliance

### Archival Description
| Standard | Status | Implementation |
|----------|--------|----------------|
| ISAD(G) | ✅ Native | Core AtoM |
| ISAAR-CPF | ✅ Native | Core AtoM |
| ISDIAH | ✅ Native | Core AtoM |
| ISDF | ✅ Native | Core AtoM |
| DACS | ✅ Supported | arDacsPlugin |
| RAD | ✅ Supported | sfRadPlugin |
| RiC-CM/RiC-O | ✅ Full | ahgRicExplorerPlugin |

### Metadata
| Standard | Status | Implementation |
|----------|--------|----------------|
| Dublin Core | ✅ Native | sfDcPlugin |
| MODS | ✅ Supported | sfModsPlugin |
| EAD 2002 | ✅ Import/Export | sfEadPlugin |
| EAD3 | ✅ Export | sfEadPlugin |
| EAC-CPF | ✅ Export | sfEacPlugin |
| PREMIS | ✅ Rights | ahgRightsPlugin |

### Museum
| Standard | Status | Implementation |
|----------|--------|----------------|
| SPECTRUM 5.0 | ✅ 14 procedures | ahgSpectrumPlugin |
| CCO | ✅ Full | ahgMuseumPlugin |
| CIDOC-CRM | ✅ Export | ahgMuseumPlugin |
| Object ID | ✅ Supported | ahgMuseumPlugin |

### Preservation
| Standard | Status | Implementation |
|----------|--------|----------------|
| OAIS (ISO 14721) | ✅ SIP/AIP/DIP | ahgPreservationPlugin |
| BagIt | ✅ Create/Validate | ahgPreservationPlugin |
| PRONOM | ✅ Registry sync | ahgPreservationPlugin |

### Privacy & Compliance
| Framework | Status | Implementation |
|-----------|--------|----------------|
| POPIA (South Africa) | ✅ Full | ahgPrivacyPlugin |
| GDPR (EU) | ✅ Full | ahgPrivacyPlugin |
| UK GDPR | ✅ Full | ahgPrivacyPlugin |
| CCPA (California) | ✅ Full | ahgPrivacyPlugin |
| PIPEDA (Canada) | ✅ Full | ahgPrivacyPlugin |
| NDPA (Nigeria) | ✅ Full | ahgPrivacyPlugin |
| DPA (Kenya) | ✅ Full | ahgPrivacyPlugin |

### Financial
| Standard | Status | Implementation |
|----------|--------|----------------|
| GRAP 103 (South Africa) | ✅ Full | ahgHeritageAccountingPlugin |
| IPSAS 45 | ✅ Templates | ahgHeritageAccountingPlugin |
| FRS 102 (UK) | ✅ Templates | ahgHeritageAccountingPlugin |
| GASB 34 (US) | ✅ Templates | ahgHeritageAccountingPlugin |

---

## External Services Configured

The framework has pre-configured integrations for:

### Linked Data
- Getty Vocabularies (AAT, TGN, ULAN) via SPARQL
- Wikidata via SPARQL
- VIAF (Virtual International Authority File)
- WorldCat

### Content
- Open Library (ISBN lookup)
- IIIF (Image/Presentation API 3.0)

### Rights
- RightsStatements.org
- Creative Commons
- Local Contexts

### Ontologies
- Schema.org
- Dublin Core Terms
- SKOS
- FOAF
- Europeana Data Model (EDM)
- CIDOC-CRM
- RiC-O

### AI Services
- OpenAI API
- Anthropic API

### Payment (South Africa)
- PayFast
- PayGate

---

## Methodology

This comparison was conducted by:

1. **Code Review:** Direct examination of `/usr/share/nginx/archive/atom-framework` and `/usr/share/nginx/archive/atom-ahg-plugins`
2. **Documentation Analysis:** Review of GitHub documentation at `ArchiveHeritageGroup/atom-extensions-catalog`
3. **Competitor Research:** Web research on official competitor websites and documentation
4. **Feature Verification:** Grep/glob searches for actual implementations

### Rating Scale
- **10/10:** Industry-leading, comprehensive implementation
- **8-9/10:** Strong implementation, minor gaps
- **6-7/10:** Adequate implementation, notable gaps
- **4-5/10:** Basic implementation, significant gaps
- **2-3/10:** Minimal implementation
- **0-1/10:** Not implemented

### Legend
- ✅ Fully implemented
- ⚠️ Partially implemented or via third-party
- ❌ Not available

---

## Plugin Dependency Analysis

### Architecture Overview

All AHG plugins are designed to run **100% autonomously** with only one required dependency:

```
ahgThemeB5Plugin (REQUIRED - Always Installed)
    │
    ├── All other plugins depend ONLY on this
    │
    └── Uses dynamic plugin detection for optional features
```

### Dependency Matrix

| Plugin | Hard Dependencies | Soft Dependencies | Standalone |
|--------|-------------------|-------------------|------------|
| **ahgThemeB5Plugin** | arDominionB5Plugin (base) | None | N/A (Required) |
| **ahgSecurityClearancePlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgMuseumPlugin** | ahgThemeB5Plugin | ahgLoanPlugin, ahgConditionPlugin | ✅ Yes |
| **ahgLibraryPlugin** | ahgThemeB5Plugin | ahgCartPlugin | ✅ Yes |
| **ahgGalleryPlugin** | ahgThemeB5Plugin | ahgLoanPlugin, ahgConditionPlugin | ✅ Yes |
| **ahgDAMPlugin** | ahgThemeB5Plugin | ahgPreservationPlugin | ✅ Yes |
| **ahgLoanPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgSpectrumPlugin** | ahgThemeB5Plugin | ahgConditionPlugin | ✅ Yes |
| **ahgConditionPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgPreservationPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgProvenancePlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgRightsPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgExtendedRightsPlugin** | ahgThemeB5Plugin | ahgRightsPlugin (enhanced) | ✅ Yes |
| **ahgPrivacyPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgAccessRequestPlugin** | ahgThemeB5Plugin | ahgSecurityClearancePlugin | ✅ Yes |
| **ahgRequestToPublishPlugin** | ahgThemeB5Plugin | ahgCartPlugin | ✅ Yes |
| **ahgResearchPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgCartPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgFavoritesPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgFeedbackPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgVendorPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgIiifCollectionPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahg3DModelPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgNerPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgHeritageAccountingPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgDonorAgreementPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgDataMigrationPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgMigrationPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgReportBuilderPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgDisplayPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgRicExplorerPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgAuditTrailPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgBackupPlugin** | ahgThemeB5Plugin | None | ✅ Yes |
| **ahgAPIPlugin** | ahgThemeB5Plugin | None | ✅ Yes |

### Dependency Types

**Hard Dependency:** Plugin will not function without it (only ahgThemeB5Plugin)

**Soft Dependency:** Plugin works without it, but features are enhanced when present
- Uses `checkPluginEnabled()` for runtime detection
- Gracefully degrades if dependency is missing
- No PHP class imports from other plugins

### Circular Dependencies

**✅ NONE DETECTED**

The dependency graph is a clean DAG (Directed Acyclic Graph):
- All plugins depend only on ahgThemeB5Plugin
- No cross-plugin PHP imports
- Soft dependencies use runtime detection

### Database Isolation

All plugins create isolated database tables with:
- Foreign keys only to core AtoM tables (`object`, `term`, `actor`, `information_object`)
- No foreign keys between plugin tables
- Self-contained schema per plugin

### Dynamic Plugin Detection Pattern

The theme uses this pattern for optional features:

```php
// In ahgThemeB5Plugin/modules/menu/templates/_mainMenu.php
function checkPluginEnabled($pluginName) {
    static $plugins = null;
    if ($plugins === null) {
        $conn = Propel::getConnection();
        $stmt = $conn->prepare('SELECT name FROM atom_plugin WHERE is_enabled = 1');
        $stmt->execute();
        $plugins = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    return isset($plugins[$pluginName]);
}

$hasMuseum = checkPluginEnabled('ahgMuseumPlugin');
$hasLibrary = checkPluginEnabled('ahgLibraryPlugin');
// ... conditionally show menu items
```

### Multi-Sector Plugin: ahgLoanPlugin

The ahgLoanPlugin uses an **Adapter Pattern** to support all sectors without dependencies:

```
ahgLoanPlugin (Standalone)
    ├── MuseumAdapter    → Works without ahgMuseumPlugin
    ├── GalleryAdapter   → Works without ahgGalleryPlugin
    ├── ArchiveAdapter   → Works without any sector plugin
    ├── LibraryAdapter   → Works without ahgLibraryPlugin
    └── DAMAdapter       → Works without ahgDAMPlugin
```

### Enable/Disable Order

**Enable Order (Recommended):**
1. ahgThemeB5Plugin (auto-enabled, locked)
2. ahgSecurityClearancePlugin (auto-enabled, locked)
3. Sector plugins (any order)
4. Supporting plugins (any order)
5. Feature plugins (any order)

**Disable Order:** Any order - no cascading failures

### Known Issues

| Issue | Plugin | Status | Resolution |
|-------|--------|--------|------------|
| ~~Disabled loan module still has routes~~ | ~~ahgMuseumPlugin~~ | ✅ Resolved | Loan routes moved to ahgLoanPlugin, loan.disabled deleted |

### Testing Isolation

Each plugin can be tested independently:
```bash
# Enable only specific plugin for testing
php bin/atom extension:enable ahgLibraryPlugin

# All other plugins can remain disabled
# Plugin will function correctly with only ahgThemeB5Plugin
```

---

## Document History

| Date | Version | Changes |
|------|---------|---------|
| 2026-01-20 | 1.0 | Initial comprehensive analysis |
| 2026-01-20 | 1.1 | Added 6% gap work-down list with implementation details |
| 2026-01-20 | 1.2 | Added plugin dependency analysis |
| 2026-01-20 | 1.3 | Resolved ahgMuseumPlugin loan issue (moved to ahgLoanPlugin) |
| 2026-01-20 | 1.4 | Added Future Features: Semantic Search with Fuzzy Logic |
| 2026-01-20 | 1.5 | **Score 94→95:** Marked Speech-to-Text (Whisper) as complete |
| 2026-01-21 | 1.6 | **PII Detection Enhanced:** Added ISAD access points integration (Subject, Place, Name, Date) and PDF redaction with viewer integration |

---

*Generated by AtoM AHG Framework+ Analysis Tool*
*The Archive and Heritage Group (Pty) Ltd*
