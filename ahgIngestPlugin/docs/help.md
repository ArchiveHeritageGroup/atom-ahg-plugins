# Data Ingest — User & Admin Guide

**Plugin:** ahgIngestPlugin v1.1.0

---

## Table of Contents

1. [Introduction](#introduction)
2. [Wizard Overview](#wizard-overview)
3. [Record Types](#record-types)
4. [Step 1: Configure](#step-1-configure)
5. [Step 2: Upload](#step-2-upload)
6. [Step 3: Map & Enrich](#step-3-map--enrich)
7. [Step 4: Validate](#step-4-validate)
8. [Step 5: Preview](#step-5-preview)
9. [Step 6: Commit](#step-6-commit)
10. [Accession CSV Ingest](#accession-csv-ingest)
11. [CLI Commands](#cli-commands)
12. [Processing Options](#processing-options)
13. [OAIS Packages](#oais-packages)
14. [Settings](#settings)
15. [Troubleshooting](#troubleshooting)

---

## Introduction

The Data Ingest plugin provides an OAIS-aligned 6-step batch ingestion pipeline for AtoM Heratio. It supports importing:

- **Archival Descriptions** — ISAD(G), RAD, DACS, Dublin Core, MODS, SPECTRUM, CCO records
- **Accessions** — Accession records with donors, events, and extended fields

Data sources include CSV files, ZIP archives with digital objects, EAD XML, and server directories.

---

## Wizard Overview

The ingest wizard guides users through 6 steps:

| Step | Name | Purpose |
|------|------|---------|
| 1 | Configure | Record type, sector, standard, repository, hierarchy, processing options |
| 2 | Upload | Upload CSV, ZIP, EAD files or point to server directory |
| 3 | Map & Enrich | Map CSV columns to target fields, apply transforms, match digital objects |
| 4 | Validate | Check required fields, dates, duplicates, hierarchy references |
| 5 | Preview | Visual tree of records to be created, approve/exclude individual rows |
| 6 | Commit | Execute import as background job, monitor progress, view completion report |

---

## Record Types

### Archival Descriptions (default)

Creates `information_object` records in AtoM. Supports all GLAM sector descriptive standards.

### Accessions

Creates `accession` records in AtoM, including:
- Core accession fields (number, title, dates, scope)
- Taxonomy-controlled fields (acquisition type, resource type, processing status/priority)
- Donor information (name, address, contact details)
- Accession events (types, dates, agents)
- Alternative identifiers
- Extended fields from ahgAccessionManagePlugin (intake notes, containers)

Select the record type in Step 1 using the **Record Type** toggle.

---

## Step 1: Configure

### Record Type
- **Archival Descriptions** — Standard archival record import
- **Accessions** — Accession record import (sector locked to Archive, hierarchy hidden)

### Sector & Standard (Descriptions only)
- Select the GLAM sector (Archive, Museum, Library, Gallery, DAM)
- Choose the descriptive standard — options filter based on sector

### Repository
Optional. All imported records will be linked to the selected repository.

### Hierarchy Placement (Descriptions only)
- **Top-level** — Records placed directly under root
- **Under existing record** — Search for and select a parent
- **Create new parent** — Auto-create a fonds/collection as parent
- **CSV hierarchy** — Use legacyId/parentId columns to build hierarchy from CSV data

### Output Options
- Create AtoM records (default: on)
- Generate SIP/AIP/DIP packages (OAIS)
- Generate thumbnails and reference images

### Processing Options
AI and processing actions to run after commit. See [Processing Options](#processing-options).

---

## Step 2: Upload

Upload one of the following:
- **CSV file** — Column headers auto-detected, encoding/delimiter inferred
- **ZIP archive** — Contains CSV + digital object files
- **EAD XML** — Encoded Archival Description file
- **Server directory** — Path to directory of files already on the server

---

## Step 3: Map & Enrich

### CSV Import Mode
- View auto-mapped column mappings (source header → target field)
- Manually adjust mappings via dropdown
- Set default values for unmapped columns
- Apply transforms: trim, uppercase, lowercase, titlecase, date_iso, strip_html
- Select digital object matching strategy (filename, legacyId, title)
- Load saved mapping profiles from ahgDataMigrationPlugin

### Directory Import Mode
- Enter metadata that applies to all files in the directory
- Fields organized by descriptive standard groups

### Accession Mode
When importing accessions, the target field dropdown shows accession-specific fields:
- accessionNumber, title, acquisitionDate, sourceOfAcquisition
- Taxonomy fields: acquisitionType, resourceType, processingStatus, processingPriority
- Donor fields: donorName, donorStreetAddress, donorCity, etc.
- Event fields: accessionEventTypes, accessionEventDates (pipe-delimited)
- Extended fields: intakeNotes, intakePriority, containerType, containerLabel

---

## Step 4: Validate

Validation runs automatically and reports:
- **Errors** — Duplicate legacyIds, missing parent references (blocks commit)
- **Warnings** — Empty required fields, unrecognized levels/dates, unmatched digital objects

### Accession Validation
- Duplicate accession numbers within batch (error)
- Accession number already exists in system (warning)
- Acquisition date format check
- Taxonomy term existence check (missing terms created automatically)
- Event type/date count mismatch

---

## Step 5: Preview

Visual tree showing all records to be created:
- Expand/collapse hierarchy
- View row details (title, level, legacy ID, digital object)
- Exclude individual rows from import

---

## Step 6: Commit

- Click **Start Import** to launch background job
- Progress bar updates every 2 seconds via AJAX polling
- Completion report shows: records created, digital objects imported, errors

The job runs as: `php symfony ingest:commit --job-id=<id>`

---

## Accession CSV Ingest

### CSV Format

The accession CSV follows base AtoM's `csv:accession-import` format with extensions:

| Column | Required | Description |
|--------|----------|-------------|
| accessionNumber | Yes | Unique accession identifier |
| title | Yes | Accession title |
| acquisitionDate | No | Date acquired (YYYY-MM-DD) |
| sourceOfAcquisition | No | Source/provenance |
| locationInformation | No | Physical location |
| receivedExtentUnits | No | Quantity/extent |
| scopeAndContent | No | Description of contents |
| appraisal | No | Appraisal notes |
| archivalHistory | No | Custodial history |
| processingNotes | No | Processing information |
| acquisitionType | No | Taxonomy term (e.g., Donation, Purchase) |
| resourceType | No | Taxonomy term |
| processingStatus | No | Taxonomy term |
| processingPriority | No | Taxonomy term |
| donorName | No | Creates/links donor record |
| donorStreetAddress | No | Donor address |
| donorCity | No | Donor city |
| donorRegion | No | Donor region/province |
| donorCountry | No | Donor country |
| donorPostalCode | No | Donor postal code |
| donorTelephone | No | Donor phone |
| donorEmail | No | Donor email |
| donorContactPerson | No | Donor contact person |
| donorNote | No | Donor notes |
| accessionEventTypes | No | Pipe-delimited event types |
| accessionEventDates | No | Pipe-delimited event dates |
| accessionEventAgents | No | Pipe-delimited event agents |
| intakeNotes | No | Extended: intake notes |
| intakePriority | No | Extended: High/Medium/Low/Urgent |
| containerType | No | Extended: container type |
| containerLabel | No | Extended: container label |
| containerBarcode | No | Extended: barcode |
| containerQuantity | No | Extended: quantity |
| culture | No | Language code (default: en) |

### NARSSA Workflow

The accession ingest supports the NARSSA transfer workflow:
1. Department creates transfer manifest (CSV)
2. Review and approve items for transfer
3. Import approved items as accessions via the ingest wizard
4. Process intake: condition checks, measurements
5. Apply workflows and generate reports

### Round-Trip Compatibility

Accessions can be exported from **Admin > Export > Accession CSV** in the same format, enabling full round-trip import/export.

---

## CLI Commands

```bash
# Process a specific ingest job
php symfony ingest:commit --job-id=123

# Create and process a job for a session
php symfony ingest:commit --session-id=456
```

---

## Processing Options

| Option | Backend | Description |
|--------|---------|-------------|
| Virus Scan | ClamAV | Scan uploaded files for malware |
| OCR | Tesseract | Extract text from images/PDFs |
| NER | Python/spaCy | Extract named entities (persons, places, organizations) |
| Summarize | Python | Auto-generate content summaries |
| Spell Check | aspell | Check spelling and grammar |
| Format ID | Siegfried | Identify file formats via PRONOM registry |
| Face Detection | OpenCV/AWS | Detect faces and match to authority records |
| Translate | Argos Translate | Offline machine translation |

---

## OAIS Packages

| Package | Description |
|---------|-------------|
| SIP | Submission Information Package — JSON manifest + checksums |
| AIP | Archival Information Package — Structured directory with PREMIS metadata |
| DIP | Dissemination Information Package — Access URLs and slugs |

---

## Settings

Configure defaults at **Admin > AHG Settings > Data Ingest Defaults**:

| Setting | Description |
|---------|-------------|
| Default Sector | Default GLAM sector for new sessions |
| Default Standard | Default descriptive standard |
| Virus Scan | Enable by default |
| OCR | Enable by default |
| NER | Enable by default |
| Summarize | Enable by default |
| Spell Check | Enable by default |
| Format ID | Enable by default |
| Face Detection | Enable by default |
| Translate | Enable by default |
| Translation Language | Default target language |
| Create Records | Create AtoM records by default |
| Generate SIP/AIP/DIP | Generate OAIS packages by default |
| Thumbnails | Generate thumbnails by default |
| Reference Images | Generate reference images by default |

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Job stuck at 0% | Check PHP error log; ensure background process can run |
| "Permission denied" on upload | Check PHP upload_max_filesize and post_max_size |
| CSV encoding issues | Ensure CSV is UTF-8; use the encoding auto-detection |
| Duplicate accession numbers | Check existing accessions; use unique identifiers |
| Taxonomy term not found | Terms are created automatically if not found |
| Digital objects not matched | Verify filenames match; check matching strategy |
| Import timeout | Job runs in background; check with `ps aux | grep ingest` |

---

*The Archive and Heritage Group (Pty) Ltd*
