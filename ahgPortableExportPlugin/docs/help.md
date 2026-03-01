# Portable Export & Import — User & Admin Guide

**Plugin:** ahgPortableExportPlugin v3.0.0

---

## Table of Contents

1. [Introduction](#introduction)
2. [Viewer Export](#viewer-export)
3. [Archive Export](#archive-export)
4. [Archive Import](#archive-import)
5. [Admin UI — Export Wizard](#admin-ui--export-wizard)
6. [Admin UI — Import Wizard](#admin-ui--import-wizard)
7. [CLI Commands](#cli-commands)
8. [Sharing & Download Tokens](#sharing--download-tokens)
9. [Export Verification](#export-verification)
10. [Settings](#settings)
11. [Troubleshooting](#troubleshooting)

---

## Introduction

The Portable Export & Import plugin provides a complete exit/portability solution for AtoM Heratio instances. It enables:

- **Viewer Export** — Self-contained HTML/JS viewer for offline browsing (CD, USB, web download)
- **Archive Export** — Structured JSON data covering 15 entity types with SHA-256 checksummed manifests
- **Archive Import** — Re-import archive packages into fresh or existing instances with ID remapping

This plugin satisfies the V2.3 Exit Export + Portability requirement, ensuring institutions can migrate their data between AtoM instances or preserve it in a standards-based, verifiable format.

---

## Viewer Export

### What It Produces

A ZIP archive containing a fully functional web application that runs in any modern browser without a server:

- `index.html` — Main viewer entry point
- `data/catalogue.json` — Description records with access points, dates, creators
- `data/search-index.json` — Pre-built search index (FlexSearch)
- `objects/` — Digital object files (thumbnails, references, optionally masters)

### Modes

- **Read Only** — Standard browsing experience
- **Editable** — Researchers can add notes and import files

### Export Scopes

| Scope | Description |
|-------|-------------|
| Entire Catalogue | All descriptions in the system |
| Fonds/Collection | A specific fonds and all descendants |
| Repository | All descriptions belonging to a repository |
| Custom (Clipboard) | Specific items selected via clipboard |

---

## Archive Export

### What It Produces

A self-documenting ZIP archive containing structured JSON files for 15 entity types:

| File | Content |
|------|---------|
| `manifest.json` | Export metadata, file checksums (SHA-256), entity counts, schema documentation |
| `README.md` | Package overview, re-import instructions, compatibility notes |
| `data/descriptions.json` | Information objects with all ISAD(G) fields, i18n, properties |
| `data/authorities.json` | Actor records with type, dates, i18n |
| `data/taxonomies.json` | Full taxonomy trees with terms |
| `data/rights.json` | Rights statements |
| `data/accessions.json` | Accession records with events and donors |
| `data/physical_objects.json` | Physical storage locations |
| `data/events.json` | Events linked to descriptions |
| `data/notes.json` | Notes by type |
| `data/relations.json` | Inter-entity relationships |
| `data/digital_objects.json` | Digital object metadata |
| `data/repositories.json` | Repository records with contacts |
| `data/object_term_relations.json` | Subject, place, genre access points |
| `data/settings.json` | System and plugin settings (passwords excluded) |
| `data/users.json` | User accounts, ACL groups, permissions (passwords excluded) |
| `data/menus.json` | Navigation menu structure (MPTT hierarchy) |

### Self-Documenting Packages

Every archive export includes:

- **README.md** — Human-readable overview with entity counts, source information, and re-import instructions
- **Schema documentation** — The manifest.json includes a `schema` key documenting every entity type's fields, primary keys, and foreign key relationships

### Entity Type Selection

In archive mode, you can choose which entity types to include. All 15 are selected by default.

### Dry-Run Estimate

Before starting an archive export, use the **Estimate Export Size** button to get:
- Record counts per entity type
- Digital object count and total file size
- Estimated package size
- Estimated duration

---

## Archive Import

### Overview

Archive packages can be imported into any AtoM Heratio instance. The import process:

1. Validates the archive structure and checksums
2. Imports entities in FK-dependency order (taxonomies first, then repositories, authorities, descriptions, etc.)
3. Remaps all IDs from source to target (old ID → new ID)
4. Copies digital object files to the correct upload paths
5. Reports created, skipped, and errored records

### Import Modes

| Mode | Behavior |
|------|----------|
| **Merge** (default) | Skip existing records (matched by slug/identifier), import only new ones |
| **Dry Run** | Validate and report what would be created, without writing to the database |
| **Replace** | Clear target tables before import (**dangerous** — use with extreme caution) |

### Import Order

Entities are imported in this order to respect foreign key dependencies:

1. Taxonomies + terms
2. Repositories
3. Authorities (actors)
4. Users + ACL groups
5. Descriptions (information objects)
6. Events
7. Notes
8. Relations
9. Rights
10. Accessions
11. Physical objects
12. Object-term relations (access points)
13. Digital objects + asset files
14. Settings
15. Menus

### ID Remapping

Every entity reference is remapped during import:
- `parent_id`, `repository_id`, `actor_id`, `term_id`, `object_id`, etc.
- Old source IDs are mapped to new target IDs
- The complete ID mapping is saved in the import record for auditing

### User Import Notes

- Passwords are **never** exported or imported — imported users receive random temporary passwords
- Imported users must reset their passwords on first login
- ACL group memberships and permissions are preserved

---

## Admin UI — Export Wizard

Navigate to **Admin > Portable Export** (or `/portable-export`).

### Step 1: Scope

Select what to export:
- **Entire Catalogue** — Exports everything
- **Fonds/Collection** — Type to search and select a specific fonds
- **Repository** — Select from dropdown

### Step 2: Content

Choose the export type:
- **Viewer Export** — HTML viewer with digital objects (thumbnails, references, masters)
- **Editable Export** — Viewer with note-taking capability
- **Archive Export** — Structured JSON with 15 entity types

For archive mode, select which entity types to include and optionally run a dry-run estimate.

### Step 3: Configure

- **Title** — Name for the export package
- **Language** — Primary culture for i18n text
- **Branding** — Optional title, subtitle, footer (viewer mode)

### Step 4: Generate

Review all settings and click **Start Export**. Progress is displayed in real-time.

---

## Admin UI — Import Wizard

Navigate to `/portable-export/import` (or click "Import Archive" on the export page).

### Step 1: Upload

- **Upload File** — Select a ZIP file from your computer
- **Server Path** — Enter a path to a ZIP or extracted directory on the server

Click **Validate Archive** to check structure, checksums, and entity counts.

### Step 2: Configure

- **Import Title** — Label for this import job
- **Import Mode** — Merge (default), Dry Run, or Replace
- **Entity Types** — Select which types to import (all available types shown with counts)

### Step 3: Import

- Real-time progress bar with imported/skipped/error counters
- Completion summary with error log (if any)

---

## CLI Commands

### Generate Export

```bash
# Viewer export (read-only)
php symfony portable:export --scope=all --zip

# Viewer export for specific fonds
php symfony portable:export --scope=fonds --slug=my-fonds --zip

# Archive export (full system, all 15 entity types)
php symfony portable:export --scope=all --mode=archive --zip

# Archive export for specific repository
php symfony portable:export --scope=repository --repository-id=5 --mode=archive

# Skip digital objects (metadata only)
php symfony portable:export --scope=all --mode=archive --no-objects --zip

# Custom output path
php symfony portable:export --scope=all --mode=archive --output=/tmp/archive.zip

# Process existing export job
php symfony portable:export --export-id=42
```

### Import Archive

```bash
# Dry run (validate without importing)
php symfony portable:import --zip=/path/to/archive.zip --mode=dry_run

# Merge import (skip existing, import new)
php symfony portable:import --zip=/path/to/archive.zip --mode=merge

# Replace import (clear and re-import)
php symfony portable:import --zip=/path/to/archive.zip --mode=replace

# Import specific entity types only
php symfony portable:import --zip=/path/to/archive.zip --entity-types=descriptions,authorities,taxonomies

# Import from extracted directory
php symfony portable:import --path=/path/to/extracted-archive

# Process existing import job
php symfony portable:import --import-id=42
```

### Verify Export

```bash
# Verify a ZIP file
php symfony portable:verify --path=/path/to/export.zip

# Verify an extracted directory
php symfony portable:verify --path=/path/to/export-dir
```

Exit code 0 = all checksums verified. Exit code 1 = errors found.

### Cleanup

```bash
# Remove expired exports
php symfony portable:cleanup

# Preview what would be deleted
php symfony portable:cleanup --dry-run
```

---

## Sharing & Download Tokens

Completed exports can be shared via secure download tokens:

1. In the Past Exports table, click the link icon on a completed export
2. Set max downloads (optional) and expiry time
3. Click **Generate Link**
4. Copy and share the URL

Tokens can be time-limited and download-limited.

---

## Export Verification

Archive exports include `manifest.json` with SHA-256 checksums for every file. Use the `portable:verify` CLI command to validate integrity after transfer:

```bash
php symfony portable:verify --path=/media/usb/archive-export.zip
```

The command:
1. Extracts the ZIP (if needed)
2. Reads `manifest.json`
3. Computes SHA-256 for each listed file
4. Reports matches, mismatches, and missing files

---

## Settings

Configure defaults at **Admin > AHG Settings > Portable Export**:

| Setting | Default | Description |
|---------|---------|-------------|
| Retention Days | 30 | Auto-delete exports after N days |
| Max Size (MB) | 2048 | Maximum export size limit |
| Default Mode | read_only | Default export mode |
| Include Objects | true | Include digital objects by default |
| Include Thumbnails | true | Include thumbnails by default |
| Include References | true | Include reference images by default |
| Include Masters | false | Include master files by default |
| Default Culture | en | Default export language |

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Export stuck at 0% | Check PHP error log; ensure background process can run |
| ZIP file too large | Disable masters; use fonds/repository scope instead of all |
| Verify fails with missing files | Package may have been corrupted during transfer; re-export |
| "Export not found" error | Export may have been cleaned up; check retention settings |
| Progress not updating | Clear Symfony cache; ensure AJAX endpoint is accessible |
| Import fails: "manifest.json not found" | Ensure the archive was created in Archive mode (not Viewer mode) |
| Import fails: checksum mismatch | Archive was corrupted during transfer; re-download or re-export |
| Import skips all records | Expected in merge mode if records already exist; use replace for full re-import |
| Imported users can't login | Imported users have random passwords; they must reset via admin or CLI |

---

*The Archive and Heritage Group (Pty) Ltd*
