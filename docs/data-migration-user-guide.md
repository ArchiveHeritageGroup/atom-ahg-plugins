# AtoM AHG Framework - Data Migration Tool

## User Guide

**Plugin Version:** 1.2.0  
**Last Updated:** 2026-01-17  
**Plugin:** ahgDataMigrationPlugin

---

## Table of Contents

1. [Overview](#1-overview)
2. [Accessing the Tool](#2-accessing-the-tool)
3. [Supported Source Systems](#3-supported-source-systems)
4. [Web Interface Workflow](#4-web-interface-workflow)
5. [Field Mapping](#5-field-mapping)
6. [Preservica Import/Export](#6-preservica-importexport)
7. [Background Jobs](#7-background-jobs)
8. [CLI Commands](#8-cli-commands)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Overview

The Data Migration Tool enables importing records from various archival and collection management systems into AtoM. It supports:

- **CSV and Excel files** from multiple source systems
- **XML formats** (Preservica OPEX, EAD)
- **Preservica packages** (PAX/XIP with digital objects)
- **Sector-specific mappings** (Archives, Museum, Library, Gallery, DAM)
- **Background processing** for large datasets via Gearman
- **Field transformation** and validation
- **Rights import** (PREMIS, SecurityDescriptor, dc:rights, MODS, EAD)
- **Provenance/history import** from OPEX

---

## 2. Accessing the Tool

### Web Interface

Navigate to: `https://[your-domain]/dataMigration`

Or: **Admin → Import/Export → Data Migration**

### Required Permissions

- Administrator access
- Or `import` permission in user group

---

## 3. Supported Source Systems

### Collection Management Systems

| System | Formats | Target Sector |
|--------|---------|---------------|
| **ArchivesSpace** | CSV/JSON | Archives, Accessions, Agents, Repositories |
| **Vernon CMS** | CSV/Excel | Museum |
| **PastPerfect** | CSV | Museum |
| **CollectiveAccess** | CSV | Multi-sector |
| **Filemaker Pro** | CSV | Any |
| **WDB** | CSV | Archives |
| **PSIS** | Excel (83 fields) | Library |

### Preservation Systems

| System | Formats | Features |
|--------|---------|----------|
| **Preservica** | OPEX XML | Metadata, rights, provenance import/export |
| **Preservica** | PAX/XIP (ZIP) | Metadata + digital objects |

### Standard Formats

| Format | Use Case |
|--------|----------|
| CSV | Universal import |
| Excel (.xlsx, .xls) | Spreadsheet data |
| XML | EAD, Dublin Core |

---

## 4. Web Interface Workflow

### Step 1: Upload File

1. Go to `/dataMigration`
2. Click **"Choose File"** or drag-and-drop
3. Supported: `.csv`, `.xlsx`, `.xls`, `.xml`, `.opex`, `.pax`, `.zip`
4. Click **"Upload"**

The system auto-detects:
- File format (CSV, Excel, XML)
- Source system (based on headers/structure)
- Sector type (Archives, Museum, Library, etc.)

### Step 2: Select or Create Mapping

**Use Existing Mapping:**
1. Select from dropdown (e.g., "Vernon CMS (Museum)")
2. Click **"Load Mapping"**

**Create New Mapping:**
1. Click **"New Mapping"**
2. Enter name (e.g., "My Museum Import")
3. Select target sector
4. Click **"Create"**

### Step 3: Map Fields

The mapping interface shows:
- **Left column**: Your source fields (from uploaded file)
- **Right column**: AtoM target fields

For each source field:
1. Click the dropdown
2. Select matching AtoM field
3. Optionally set **transformation** rules

**Field Transformations:**
- `trim` - Remove whitespace
- `uppercase` / `lowercase` - Case conversion
- `date:Y-m-d` - Date formatting
- `prepend:/uploads/` - Add prefix to paths
- `split:|` - Split multi-value fields

### Step 4: Preview

1. Click **"Preview"**
2. Review first 10-20 records
3. Check field mappings are correct
4. Verify hierarchy (parent-child relationships)

### Step 5: Import

**Option A: Export to AtoM CSV**
1. Click **"Export AtoM CSV"**
2. Download the transformed CSV
3. Use AtoM's built-in CSV Import (Admin → Import → CSV)

**Option B: Direct Import (Large Files)**
1. Click **"Background Job"**
2. Job queued to Gearman workers
3. Monitor progress at `/dataMigration/jobs`

---

## 5. Field Mapping

### Core AtoM Fields

| AtoM Field | Description | Required |
|------------|-------------|----------|
| `legacyId` | Unique ID from source system | Yes |
| `parentId` | Parent's legacyId for hierarchy | No |
| `title` | Record title | Yes |
| `identifier` | Reference code | No |
| `scopeAndContent` | Description/scope | No |
| `levelOfDescription` | Fonds/Series/File/Item | Yes |
| `repository` | Repository name or ID | No |
| `culture` | Language code (en, af, etc.) | No |

### Digital Object Fields

| Field | Description |
|-------|-------------|
| `digitalObjectPath` | Path to file (relative or absolute) |
| `digitalObjectURI` | External URL |
| `digitalObjectChecksum` | MD5/SHA256 for verification |

### Multi-Value Fields

Use pipe `|` separator for multiple values:
```
subjectAccessPoints: History|World War II|Military
placeAccessPoints: South Africa|Johannesburg
nameAccessPoints: Jan Smuts|Louis Botha
```

### Hierarchy Example
```csv
legacyId,parentId,title,levelOfDescription
F001,,Municipal Archives,Fonds
S001,F001,Council Minutes,Series
F001-001,S001,Minutes 1950-1960,File
F001-001-001,F001-001,Meeting 1950-01-15,Item
```

---

## 6. Preservica Import/Export

### OPEX Import

OPEX (Open Preservation Exchange) is Preservica's XML metadata format.

**Web Interface:**
1. Upload `.opex` or `.xml` file
2. Select "Preservica OPEX" mapping
3. Map fields or use defaults
4. Preview and import

**CLI:**
```bash
php symfony preservica:import /path/to/file.opex
php symfony preservica:import /path/to/file.opex --repository=5
php symfony preservica:import /path/to/file.opex --dry-run
```

**OPEX Rights Extraction:**
The importer automatically extracts rights from:
- `SecurityDescriptor` elements
- `dc:rights` Dublin Core
- `dcterms:license` 
- MODS `<accessCondition>`
- EAD `<userestrict>` and `<accessrestrict>`

**Provenance Import:**
OPEX `<opex:History>` elements are imported to `provenance_event` table.

### PAX/XIP Import

PAX packages contain metadata (XIP XML) plus content files.

**Web Interface:**
1. Upload `.pax` or `.zip` file
2. Select "Preservica PAX/XIP" mapping
3. Digital objects extracted automatically
4. Preview and import

**CLI:**
```bash
php symfony preservica:import /path/to/package.pax --format=xip
php symfony preservica:import /path/to/directory --batch
```

### Preservica Export

Export AtoM records to Preservica format:

**CLI:**
```bash
# Export single record
php symfony preservica:export 123

# Export with hierarchy
php symfony preservica:export 123 --hierarchy

# Export to XIP/PAX format
php symfony preservica:export 123 --format=xip

# Export entire repository
php symfony preservica:export --repository=5
```

**Output Location:** `/uploads/exports/preservica/`

---

## 7. Background Jobs

For large imports (1000+ records), use background processing:

### Starting a Background Job

1. Complete field mapping
2. Click **"Background Job"** instead of direct import
3. Job queued to Gearman workers

### Monitoring Jobs

Navigate to: `/dataMigration/jobs`

| Status | Description |
|--------|-------------|
| `queued` | Waiting for worker |
| `running` | Currently processing |
| `completed` | Finished successfully |
| `failed` | Error occurred |

### Job Details

Click any job to see:
- Records processed / total
- Errors encountered
- Processing time
- Download results

---

## 8. CLI Commands

### List Available Mappings
```bash
php symfony migration:import --list-mappings
```

Output:
```
ARCHIVES:
  [2] ArchivesSpace Resources
  [11] Preservica OPEX
  [12] Preservica PAX/XIP

MUSEUM:
  [10] Vernon CMS (Museum)

LIBRARY:
  [8] PSIS Full Import (83 fields)
```

### Import with Mapping
```bash
# By mapping ID
php symfony migration:import /path/to/file.csv --mapping=10

# By mapping name
php symfony migration:import /path/to/file.csv --mapping="Vernon CMS"

# With options
php symfony migration:import /path/to/file.csv --mapping=10 \
    --repository=5 \
    --culture=en \
    --update
```

### Dry Run (Preview Only)
```bash
php symfony migration:import /path/to/file.csv --mapping=10 --dry-run
```

### Preservica Commands
```bash
# Show Preservica info
php symfony preservica:info

# Import OPEX
php symfony preservica:import /path/to/file.opex

# Import PAX/XIP
php symfony preservica:import /path/to/package.pax --format=xip

# Export to OPEX
php symfony preservica:export 123 --format=opex

# Export to PAX
php symfony preservica:export 123 --format=xip --hierarchy
```

---

## 9. Troubleshooting

### File Upload Fails

**Problem:** File too large  
**Solution:** Increase PHP limits in `/etc/php/8.3/fpm/php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```

### Mapping Not Found

**Problem:** Source columns not detected  
**Solution:** Ensure CSV has headers in first row, UTF-8 encoding

### Hierarchy Not Working

**Problem:** Parent-child relationships broken  
**Solution:** 
- Ensure `legacyId` is unique
- Ensure `parentId` matches a valid `legacyId`
- Parents must appear before children in file

### Digital Objects Not Importing

**Problem:** Files not attaching to records  
**Solution:**
- Check `digitalObjectPath` is correct
- Verify files exist at specified path
- Use absolute paths or paths relative to AtoM root

### Background Job Stuck

**Problem:** Job shows "running" but no progress  
**Solution:**
```bash
# Check Gearman workers
ps aux | grep jobs:worker

# Restart workers
sudo systemctl restart atom-worker
```

### OPEX Rights Not Importing

**Problem:** Rights not appearing on records  
**Solution:**
- Verify OPEX contains `<SecurityDescriptor>` or `<dc:rights>`
- Check `ahg_rights_statement` table for imported rights
- Ensure ahgRightsPlugin is enabled

---

## Quick Reference

| Task | Web UI | CLI |
|------|--------|-----|
| Import CSV | `/dataMigration` → Upload | `php symfony migration:import file.csv --mapping=X` |
| Import OPEX | `/dataMigration` → Upload | `php symfony preservica:import file.opex` |
| Import PAX | `/dataMigration` → Upload | `php symfony preservica:import file.pax --format=xip` |
| Export OPEX | N/A | `php symfony preservica:export 123` |
| Export PAX | N/A | `php symfony preservica:export 123 --format=xip` |
| View Jobs | `/dataMigration/jobs` | N/A |
| List Mappings | Dropdown | `php symfony migration:import --list-mappings` |

---

## Version History

| Version | Changes |
|---------|---------|
| 1.2.0 | Added Preservica OPEX/PAX support, rights import, provenance import, Gearman jobs |
| 1.1.0 | Added sector-specific CSV exporters |
| 1.0.0 | Initial release with field mapping UI |

---

**Need Help?**

- Check `/dataMigration/jobs` for import status
- Review error logs: `/log/qubit.log`
- Contact: support@theahg.co.za
