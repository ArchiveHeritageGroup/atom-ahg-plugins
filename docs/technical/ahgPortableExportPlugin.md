# ahgPortableExportPlugin - Technical Documentation

**Version:** 1.0.0
**Category:** Export
**Dependencies:** atom-framework, ahgCorePlugin
**Load Order:** 100

---

## Overview

Standalone portable catalogue viewer plugin that exports AtoM catalogue data as a self-contained HTML/JS application for offline access on CD, USB, or downloadable ZIP. The generated viewer runs entirely client-side in any modern browser with zero server dependency.

Key capabilities: MPPT hierarchy extraction, digital object collection with checksums, FlexSearch client-side indexing, Bootstrap 5 viewer with tree navigation, edit mode with researcher exchange format (v1.0).

---

## Architecture

```
+---------------------------------------------------------------------+
|                      ahgPortableExportPlugin                         |
+---------------------------------------------------------------------+
|                                                                      |
|  +---------------------------------------------------------------+  |
|  |                   Plugin Configuration                         |  |
|  |  ahgPortableExportPluginConfiguration.class.php                |  |
|  |  - Route registration (7 routes via RouteLoader)               |  |
|  |  - Module initialization                                       |  |
|  +---------------------------------------------------------------+  |
|                              |                                       |
|                              v                                       |
|  +---------------------------------------------------------------+  |
|  |                    Action Methods (7)                          |  |
|  |  index | apiStartExport | apiProgress | apiList               |  |
|  |  download | apiDelete | apiToken                               |  |
|  +---------------------------------------------------------------+  |
|                              |                                       |
|                              v                                       |
|  +---------------------------------------------------------------+  |
|  |                    Service Layer (5 classes)                   |  |
|  |                                                                |  |
|  |  ExportPipelineService                                         |  |
|  |  - Orchestrates full pipeline                                  |  |
|  |  - Progress tracking (0-100%)                                  |  |
|  |  - Error handling + cleanup                                    |  |
|  |       |                                                        |  |
|  |       +-> CatalogueExtractor                                   |  |
|  |       |   - MPPT hierarchy queries                              |  |
|  |       |   - Access points, events, creators                     |  |
|  |       |   - Nested tree building                                |  |
|  |       |   - Taxonomy extraction                                 |  |
|  |       |                                                        |  |
|  |       +-> AssetCollector                                        |  |
|  |       |   - Digital object file copying                         |  |
|  |       |   - Derivative resolution (thumb/ref/master)            |  |
|  |       |   - SHA-256 checksums                                   |  |
|  |       |                                                        |  |
|  |       +-> SearchIndexBuilder                                    |  |
|  |       |   - FlexSearch-compatible index                         |  |
|  |       |   - Multi-field indexing                                |  |
|  |       |                                                        |  |
|  |       +-> ViewerPackager                                        |  |
|  |           - Copy viewer template files                          |  |
|  |           - Write config.json                                   |  |
|  |           - Create ZIP archive                                  |  |
|  +---------------------------------------------------------------+  |
|                              |                                       |
|                              v                                       |
|  +---------------------------------------------------------------+  |
|  |                    CLI Task Layer                              |  |
|  |  portableExportTask.class.php                                  |  |
|  |  - php symfony portable:export                                 |  |
|  |  - Background job processing via nohup                         |  |
|  +---------------------------------------------------------------+  |
|                              |                                       |
|                              v                                       |
|  +---------------------------------------------------------------+  |
|  |                    Client-Side Viewer                          |  |
|  |  index.html + app.js + tree.js + search.js + import.js        |  |
|  |  + Bootstrap 5 + FlexSearch (all bundled locally)              |  |
|  +---------------------------------------------------------------+  |
|                              |                                       |
|                              v                                       |
|  +---------------------------------------------------------------+  |
|  |                    Database Tables                             |  |
|  |  portable_export | portable_export_token                      |  |
|  +---------------------------------------------------------------+  |
|                                                                      |
+---------------------------------------------------------------------+
```

---

## File Structure

