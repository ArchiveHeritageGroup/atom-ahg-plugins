# Export — User & Admin Guide

**Plugin:** ahgExportPlugin v1.0.0

---

## Table of Contents

1. [Introduction](#introduction)
2. [Export Formats](#export-formats)
3. [Archival Description Export](#archival-description-export)
4. [Authority Record Export](#authority-record-export)
5. [Repository Export](#repository-export)
6. [Accession CSV Export](#accession-csv-export)
7. [Admin UI](#admin-ui)
8. [Troubleshooting](#troubleshooting)

---

## Introduction

The Export plugin provides multiple export formats for AtoM Heratio data. Records can be exported from the web UI at **Admin > Export** (`/export`).

---

## Export Formats

| Format | Entity | Description |
|--------|--------|-------------|
| EAD 2002 | Descriptions | Encoded Archival Description XML |
| Dublin Core | Descriptions | Simple Dublin Core XML |
| CSV | Descriptions | Comma-separated values (ISAD-G columns) |
| EAC-CPF | Authorities | Encoded Archival Context XML |
| CSV | Authorities | Authority record CSV |
| CSV | Repositories | Repository/institution CSV |
| CSV | Accessions | Accession record CSV (round-trip compatible) |

---

## Archival Description Export

### CSV Export
Exports descriptions with ISAD(G) fields including:
- Identity: legacyId, identifier, title, levelOfDescription
- Context: archivalHistory, arrangement
- Content: scopeAndContent, extentAndMedium
- Access: accessConditions, reproductionConditions, findingAids
- Allied: locationOfOriginals, locationOfCopies, relatedUnitsOfDescription
- Access points: subjects, places, names
- Events: dates

### Filters
- By repository
- By level of description
- By parent record (with/without descendants)

### EAD Export
Full EAD 2002 XML export with hierarchical structure preserved.

---

## Authority Record Export

Exports actor records (persons, organizations, families) with:
- Authorized form of name
- Entity type
- Dates of existence
- History, places, legal status, functions

### Formats
- **EAC-CPF** — XML standard for authority records
- **CSV** — Flat file export

---

## Repository Export

Exports repository/institution records with:
- Authorized name, identifier
- Contact information (address, phone, email, website)
- Description, access conditions

---

## Accession CSV Export

Exports accession records to CSV format that is directly compatible with the ahgIngestPlugin accession import wizard.

### Exported Columns

| Column Group | Fields |
|-------------|--------|
| Core | accessionNumber, title, acquisitionDate, sourceOfAcquisition, locationInformation, receivedExtentUnits, scopeAndContent, appraisal, archivalHistory, processingNotes |
| Taxonomy | acquisitionType, resourceType, processingStatus, processingPriority |
| Donor | donorName, donorStreetAddress, donorCity, donorRegion, donorCountry, donorPostalCode, donorTelephone, donorFax, donorEmail, donorContactPerson, donorNote |
| Events | accessionEventTypes, accessionEventDates, accessionEventAgents |
| Alternative IDs | alternativeIdentifiers, alternativeIdentifierNotes |
| Extended | intakeNotes, intakePriority |
| Control | culture |

### Filters
- By repository
- By acquisition date range (from/to)

### Round-Trip Compatibility

The exported CSV can be re-imported using:
- **Ingest Wizard** — Admin > Ingest > New > select Accessions
- **CLI** — `php symfony csv:accession-import filename.csv`

---

## Admin UI

Navigate to **Admin > Export** (`/export`).

The export dashboard provides cards for each entity type with links to available formats.

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Export timeout | Use CLI export for large datasets |
| Empty export | Check filter settings; verify records exist |
| Missing access points | Ensure records have linked terms |
| Accession donor missing | Donor must be linked via relation |

---

*The Archive and Heritage Group (Pty) Ltd*
