# AtoM Heratio — Accession Management V2

## Feature Overview

**Plugin:** ahgAccessionManagePlugin v2.0.0
**Author:** The Archive and Heritage Group (Pty) Ltd
**Date:** February 2026
**Framework:** AtoM Heratio v2.8.2

---

## Overview

Accession Management V2 transforms accessions from basic catalogue entries into first-class entities with a structured intake queue, formal appraisal workflow, physical container tracking, PREMIS-aligned rights inheritance, and full multi-tenant isolation.

This upgrade maintains full backward compatibility with base AtoM's `accession` table while adding a 1:1 extension table (`accession_v2`) and 14 additional tables for the new functionality.

---

## Key Features

### M1: Intake Queue

- **Status Workflow:** Six-stage lifecycle (draft → submitted → under_review → accepted/rejected/returned) with enforced state machine transitions
- **Configurable Checklists:** Reusable intake checklist templates with per-accession item tracking and completion progress
- **Assignment System:** Assign accessions to staff members for review with notification timeline entries
- **Chain-of-Custody Timeline:** Immutable, append-only event log providing a complete audit trail of every action taken on an accession
- **File Attachments:** Upload and categorize supporting documents (deed of gift, photographs, correspondence, inventories) with filesystem storage
- **Priority Management:** Four priority levels (low, normal, high, urgent) with visual badges and filtering

### M2: Appraisal & Valuation

- **Formal Appraisal:** Create structured appraisals with type classification (archival, monetary, insurance, historical, research), significance rating, and recommendation
- **Weighted Scoring Criteria:** Configurable scoring grid with named criteria, 1–5 scoring scale, and weighted average calculation
- **Appraisal Templates:** Reusable templates with pre-defined criteria sets per GLAM sector (archive, library, museum, gallery, DAM)
- **Heritage Asset Valuation:** GRAP 103 / IPSAS 45 compliant valuation history tracking with support for initial, revaluation, impairment, and disposal entries
- **Portfolio Reporting:** Aggregate valuation report showing total portfolio value by currency, valuation type breakdown, and per-accession detail

### M3: Containers & Rights

- **Physical Container Tracking:** Detailed container records with type, label, barcode, location, dimensions, weight, and condition assessment
- **Container Items:** Pre-arrangement item records within containers that can be linked to information objects during description
- **Barcode Lookup:** Instant container lookup by barcode for receiving and inventory workflows
- **PREMIS-Aligned Rights:** Accession-level rights records with basis, holder, dates, restriction types, grant acts, and conditions
- **Rights Inheritance:** Explicit push of rights from accessions to linked child information objects with full audit trail
- **Per-Repository Numbering:** Configurable accession number generation with token masks ({YEAR}, {SEQ:n}, {REPO}) and per-repository sequences

---

## Compliance

| Standard | Coverage |
|----------|----------|
| GRAP 103 | Heritage asset valuation, initial recognition, revaluation, impairment |
| IPSAS 45 | International heritage asset accounting alignment |
| PREMIS | Rights basis vocabulary, grant acts, restriction types |
| ISAD(G) | Accession-level description compatible |
| NARSSA | Audit trail and chain-of-custody compliance |

---

## Technical Requirements

- **AtoM Heratio Framework:** v2.8.0 or later
- **PHP:** 8.1 or later
- **MySQL:** 8.0 (with JSON column support)
- **Dependencies:** ahgCorePlugin

---

## Database

15 new tables with full multi-tenant isolation (tenant_id on all tables):

| Table | Purpose |
|-------|---------|
| accession_v2 | 1:1 extension of base accession table |
| accession_intake_checklist | Per-accession checklist items |
| accession_intake_template | Reusable checklist templates |
| accession_timeline | Immutable chain-of-custody event log |
| accession_attachment | File attachment metadata |
| accession_appraisal | Formal appraisal records |
| accession_appraisal_criterion | Scoring criteria per appraisal |
| accession_appraisal_template | Reusable appraisal criteria templates |
| accession_valuation_history | Heritage asset valuation tracking |
| accession_container | Physical container records |
| accession_container_item | Items within containers |
| accession_rights | Accession-level rights records |
| accession_rights_inherited | Rights pushed to child IOs |
| accession_numbering_sequence | Per-repo numbering sequences |
| accession_config | Per-tenant configuration |

---

## CLI Commands

```bash
# Intake queue management
php symfony accession:intake --queue              # List intake queue
php symfony accession:intake --stats              # Show queue statistics
php symfony accession:intake --assign=ID --user=X # Assign accession
php symfony accession:intake --accept=ID          # Accept accession
php symfony accession:intake --reject=ID --reason="..." # Reject
php symfony accession:intake --checklist=ID       # Show checklist
php symfony accession:intake --timeline=ID        # Show timeline

# Reporting
php symfony accession:report --status             # Status summary
php symfony accession:report --valuation          # Portfolio valuation
php symfony accession:report --export-csv         # CSV export with V2 fields
```

---

## Web Interface

| URL | Purpose |
|-----|---------|
| /accession/browse | Enhanced browse with status/priority columns |
| /admin/accessions/queue | Intake queue dashboard |
| /admin/accessions/dashboard | Accession KPI dashboard |
| /admin/accessions/{id}/intake | Intake detail (tabs: overview, checklist, attachments, timeline) |
| /admin/accessions/{id}/appraisal | Appraisal form with scoring grid |
| /admin/accessions/{id}/valuation | Valuation history |
| /admin/accessions/{id}/containers | Container management |
| /admin/accessions/{id}/rights | Rights management |
| /admin/accessions/valuation-report | Portfolio valuation report |
| /admin/accessions/config | V2 configuration |

---

## Settings

Configurable at **Admin > AHG Settings > Accession Management** (requires ahgSettingsPlugin):

- Numbering mask pattern
- Auto-assign to archivist
- Require donor agreement
- Require appraisal before acceptance
- Default priority level
- Default checklist template
- Default appraisal template
- Container barcode support
- Rights inheritance enabled

---

## Installation

```bash
# Run database migration
mysql -u root archive < atom-ahg-plugins/ahgAccessionManagePlugin/database/install.sql

# Clear cache
php symfony cc

# Verify
php symfony accession:intake --stats
```

---

## Multi-Tenant Support

All 15 tables include a `tenant_id` column. Service classes accept an optional tenant ID and apply scope filtering via `scopeQuery()`. This enables full hard isolation between tenants when used with the ahgMultiTenantPlugin.

---

*The Archive and Heritage Group (Pty) Ltd*
*https://theahg.co.za*