```
ahgPortableExportPlugin/
+-- config/
|   +-- ahgPortableExportPluginConfiguration.class.php
|   +-- routing.yml (reference only - routes via RouteLoader)
+-- database/
|   +-- install.sql (2 tables + admin menu entry)
+-- extension.json
+-- lib/
|   +-- Services/
|   |   +-- ExportPipelineService.php
|   |   +-- CatalogueExtractor.php
|   |   +-- AssetCollector.php
|   |   +-- SearchIndexBuilder.php
|   |   +-- ViewerPackager.php
|   +-- task/
|       +-- portableExportTask.class.php
+-- modules/
|   +-- portableExport/
|       +-- actions/
|       |   +-- actions.class.php (7 methods)
|       +-- templates/
|           +-- indexSuccess.php
+-- web/
    +-- viewer/
        +-- index.html
        +-- js/
        |   +-- app.js
        |   +-- search.js
        |   +-- tree.js
        |   +-- import.js
        +-- css/
        |   +-- viewer.css
        +-- lib/
            +-- bootstrap.bundle.min.js (~80KB)
            +-- bootstrap.min.css (~230KB)
            +-- bootstrap-icons.min.css (~86KB)
            +-- flexsearch.min.js (~16KB)
            +-- fonts/
                +-- bootstrap-icons.woff2
                +-- bootstrap-icons.woff
```

---

## Database Schema

### portable_export
```sql
CREATE TABLE IF NOT EXISTS portable_export (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    scope_type ENUM('all','fonds','repository','custom') NOT NULL DEFAULT 'all',
    scope_slug VARCHAR(255) DEFAULT NULL,
    scope_repository_id INT DEFAULT NULL,
    mode ENUM('read_only','editable') DEFAULT 'read_only',
    include_objects TINYINT(1) DEFAULT 1,
    include_masters TINYINT(1) DEFAULT 0,
    include_thumbnails TINYINT(1) DEFAULT 1,
    include_references TINYINT(1) DEFAULT 1,
    branding JSON DEFAULT NULL,
    culture VARCHAR(16) DEFAULT 'en',
    status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    progress INT DEFAULT 0,
    total_descriptions INT DEFAULT 0,
    total_objects INT DEFAULT 0,
    output_path VARCHAR(1024) DEFAULT NULL,
    output_size BIGINT UNSIGNED DEFAULT 0,
    error_message TEXT DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_portable_export_user (user_id),
    INDEX idx_portable_export_status (status)
);
```

### portable_export_token
```sql
CREATE TABLE IF NOT EXISTS portable_export_token (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    export_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    download_count INT DEFAULT 0,
    max_downloads INT DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (export_id) REFERENCES portable_export(id) ON DELETE CASCADE,
    INDEX idx_portable_export_token (token)
);
```

---

## Routes

| Route Name | URL | Action | Purpose |
|-----------|-----|--------|---------|
| portable_export_index | /portable-export | index | Export form + past exports |
| portable_export_api_start | /portable-export/api/start | apiStartExport | Create export, launch background job |
| portable_export_api_progress | /portable-export/api/progress | apiProgress | Poll progress (AJAX) |
| portable_export_api_list | /portable-export/api/list | apiList | List past exports (JSON) |
| portable_export_download | /portable-export/download | download | Download ZIP (admin or token) |
| portable_export_api_delete | /portable-export/api/delete | apiDelete | Delete export + files |
| portable_export_api_token | /portable-export/api/token | apiToken | Generate share token |

---

## Service Details

### ExportPipelineService
- Entry point: `runExport(int $exportId)`
- Steps: validate → extract catalogue → collect assets → build index → package → ZIP
- Updates `portable_export.progress` at each step (0-100) for AJAX polling
- Output: `{ATOM_ROOT}/downloads/portable-exports/export-{id}/` + `.zip`
- On failure: sets status='failed' with error message

### CatalogueExtractor
- Entry point: `extract(scopeType, scopeSlug, repositoryId)`
- Queries: information_object (MPPT-ordered), information_object_i18n, slug, digital_object, term, term_i18n, object_term_relation, relation, event, event_i18n, actor_i18n, repository
- Access points: subjects (taxonomy 35), places (taxonomy 42), genres (taxonomy 78)
- Creators: from events (type 111) and relations (type 161)
- Chunked queries (500 IDs per batch) for memory efficiency
- Output: `{ descriptions: [], hierarchy: [], taxonomies: {}, repositories: {} }`

