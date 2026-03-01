# AtoM Heratio — Portable Export & Import

## Feature Overview

**Version:** 3.0.0
**Author:** The Archive and Heritage Group (Pty) Ltd
**Category:** Exit Export & Data Portability

---

## Overview

The Portable Export & Import plugin provides a complete data portability solution for AtoM Heratio archival management instances. It enables institutions to:

1. **Export** entire AtoM instances as structured, verifiable archive packages
2. **Import** archive packages into fresh or existing instances with full ID remapping
3. **Distribute** offline catalogue viewers for CD, USB, or web download

This plugin satisfies the exit/portability requirement — ensuring institutions retain full control of their data and can migrate between AtoM instances without vendor lock-in.

---

## Key Features

### Full-System Archive Export (v3.0)
- **15 entity types** exported as structured JSON: descriptions, authorities, taxonomies, rights, accessions, physical objects, events, notes, relations, digital objects, repositories, access points (object-term relations), settings, users/groups, and menus
- **SHA-256 checksummed manifest** for integrity verification of every file
- **Self-documenting packages** with README.md and schema documentation embedded in the manifest
- Dry-run estimate before export (record counts, estimated size, duration)
- All i18n cultures preserved per entity
- Sensitive data excluded (passwords, tokens, API keys)

### Full-System Archive Import (v3.0)
- **FK-dependency-ordered import pipeline** — entities imported in the correct order to maintain referential integrity
- **ID remapping** — all source IDs automatically mapped to new target IDs
- **Three import modes:**
  - **Merge** — Skip existing records, import only new (safe default)
  - **Dry Run** — Validate and report without database changes
  - **Replace** — Clear and re-import (with confirmation safeguards)
- Digital object file copying — asset files transferred to correct upload paths
- Secure user import — user accounts imported without passwords (must be reset)

### Viewer Export Mode
- Self-contained HTML/JS viewer — zero server dependencies
- Client-side search powered by FlexSearch
- Hierarchical tree navigation (MPTT-ordered)
- Digital object inline viewing (images, PDFs)
- Editable mode for researcher notes and file import
- Configurable branding (title, subtitle, footer)

### Shared Features
- Export scopes: entire catalogue, specific fonds, by repository, custom (clipboard)
- Background processing with progress tracking (via QueueService or nohup fallback)
- Admin UI: 4-step export wizard + 3-step import wizard
- Secure download tokens for sharing (time-limited, download-limited)
- CLI commands for automation (export, import, verify, cleanup)
- Automatic retention and cleanup

---

## CLI Commands

```bash
# Archive export (full system, 15 entity types)
php symfony portable:export --scope=all --mode=archive --zip

# Viewer export
php symfony portable:export --scope=all --zip
php symfony portable:export --scope=fonds --slug=my-fonds --mode=read_only

# Import (dry run)
php symfony portable:import --zip=/path/to/archive.zip --mode=dry_run

# Import (merge — skip existing, import new)
php symfony portable:import --zip=/path/to/archive.zip --mode=merge

# Import specific entity types
php symfony portable:import --zip=/path/to/archive.zip --entity-types=descriptions,authorities,taxonomies

# Verify an archive export
php symfony portable:verify --path=/path/to/export.zip

# Clean up expired exports
php symfony portable:cleanup
```

---

## Archive Export Package Structure

```
export-{id}/
  manifest.json              # Checksums, entity counts, schema, export metadata
  README.md                  # Package overview + re-import instructions
  data/
    descriptions.json        # ISAD(G) fields + all i18n + properties
    authorities.json         # Actors with type, dates, relations
    taxonomies.json          # Full taxonomy trees with parent-child
    rights.json              # Rights statements linked to objects
    accessions.json          # Accession records + events + donors
    physical_objects.json    # Storage locations
    events.json              # Events linked to descriptions
    notes.json               # Notes by type
    relations.json           # Inter-entity relationships
    digital_objects.json     # Digital object metadata
    repositories.json        # Repository metadata + contacts
    object_term_relations.json # Subject/place/genre access points
    settings.json            # System + plugin settings
    users.json               # Users, ACL groups, permissions
    menus.json               # Navigation structure (MPTT)
  objects/                   # Digital object files (if included)
```

---

## Technical Requirements

- AtoM Heratio Framework v2.0+
- PHP 8.1+
- MySQL 8.0+
- ahgCorePlugin (dependency)

---

## Compliance & Standards

| Standard | Coverage |
|----------|----------|
| OAIS (ISO 14721) | Archive packages align with SIP/AIP concepts |
| SHA-256 | Every file checksummed in manifest for bit-level integrity |
| ISAD(G) | Full descriptive metadata preserved |
| ISAAR(CPF) | Authority records with complete fields |
| Data Portability | Full exit path — no vendor lock-in |

---

## Admin UI

### Export Wizard
Access via **Admin > Portable Export** (`/portable-export`).

4-step wizard:
1. **Scope** — Select what to export (all, fonds, repository)
2. **Content** — Choose export type (Viewer/Archive), entity types, digital objects
3. **Configure** — Title, language, branding
4. **Generate** — Review settings, start export, monitor progress

### Import Wizard
Access via `/portable-export/import` (or click "Import Archive" on the export page).

3-step wizard:
1. **Upload** — Upload ZIP or enter server path, validate archive
2. **Configure** — Select mode (merge/dry-run/replace), choose entity types
3. **Import** — Real-time progress with imported/skipped/error counters

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `portable_export` | Export job tracking |
| `portable_export_token` | Secure download tokens |
| `portable_import` | Import job tracking with ID mapping |

---

*The Archive and Heritage Group (Pty) Ltd*
*https://www.theahg.co.za*
