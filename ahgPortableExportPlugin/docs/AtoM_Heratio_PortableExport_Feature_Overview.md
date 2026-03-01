# AtoM Heratio — Portable Export Plugin

**Version:** 2.0.0
**Author:** The Archive and Heritage Group (Pty) Ltd
**Category:** Export & Portability

---

## Overview

The Portable Export plugin provides two distinct export modes for AtoM Heratio:

1. **Viewer Export** — A self-contained HTML/JS catalogue viewer for offline access on CD, USB, or downloadable ZIP. Opens in any modern browser with no server or internet required.

2. **Archive Export** — A structured, re-importable JSON package with SHA-256 checksums for full system portability. Covers all entity types (descriptions, authorities, taxonomies, rights, accessions, physical objects, events, notes, relations, repositories) with a verifiable manifest.

---

## Key Features

### Viewer Export Mode
- Self-contained HTML/JS viewer — zero server dependencies
- Client-side search powered by FlexSearch
- Hierarchical tree navigation (MPTT-ordered)
- Digital object inline viewing (images, PDFs)
- Editable mode for researcher notes and file import
- Configurable branding (title, subtitle, footer)

### Archive Export Mode (v2.0)
- Re-importable JSON format for full system portability
- 11 entity types: descriptions, authorities, taxonomies, rights, accessions, physical objects, events, notes, relations, digital object metadata, repositories
- SHA-256 checksums on all exported files
- Manifest with entity counts, source metadata, and file inventory
- Dry-run estimate before export (record counts, estimated size, duration)
- Export verification CLI command
- All i18n cultures preserved per entity

### Shared Features
- Export scopes: entire catalogue, specific fonds, by repository, custom (clipboard)
- Background processing with progress tracking (via QueueService or nohup fallback)
- Admin UI with 4-step wizard
- Secure download tokens for sharing (time-limited, download-limited)
- CLI commands for automation
- Automatic retention and cleanup

---

## CLI Commands

```bash
# Viewer export
php symfony portable:export --scope=all --zip
php symfony portable:export --scope=fonds --slug=my-fonds --mode=read_only

# Archive export
php symfony portable:export --scope=all --mode=archive --zip
php symfony portable:export --scope=repository --repository-id=5 --mode=archive

# Verify an archive export
php symfony portable:verify --path=/path/to/export.zip

# Clean up expired exports
php symfony portable:cleanup
```

---

## Archive Export Package Structure

```
export-{id}/
  manifest.json          # Checksums, entity counts, export metadata
  data/
    descriptions.json    # ISAD(G) fields + all i18n + properties
    authorities.json     # Actors with type, dates, relations
    taxonomies.json      # Full taxonomy trees with parent-child
    rights.json          # Rights statements linked to objects
    accessions.json      # Accession records + events + donors
    physical_objects.json# Storage locations
    events.json          # Events linked to descriptions
    notes.json           # Notes by type
    relations.json       # Inter-entity relationships
    digital_objects.json # Digital object metadata
    repositories.json    # Repository metadata + contacts
  objects/               # Digital object files (if included)
    thumbnails/
    references/
    masters/
```

---

## Technical Requirements

- AtoM Heratio Framework v2.0+
- PHP 8.1+
- MySQL 8.0+
- ahgCorePlugin (dependency)

---

## Compliance & Standards

- Supports OAIS-aligned export packaging
- SHA-256 file integrity verification
- Full audit trail integration
- Compatible with NARSSA/POPIA archival requirements

---

## Admin UI

Access via **Admin > Portable Export** (`/portable-export`).

The 4-step wizard guides through:
1. **Scope** — Select what to export (all, fonds, repository)
2. **Content** — Choose export type (Viewer/Archive), entity types, digital objects
3. **Configure** — Title, language, branding
4. **Generate** — Review settings, start export, monitor progress

---

*The Archive and Heritage Group (Pty) Ltd*
*https://theahg.co.za*
