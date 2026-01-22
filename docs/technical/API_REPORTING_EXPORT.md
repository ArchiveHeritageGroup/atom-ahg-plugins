# AHG Framework - API, Reporting & Export Documentation

## Overview

This document catalogs all API endpoints, reporting systems, and export functionality developed for the AtoM AHG Framework.

---

## 1. REST API Development

### 1.1 Base AtoM REST API (arRestApiPlugin)

**Location:** `/plugins/arRestApiPlugin/`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/informationobjects` | GET | List archival descriptions |
| `/api/informationobjects/{id}` | GET | Single description details |
| `/api/actors` | GET | List authority records |
| `/api/actors/{id}` | GET | Single actor details |
| `/api/repositories` | GET | List repositories |
| `/api/repositories/{id}` | GET | Single repository details |
| `/api/taxonomies/{id}/terms` | GET | Get taxonomy terms |

**Authentication:**
```bash
curl -H "REST-API-Key: YOUR_KEY" https://psis.theahg.co.za/api/informationobjects
```

**Key Files:**
- `QubitApiAction.class.php` - Base API action with authentication
- Property name: `restApiKey` (stored in user properties)

### 1.2 RIC Semantic Search API

**Location:** `/ric-dashboard/api/`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/ric-dashboard/api/stats.php` | GET | RIC statistics JSON |
| `/ric-dashboard/api/entities.php` | GET | Entities JSON endpoint |
| `/ric-dashboard/api/graph.php` | GET | Graph data for D3.js |
| `/ric-dashboard/api/search.php` | GET | Semantic search JSON |

**Features:**
- SPARQL queries to Apache Jena Fuseki
- RiC-CM ontology support
- Cross-walk between AtoM and RIC entities

### 1.3 Internal API Endpoints (AHG Plugins)

| Plugin | Endpoint | Description |
|--------|----------|-------------|
| ahgBackupPlugin | `/api/backup/status` | Backup job status |
| ahgAuditTrailPlugin | `/api/audit/log` | Audit entries JSON |
| ahgResearchPlugin | `/api/research/collections` | Researcher collections |
| ahgConditionPlugin | `/api/condition/reports` | Condition reports JSON |

---

## 2. OAI-PMH (Open Archives Initiative)

### 2.1 Base AtoM OAI-PMH (arOaiPlugin)

**Endpoint:** `/index.php/;oai`

**Verbs Supported:**
- `Identify` - Repository identification
- `ListMetadataFormats` - Available formats
- `ListSets` - Repository sets
- `ListIdentifiers` - Record identifiers
- `ListRecords` - Full records
- `GetRecord` - Single record

**Formats:**
- Dublin Core (`oai_dc`)
- EAD (`oai_ead`)

### 2.2 Configuration

**Location:** Admin → Settings → OAI Repository

| Setting | Description |
|---------|-------------|
| Repository Identifier | e.g., `theahg.co.za` |
| Administrator Email | Contact for OAI issues |
| Sets Enabled | Group by repository/collection |
| Resume Token Limit | Records per page |

---

## 3. Reporting System

### 3.1 Central Reports Dashboard

**URL:** `/index.php/reports`

**Statistics Cards:**
- Total Archival Descriptions
- Total Authority Records
- Total Digital Objects
- Recent Updates

### 3.2 Report Categories

| Category | Reports |
|----------|---------|
| **Archive** | Descriptions by level, Repository statistics, Physical storage |
| **Library** | Catalog by format, Circulation stats, Holdings |
| **Museum** | Objects by type, Acquisition, Condition summaries |
| **Gallery** | Artworks, Exhibitions, Loans |
| **DAM** | Digital objects by type, Storage usage, Format distribution |
| **Researchers** | Active researchers, Request statistics, Visit logs |

### 3.3 Sector-Specific Reports

**Archive Reports:**
- Descriptions by Level of Description
- Holdings by Repository
- Accession Register
- Finding Aid Generation
- Physical Object Holdings

**Library Reports:**
- Catalog Statistics
- Items by Call Number
- Circulation History

**Museum Reports:**
- Objects by Classification
- Provenance Chains
- Condition Assessment Summary
- Acquisition by Year

**DAM Reports:**
- Digital Objects by MIME Type
- Storage Usage Analysis
- Fixity Check Results
- IPTC Metadata Coverage

### 3.4 Reports Dashboard Features

- **Chart.js Visualizations** - Bar, pie, line charts
- **Export Formats** - HTML, PDF, CSV, XLSX, JSON
- **Scheduled Reports** - Cron-based generation
- **Email Delivery** - Automated report distribution
- **Saved Configurations** - Reusable report parameters

---

## 4. Export Functionality

### 4.1 Standard Export Formats (Base AtoM)

| Format | Module | Extension |
|--------|--------|-----------|
| EAD 2002 | sfEadPlugin | `.xml` |
| EAD3 | sfEad3Plugin | `.xml` |
| Dublin Core | sfDcPlugin | `.xml` |
| MODS | sfModsPlugin | `.xml` |
| SKOS | sfSkosPlugin | `.xml` |
| CSV | clipboard/export | `.csv` |

