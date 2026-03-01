# AtoM Heratio — Data Ingest Plugin

**Version:** 1.1.0
**Author:** The Archive and Heritage Group (Pty) Ltd
**Category:** Data Ingest & Import

---

## Overview

The Data Ingest plugin provides an OAIS-aligned 6-step batch ingestion pipeline for AtoM Heratio. It supports importing archival descriptions across all GLAM sectors (Archives, Museums, Libraries, Galleries, DAM) as well as accession records following NARSSA transfer workflows.

---

## Key Features

### Multi-Standard Description Import
- 7 descriptive standards: ISAD(G), RAD, DACS, Dublin Core, MODS, SPECTRUM 5.1, CCO
- Auto-detection of CSV encoding and delimiter
- Column auto-mapping with intelligent header matching
- Saved mapping profiles for repeated imports
- Support for CSV, ZIP, EAD, and server directory sources

### Accession CSV Ingest
- Full accession record creation with all standard AtoM fields
- Donor information with contact details (auto-creates donor records)
- Accession events (multiple per record, pipe-delimited)
- Taxonomy-controlled fields (acquisition type, resource type, processing status/priority)
- Extended fields from ahgAccessionManagePlugin (intake notes, containers)
- Round-trip compatible with Accession CSV Export
- NARSSA transfer manifest workflow support

### 6-Step Wizard
1. **Configure** — Record type, sector, standard, repository, hierarchy, processing
2. **Upload** — File upload with auto-detection
3. **Map & Enrich** — Column mapping, transforms, metadata extraction
4. **Validate** — Required fields, duplicates, hierarchy, dates
5. **Preview** — Hierarchical tree visualization, row approve/exclude
6. **Commit** — Background processing with real-time progress

### AI Processing Pipeline
- Virus scanning (ClamAV)
- OCR text extraction (Tesseract)
- Named Entity Recognition (spaCy)
- Text summarization
- Spell checking (aspell)
- Format identification (Siegfried/PRONOM)
- Face detection and matching
- Machine translation (Argos Translate)

### OAIS Package Generation
- SIP (Submission Information Package)
- AIP (Archival Information Package) with PREMIS metadata
- DIP (Dissemination Information Package)

### Shared Features
- Background job execution with progress tracking
- Security classification support
- Configurable defaults via AHG Settings
- Full audit trail integration
- Dashboard with session management

---

## CLI Commands

```bash
# Process ingest job
php symfony ingest:commit --job-id=123
php symfony ingest:commit --session-id=456
```

---

## Technical Requirements

- AtoM Heratio Framework v2.0+
- PHP 8.1+
- MySQL 8.0+
- ahgCorePlugin (dependency)
- ahgAccessionManagePlugin (optional, for extended accession fields)

---

## Compliance & Standards

- OAIS-aligned ingestion pipeline
- NARSSA transfer workflow support
- ISAD(G) / RAD / DACS / Dublin Core / MODS / SPECTRUM 5.1 / CCO standards
- SHA-256 checksums for digital object integrity
- Full audit trail integration
- Compatible with POPIA/NARSSA archival requirements

---

## Admin UI

Access via **Admin > Ingest** (`/ingest`).

The dashboard shows all ingest sessions with status, progress, and actions (resume, view report, cancel, rollback).

---

*The Archive and Heritage Group (Pty) Ltd*
*https://theahg.co.za*
