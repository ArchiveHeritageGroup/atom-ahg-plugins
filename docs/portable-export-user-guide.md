# Portable Export

## User Guide

Export your catalogue as a self-contained, portable HTML/JS application on CD, USB, or downloadable ZIP. The viewer opens in any modern browser with no server, installation, or internet connection required — a "mini Heratio" for offline use.

---

## Overview
```
+-------------------------------------------------------------+
|                   PORTABLE EXPORT                            |
|              ahgPortableExportPlugin v1.0.0                  |
+-------------------------------------------------------------+
|                                                              |
|  SERVER SIDE                                                 |
|  +-------------------------------------------------------+  |
|  |  Admin > AHG Settings > Portable Export                |  |
|  |  or: php symfony portable:export --scope=all --zip     |  |
|  +-------------------------------------------------------+  |
|       |                                                      |
|       v                                                      |
|  +-----------+  +-----------+  +-----------+  +---------+   |
|  | Extract   |->| Collect   |->| Build     |->| Package |   |
|  | Catalogue |  | Assets    |  | Search    |  | & ZIP   |   |
|  +-----------+  +-----------+  +-----------+  +---------+   |
|                                                    |         |
|  CLIENT SIDE (zero server)                         v         |
|  +-------------------------------------------------------+  |
|  |  portable-export.zip                                   |  |
|  |  +-------------------------------------------------+   |  |
|  |  | index.html  - Open in any browser               |   |  |
|  |  | Tree nav    - Fonds > Series > File > Item      |   |  |
|  |  | Search      - Instant full-text (FlexSearch)    |   |  |
|  |  | View        - Images, PDFs, all ISAD(G) fields  |   |  |
|  |  | Edit mode   - Add notes, import files           |   |  |
|  |  +-------------------------------------------------+   |  |
|  +-------------------------------------------------------+  |
|                                                              |
+-------------------------------------------------------------+
```

---

## Key Features
```
+-------------------------------------------------------------+
|                   CAPABILITIES                               |
+-------------------------------------------------------------+
|  [Browse]   Tree Navigation   - Hierarchical tree mirroring  |
|                                 AtoM fonds/series/file/item  |
|  [Search]   Full-Text Search  - FlexSearch-powered instant   |
|                                 client-side search           |
|  [View]     Detail View       - All ISAD(G) fields, access  |
|                                 points, dates, creators      |
|  [Images]   Digital Objects   - Inline image + PDF viewing   |
|  [Scope]    Flexible Scope    - Entire catalogue, by fonds,  |
|                                 or by repository             |
|  [Edit]     Edit Mode         - Add notes, import files,     |
|                                 export researcher exchange   |
|  [Brand]    Custom Branding   - Title, subtitle, footer      |
|  [Share]    Download Tokens   - Secure shareable links with  |
|                                 expiry and download limits   |
|  [CLI]      Command Line      - Scriptable via CLI command   |
|  [Offline]  Zero Server       - Works from any filesystem,   |
|                                 no internet required         |
+-------------------------------------------------------------+
```

---

## How to Access
```
  Admin Menu
      |
      v
   AHG Settings (/ahgSettings/index)
      |
      v
   Portable Export tile
      |
      v
   /portable-export
      |
      +---> New Export form
      |       |
      |       +---> Select scope (All / Fonds / Repository)
      |       +---> Choose mode (Read Only / Editable)
      |       +---> Set branding (title, subtitle, footer)
      |       +---> Select objects (thumbs, refs, masters)
      |       +---> Start Export
      |               |
      |               +---> Progress bar (real-time polling)
      |               +---> Download ZIP when complete
      |
      +---> Past Exports table
      |       |
      |       +---> Download completed exports
      |       +---> Generate share links
      |       +---> Delete old exports
      |
      +---> CLI: php symfony portable:export
```

---

## Creating an Export (Web UI)

### Step 1: Open the Export Page
```
  AHG Settings > Portable Export tile
  or navigate directly to /portable-export
```

### Step 2: Configure the Export
```
  +-----------------------------------------------------------+
  |  New Export                                                 |
  +-----------------------------------------------------------+
  |                                                             |
  |  Export Title:    [ Portable Catalogue          ]           |
  |  Language:        [ English         v ]                     |
  |  Viewer Mode:     [ Read Only       v ]                     |
  |                                                             |
  |  Scope:           [ Entire Catalogue v ]                    |
  |                   [ Specific Fonds     ]  <- enter slug     |
  |                   [ By Repository      ]  <- select repo    |
  |                                                             |
  |  Digital Objects: [x] Digital Objects                        |
  |                   [x] Thumbnails                            |
  |                   [x] Reference Images                      |
  |                   [ ] Master Files (large!)                  |
  |                                                             |
  |  Branding:                                                  |
  |    Viewer Title:  [ My Archive Collection     ]             |
  |    Subtitle:      [ Special Collections       ]             |
  |    Footer:        [ (c) 2026 My Institution   ]             |
  |                                                             |
  |  [ Start Export ]                                           |
  +-----------------------------------------------------------+
```