### AssetCollector
- Entry point: `collect(descriptions, outputDir, options)`
- Resolves: `uploads/{path}/{name}` for masters, derivatives for thumbs/refs
- SHA-256 checksums for all copied files
- Updates description objects with relative file paths (thumbnail_file, reference_file, etc.)
- Output: `{ files: [manifest], total_size: int, descriptions: [updated] }`

### SearchIndexBuilder
- Entry point: `buildIndex(descriptions)`
- Indexed fields: title, identifier, content, level, creators, subjects, places, dates, extent
- HTML stripping + whitespace normalization
- Output: FlexSearch-compatible `{ documents: [], stats: {} }`

### ViewerPackager
- Entry point: `package(exportDir, config)` + `createZip(exportDir, zipPath)`
- Copies viewer files from `web/viewer/` to export directory
- Writes `data/config.json` with branding, mode, counts, hierarchy, repositories
- Creates ZIP with `ZipArchive` class

---

## Client-Side Viewer

### app.js
- Main application: data loading, routing, state management, rendering
- Loads catalogue.json, config.json, search-index.json via XHR
- Renders ISAD(G) description detail views with breadcrumbs
- Supports digital object inline viewing (images, PDFs)
- Edit mode: research notes textarea per description

### tree.js
- Hierarchical tree navigation from config.hierarchy
- Expand/collapse with MPPT ordering preserved
- Level-specific icons (fonds=archive, series=folder, file=document, item=text)
- Ancestor expansion for deep linking
- Expand All / Collapse All buttons

### search.js
- FlexSearch Document index with multi-field search
- Fields: title, identifier, content, creators, subjects, places, dates
- Auto-search with 300ms debounce
- Snippet generation with query highlighting
- Fallback: simple substring match if FlexSearch unavailable

### import.js (edit mode only)
- Drag-drop / file picker for importing files
- Files stored as base64 data URLs in memory
- Caption field per imported file
- Notes summary panel
- Export as researcher-exchange.json (v1.0 format)

---

## Exchange Format (v1.0)

```json
{
  "format_version": "1.0",
  "source": "portable-viewer",
  "exported_at": "2026-02-14T10:30:00Z",
  "source_config": {
    "title": "Portable Catalogue",
    "scope_type": "all",
    "culture": "en"
  },
  "collections": [
    {
      "title": "Research Notes",
      "type": "notes",
      "items": [
        {
          "reference_id": 123,
          "reference_slug": "example-description",
          "reference_identifier": "REF-001",
          "title": "Description Title",
          "level_of_description": "file",
          "note": "User-added research note text"
        }
      ]
    },
    {
      "title": "Imported Files",
      "type": "files",
      "items": [
        {
          "title": "Site A Overview",
          "level_of_description": "item",
          "scope_and_content": "Photo caption",
          "files": [
            {
              "filename": "photo.jpg",
              "caption": "Overview",
              "mime_type": "image/jpeg",
              "size": 234567
            }
          ]
        }
      ]
    }
  ]
}
```

This format is shared between ahgPortableExportPlugin (write) and the planned ahgResearcherPlugin (read/import).

---

## CLI Command

```
Namespace: portable
Task:      export
Class:     portableExportTask extends arBaseTask

Options:
  --scope=all|fonds|repository|custom
  --slug=<fonds-slug>
  --repository-id=<int>
  --mode=read_only|editable
  --culture=en|fr|af|pt
  --title=<string>
  --output=<path>
  --zip
  --no-objects
  --no-thumbnails
  --no-references
  --include-masters
  --export-id=<int>
```

---

## Background Job Pattern

Web UI launches export via nohup:
```bash
nohup php {ATOM_ROOT}/symfony portable:export --export-id={ID} > {log} 2>&1 &
```

Progress polling via AJAX every 2 seconds to `/portable-export/api/progress?id={ID}`.

---

## Security

- All actions require admin authentication (except token-based download)
- Download tokens: 64-byte random hex, optional max_downloads + expires_at
- Token-based downloads bypass admin auth but are scoped to a single export
- No user data exposed in the static viewer (only catalogue metadata)
