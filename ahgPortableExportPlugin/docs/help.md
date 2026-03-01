# Portable Export — User & Admin Guide

**Plugin:** ahgPortableExportPlugin v2.0.0

---

## Table of Contents

1. [Introduction](#introduction)
2. [Viewer Export](#viewer-export)
3. [Archive Export](#archive-export)
4. [Admin UI Wizard](#admin-ui-wizard)
5. [CLI Commands](#cli-commands)
6. [Sharing & Download Tokens](#sharing--download-tokens)
7. [Export Verification](#export-verification)
8. [Settings](#settings)
9. [Troubleshooting](#troubleshooting)

---

## Introduction

The Portable Export plugin allows administrators to create self-contained export packages of their AtoM catalogue. Two export modes are available:

- **Viewer Export** — An HTML/JS viewer for offline browsing (CD, USB, web download)
- **Archive Export** — Structured JSON data files with checksums for system portability and backup

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

A ZIP archive containing structured JSON files covering all entity types:

| File | Content |
|------|---------|
| `manifest.json` | Export metadata, file checksums (SHA-256), entity counts |
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

### Entity Type Selection

In archive mode, you can choose which entity types to include. All are selected by default.

### Dry-Run Estimate

Before starting an archive export, use the **Estimate Export Size** button to get:
- Record counts per entity type
- Digital object count and total file size
- Estimated package size
- Estimated duration

---

## Admin UI Wizard

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
- **Archive Export** — Structured JSON for portability

For archive mode, select which entity types to include and optionally run a dry-run estimate.

### Step 3: Configure

- **Title** — Name for the export package
- **Language** — Primary culture for i18n text
- **Branding** — Optional title, subtitle, footer (viewer mode)

### Step 4: Generate

Review all settings and click **Start Export**. Progress is displayed in real-time.

---

## CLI Commands

### Generate Export

```bash
# Viewer export (read-only)
php symfony portable:export --scope=all --zip

# Viewer export for specific fonds
php symfony portable:export --scope=fonds --slug=my-fonds --zip

# Archive export (full system)
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

---

*The Archive and Heritage Group (Pty) Ltd*