### Step 3: Wait for Processing
```
  +-----------------------------------------------------------+
  |  Export Progress                                            |
  +-----------------------------------------------------------+
  |                                                             |
  |  [=============>                     ] 42%                  |
  |                                                             |
  |  Collecting digital objects...                              |
  |                                                             |
  +-----------------------------------------------------------+
```
Progress stages:
- 0-40%: Extracting catalogue data from database
- 40-70%: Collecting digital object files
- 70-80%: Building search index
- 80-90%: Packaging viewer
- 90-100%: Creating ZIP archive

### Step 4: Download
```
  +-----------------------------------------------------------+
  |  Export complete! 1,234 descriptions, 567 objects (45 MB)  |
  |                                                             |
  |  [ Download ZIP ]   [ Share Link ]                          |
  +-----------------------------------------------------------+
```

---

## Creating an Export (CLI)

### Basic Commands
```bash
# Export entire catalogue as ZIP
php symfony portable:export --scope=all --zip --output=/tmp/catalogue.zip

# Export a specific fonds
php symfony portable:export --scope=fonds --slug=example-fonds

# Export by repository
php symfony portable:export --scope=repository --repository-id=5

# Export with edit mode enabled
php symfony portable:export --scope=all --mode=editable

# Metadata only (no digital objects)
php symfony portable:export --scope=all --no-objects

# Include master files (large!)
php symfony portable:export --scope=all --include-masters

# Custom title and language
php symfony portable:export --scope=all --title="My Collection" --culture=af
```

### CLI Options
```
+-------------------+--------------------------------------------------+
| Option            | Description                                      |
+-------------------+--------------------------------------------------+
| --scope           | all, fonds, repository, or custom                |
| --slug            | Fonds/description slug (scope=fonds)             |
| --repository-id   | Repository ID (scope=repository)                 |
| --mode            | read_only (default) or editable                  |
| --culture         | Language code: en, fr, af, pt (default: en)      |
| --title           | Export title (default: Portable Catalogue)        |
| --output          | Output path (directory or .zip)                  |
| --zip             | Create ZIP archive                               |
| --no-objects      | Skip digital objects (metadata only)             |
| --no-thumbnails   | Skip thumbnail images                            |
| --no-references   | Skip reference images                            |
| --include-masters | Include original master files                    |
| --export-id       | Process an existing export job by ID             |
+-------------------+--------------------------------------------------+
```

---

## Using the Portable Viewer

### Opening the Viewer
```
  1. Extract the ZIP (or burn to CD/copy to USB)
  2. Open index.html in any modern browser
     - Chrome, Firefox, Edge, Safari all supported
     - No server or internet connection needed
     - Works from local filesystem (file:// protocol)
```

### Browse Mode
```
  +---------------------------+-------------------------------+
  |  Hierarchy                |  Description Detail           |
  |                           |                               |
  |  v Fonds A                |  Series 1                     |
  |    v Series 1             |  [Series] [REF-001]           |
  |      > File 1.1           |                               |
  |      > File 1.2           |  Scope and Content            |
  |    > Series 2             |  This series contains...      |
  |  > Fonds B                |                               |
  |                           |  Dates                        |
  |  [Expand All]             |  Creation: 1950-1960          |
  |  [Collapse All]           |                               |
  |                           |  Subject Access Points        |
  |                           |  [History] [Land reform]      |
  |                           |                               |
  |                           |  Sub-levels (3)               |
  |                           |  > File 1.1                   |
  |                           |  > File 1.2                   |
  |                           |  > File 1.3                   |
  +---------------------------+-------------------------------+
```

### Search Mode
```
  +-----------------------------------------------------------+
  |  [ Search descriptions...                    ] [Search]    |
  |                                                             |
  |  3 results for "land reform"                                |
  |                                                             |
  |  Land Reform Records                                        |
  |  [REF-042] [Series]                                         |
  |  ...correspondence relating to <mark>land reform</mark>... |
  |                                                             |
  |  Title Deeds Collection                                     |
  |  [REF-089] [File]                                           |
  |  ...documents pertaining to <mark>land reform</mark>...    |
  +-----------------------------------------------------------+
```