### 4.2 Export Access Points

1. **Clipboard Export** - Select records → Export
2. **Individual Record** - View page → Export sidebar
3. **Admin Bulk Export** - Admin → Import/Export
4. **CLI Export** - `php symfony export:bulk`

### 4.3 Sector-Specific Exports

| Sector | Formats | Notes |
|--------|---------|-------|
| **Archive** | EAD, CSV, DC | Standard AtoM |
| **Library** | MARC, BibTeX, DC, CSV | ahgLibraryPlugin |
| **Museum** | CCO, Spectrum CSV, CIDOC-CRM | sfMuseumPlugin |
| **Gallery** | CCO, DC | ahgGalleryPlugin |
| **DAM** | IPTC, DC, CSV | ahgDAMPlugin |

### 4.4 Finding Aid Generation

**URL:** `/informationobject/findingAid/{slug}`

**Features:**
- PDF/A output format
- Customizable templates
- Hierarchical structure
- Digital object thumbnails
- Admin regeneration option

### 4.5 TIFF to PDF Merge Tool

**URL:** `/index.php/tiff-pdf-merge`

| Feature | Description |
|---------|-------------|
| Multi-TIFF Upload | Batch upload images |
| Reorder | Drag-drop page ordering |
| PDF/A Output | Archival-quality output |
| Job Queue | Background processing |
| Integration | Links to Information Objects |

---

## 5. Outstanding / Parked Items

### 5.1 Outstanding (2 items)

| Item | Description | Status |
|------|-------------|--------|
| **JSON-LD Export** | Structured data for search engines/linked data | ❌ Not Started |
| **Wikidata/VIAF Linking** | Authority record enrichment | ❌ Not Started |

### 5.2 Parked Export Items

| Item | Description |
|------|-------------|
| Researcher Finding Aid | PDF export from custom collection lists |
| CIDOC-CRM Export | Museum standard RDF mapping |
| Public SPARQL Endpoint | External query access to RIC triplestore |
| Mobile App API | Extended REST API for mobile |

---

## 6. Background Jobs (Gearman)

### 6.1 Export Jobs
```
arXmlExportSingleFileJob    - Single XML export
arActorCsvExportJob         - Actor CSV bulk export
arActorXmlExportJob         - Actor EAC-CPF export
arRepositoryCsvExportJob    - Repository CSV export
arGenerateReportJob         - Scheduled report generation
arPhysicalObjectCsvHoldingsReportJob - Physical storage report
arValidateCsvJob            - CSV import validation
```

### 6.2 Processing Jobs
```
arUpdateEsIoDocumentsJob    - Elasticsearch reindex
arFindingAidJob             - PDF finding aid generation
arTiffPdfMergeJob           - Multi-TIFF to PDF conversion
```

---

## 7. Key Files Reference

### API Files

| File | Purpose |
|------|---------|
| `plugins/arRestApiPlugin/lib/QubitApiAction.class.php` | REST API authentication |
| `ric-dashboard/api/*.php` | RIC semantic API endpoints |

### Report Files

| File | Purpose |
|------|---------|
| `ahgThemeB5Plugin/modules/reports/actions/` | Report actions |
| `ahgThemeB5Plugin/modules/reports/templates/indexSuccess.php` | Dashboard |
| `atom-framework/src/Repositories/*Repository.php` | Data queries |

### Export Files

| File | Purpose |
|------|---------|
| `lib/task/export/*.php` | CLI export tasks |
| `plugins/sfEadPlugin/` | EAD export |
| `plugins/sfDcPlugin/` | Dublin Core export |
| `atom-framework/src/Services/ImportExport/` | Import/Export service |

---

## 8. Configuration

### 8.1 REST API Key Setup

1. Admin → Users → Edit User
2. Generate REST API Key
3. Use in header: `REST-API-Key: {key}`

### 8.2 Report Dashboard Access

Admin → Reports (requires authenticated user)

### 8.3 Export Permissions

Controlled via AtoM ACL:
- `read` - View records
- `export` - Export functionality (implied by read)
- `admin` - Bulk export, regenerate finding aids

---

## 9. Integration Points

### 9.1 Elasticsearch

- Search results feed reports
- Index sync via `arUpdateEsIoDocumentsJob`

### 9.2 Apache Jena Fuseki

- RIC triplestore for linked data
- SPARQL queries for semantic search

### 9.3 Gearman

- Background job processing
- Large export handling
- Scheduled tasks

---

## Summary

| Category | Status | Items |
|----------|--------|-------|
| REST API | ✅ Complete | 6+ endpoints |
| OAI-PMH | ✅ Complete | Standard verbs |
| Reports Dashboard | ✅ Complete | 30+ reports |
| Standard Export | ✅ Complete | EAD, DC, CSV, MODS |
| Sector Export | ✅ Complete | MARC, CCO, IPTC |
| Finding Aid | ✅ Complete | PDF generation |
| TIFF-PDF Merge | ✅ Complete | Background jobs |
| JSON-LD Export | ❌ Outstanding | Linked data foundation |
| Wikidata/VIAF | ❌ Outstanding | Authority enrichment |