### Edit Mode (Editable Exports Only)
```
  +-----------------------------------------------------------+
  |  Edit Mode                                                  |
  |                                                             |
  |  Drag & drop files here or click to browse                  |
  |  Images, PDFs, and documents accepted                       |
  |                                                             |
  |  Imported Files (2)                                         |
  |  +-------------------------------------------------------+ |
  |  | [photo.jpg]  234 KB  Caption: [ Site overview     ]    | |
  |  | [notes.pdf]  89 KB   Caption: [ Field notes       ]    | |
  |  +-------------------------------------------------------+ |
  |                                                             |
  |  Notes Summary                                              |
  |  +-------------------------------------------------------+ |
  |  | Correspondence File A                                  | |
  |  | "Contains letters from 1952 relating to..."           | |
  |  +-------------------------------------------------------+ |
  |                                                             |
  |  [ Export Changes (researcher-exchange.json) ]              |
  +-----------------------------------------------------------+
```

When browsing descriptions in edit mode, each description has a
"Research Notes" textarea at the bottom where you can add observations.

The "Export Changes" button downloads a `researcher-exchange.json` file
that can be submitted to the archive for import via ahgResearcherPlugin.

---

## Sharing Exports

### Generate a Download Token
```
  Past Exports table > [Share Link] button

  +-----------------------------------+
  |  Share Download Link              |
  |                                    |
  |  Max Downloads: [ 5  ]            |
  |  Expires After: [ 168 ] hours     |
  |                                    |
  |  [ Generate Link ]                |
  |                                    |
  |  Share URL:                        |
  |  https://psis.theahg.co.za/       |
  |  portable-export/download?         |
  |  token=abc123...  [Copy]          |
  +-----------------------------------+
```

Tokens support:
- Maximum download count (or unlimited)
- Expiry time (default 7 days)
- No login required for token-based downloads

---

## Output Structure
```
  portable-export.zip
  |
  +-- index.html              <- Open this in a browser
  +-- assets/
  |   +-- css/viewer.css
  |   +-- js/app.js
  |   +-- js/search.js
  |   +-- js/tree.js
  |   +-- js/import.js         <- Edit mode only
  |   +-- lib/
  |       +-- bootstrap.bundle.min.js
  |       +-- bootstrap.min.css
  |       +-- bootstrap-icons.min.css
  |       +-- flexsearch.min.js
  |       +-- fonts/
  +-- data/
  |   +-- catalogue.json       <- All descriptions
  |   +-- search-index.json    <- Pre-built search index
  |   +-- taxonomies.json      <- Subjects, places, genres
  |   +-- config.json          <- Viewer settings
  |   +-- manifest.json        <- File checksums
  +-- objects/
      +-- thumb/               <- Thumbnail images
      +-- ref/                 <- Reference images
      +-- pdf/                 <- PDF access copies
      +-- master/              <- Master files (if included)
```

---

## Use Cases

### Delivering to Clients
```
  Archive creates export (scope=fonds for client's collection)
  -> Burns to CD or copies to USB
  -> Client opens index.html in browser
  -> Full offline access to their collection
```

### Exhibition Kiosk
```
  Export with scope=repository, read-only mode
  -> Load onto kiosk computer
  -> Visitors browse collection without internet
```

### Researcher Field Work
```
  Export with editable mode enabled
  -> Researcher takes USB to field location
  -> Browses catalogue, adds notes to descriptions
  -> Imports photos from field work
  -> Exports researcher-exchange.json
  -> Submits back to archive on return
```

### Disaster Recovery Copy
```
  Full catalogue export (scope=all, include masters)
  -> Store on external drive in secure location
  -> Complete offline reference copy of all holdings
```

### NARSSA Handover
```
  Export specific repository holdings
  -> Include all metadata and reference images
  -> Provide as self-contained package to NARSSA
```

---

## Tips
```
+-------------------------------------------------------------+
|  TIP: Large exports with master files can be very large.     |
|  Consider excluding masters for most use cases.              |
+-------------------------------------------------------------+
|  TIP: The CLI command is better for large exports — the      |
|  web UI launches the same process in the background.         |
+-------------------------------------------------------------+
|  TIP: Edit mode exports include import.js which adds ~5KB   |
|  to the package. Read-only mode is slightly smaller.         |
+-------------------------------------------------------------+
|  TIP: The viewer works on Chrome, Firefox, Edge, and Safari. |
|  Internet Explorer is NOT supported.                         |
+-------------------------------------------------------------+
|  TIP: Share links expire after 7 days by default.            |
|  Set a longer expiry for permanent sharing.                  |
+-------------------------------------------------------------+
```
