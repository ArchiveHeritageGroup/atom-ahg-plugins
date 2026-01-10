<div align="center">

# 🏛️ AtoM Extensions Catalog

**A curated collection of extensions for [Access to Memory](https://www.accesstomemory.org/) 2.8 to 2.10**

[![AtoM Version](https://img.shields.io/badge/AtoM-2.8.x--2.10.x-blue.svg)](https://www.accesstomemory.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.3-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--3.0-green.svg)](LICENSE)
[![Extensions](https://img.shields.io/badge/Extensions-18+-orange.svg)](#available-extensions)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3.svg)](https://getbootstrap.com/)

[Quick Start](#-quick-start) • [Extensions](#-available-extensions) • [User Guides](#-user-guides) • [Installation](#installation) • [Documentation](#-documentation)

</div>

---

## 📊 Overview

The AtoM AHG Framework extends Access to Memory 2.8+ with Laravel Query Builder (Illuminate\Database) integration while maintaining full Symfony 1.x compatibility. This catalog provides a modular plugin ecosystem designed for GLAM institutions (Galleries, Libraries, Archives, Museums) with comprehensive support for South African legislative compliance (POPIA, NARSSA, GRAP 103), international standards (GDPR, ISAD(G), Spectrum 5.0), and Records in Contexts (RiC) integration.

### Architecture
```
┌─────────────────────────────────────────────────────────────────┐
│                     AtoM 2.10 BASE (Symfony 1.x)                │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 1: atom-framework (REQUIRED)                             │
│  • Laravel Query Builder (Illuminate\Database)                  │
│  • Extension Manager CLI & Service                              │
│  • Base Repository/Service classes                              │
│  • Helper classes                                               │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 2: atom-ahg-plugins (OPTIONAL per plugin)                │
│  • ahgThemeB5Plugin (REQUIRED - locked)                         │
│  • ahgSecurityClearancePlugin (REQUIRED - locked)               │
│  • ahgDisplayPlugin, ahgLibraryPlugin, etc. (optional)          │
└─────────────────────────────────────────────────────────────────┘
```

### Category Summary

| Category | Count | Description |
|----------|-------|-------------|
| 🎨 [Themes](#-themes) | 1 | Bootstrap 5 enhanced theme with responsive design |
| 🏛️ [GLAM Sectors](#-glam-sector-plugins) | 4 | Sector-specific cataloging standards and workflows |
| 📁 [DAM](#-glam-sector-plugins) | 1 | Digital Asset Management with metadata extraction |
| 🔬 [Research](#-research--access) | 2 | Researcher registration, workspace, and access control |
| 🔒 [Security](#-required-installed-by-default) | 1 | Multi-level security classification (Public to Top Secret) |
| 📋 [Compliance](#-compliance--governance) | 2 | Privacy (6 jurisdictions) and Heritage Accounting (10 standards) |
| 🛠️ [Utilities](#-utility-plugins) | 5 | Backup, audit, rights, vendor, donor management and data migration |
| 🎬 [Media](#-media--enhanced-features) | 6 | 3D viewers, audio/video players, transcription, metadata |
| 🤖 [AI/NLP](#-ai--nlp) | 1 | Named Entity Recognition for automatic metadata extraction |

---

## 📖 User Guides

Step-by-step guides with visual flow diagrams for end users (non-technical):

### Core Features

| Guide | Description |
|-------|-------------|
| [Advanced Search](docs/advanced-search-user-guide.md) | Boolean operators, filters, saved searches, facets |
| [Audit Trail](docs/audit-trail-user-guide.md) | Track changes, view history, export logs |
| [Backup & Restore](docs/backup-restore-user-guide.md) | Create backups, restore data, download copies |
| [Barcode System](docs/barcode-user-guide.md) | Generate, print, and scan barcodes for tracking |
| [Reports Dashboard](docs/reports-dashboard-user-guide.md) | Generate collection, activity, and compliance reports |

### GLAM Sector Modules

| Guide | Description |
|-------|-------------|
| [Museum Module](docs/museum-module-user-guide.md) | CCO/Spectrum cataloging, provenance, locations |
| [Library Module](docs/library-module-user-guide.md) | Dublin Core/MARC cataloging, serials, call numbers |
| [Gallery Module](docs/gallery-module-user-guide.md) | CCO visual arts, artists, exhibitions, editions |
| [DAM Module](docs/dam-module-user-guide.md) | Digital assets, IPTC metadata, batch upload |
| [Spectrum 5.0](docs/spectrum-user-guide.md) | Museum procedures - loans, movements, condition, valuations |

### Media & Viewers

| Guide | Description |
|-------|-------------|
| [3D Model Viewer](docs/3d-model-viewer-user-guide.md) | View GLB/GLTF models, AR support, controls |
| [Audio Player](docs/audio-player-user-guide.md) | Waveform display, snippets, transcription |
| [OpenSeadragon](docs/openseadragon-user-guide.md) | Deep zoom for high-resolution images |
| [Mirador Viewer](docs/mirador-user-guide.md) | IIIF comparison, annotations, multi-page |
| [IIIF Integration](docs/iiif-integration-user-guide.md) | Image server features, sharing, navigation |

### Security & Compliance

| Guide | Description |
|-------|-------------|
| [Security Classification](docs/security-classification-user-guide.md) | Clearance levels, classifying records, access control |
| [Privacy Compliance](docs/privacy-compliance-user-guide.md) | POPIA/GDPR, DSAR requests, breach management |
| [Embargo System](docs/embargo-user-guide.md) | Setting embargoes, types, propagation |
| [Extended Rights](docs/extended-rights-user-guide.md) | Copyright, Creative Commons, TK Labels |

### Workflows & Tools

| Guide | Description |
|-------|-------------|
| [Researcher Portal](docs/researcher-user-guide.md) | Registration, workspace, bookings, favorites |
| [Condition Assessment](docs/condition-assessment-user-guide.md) | Damage categories, annotations, recommendations |
| [Heritage Accounting](docs/heritage-accounting-user-guide.md) | GRAP 103 valuations, movements, compliance |
| [Vendor Management](docs/vendor-user-guide.md) | Track items at external vendors, transactions |
| [PDF Merge Tool](docs/pdf-merge-user-guide.md) | Combine images into PDF/A documents |
| [Data Migration](docs/data-migration-user-guide.md) | Import from Vernon, ArchivesSpace, PastPerfect, CSV/Excel |

---

## 🎬 Media & Enhanced Features

Built-in framework enhancements for rich media handling:

### 3D Model Viewers

| Feature | Description |
|---------|-------------|
| **Interactive 3D Viewer** | Three.js with drag-rotate, scroll-zoom, pan controls |
| **GLB/GLTF Viewer** | Google Model-viewer with AR support on mobile |
| **Fullscreen Mode** | Auto-rotate, reset camera, ESC to close |
| **3D Thumbnail Generation** | Blender CLI renders thumbnails for GLB, OBJ, STL, FBX, PLY, DAE |
| **Auto-queue on Upload** | Observer pattern triggers background rendering |

### Enhanced Audio/Video Player

| Feature | Description |
|---------|-------------|
| **Waveform Visualization** | Canvas-based waveform display for audio files |
| **Playback Controls** | Play/pause, skip ±10s, speed 0.5x-2x, volume, loop |
| **Keyboard Shortcuts** | Space, arrows, M (mute), L (loop), F (fullscreen), 0-9 (seek) |
| **Legacy Format Streaming** | FFmpeg transcoding for AVI, MOV, WMV, AIFF, AU to browser-compatible formats |

### Snippet Creation

| Feature | Description |
|---------|-------------|
| **IN/OUT Points** | Mark start/end with I/O keys or buttons |
| **Visual Region Overlay** | See selection on progress bar and waveform |
| **Save & Export** | FFmpeg extracts clips with metadata |
| **Thumbnails** | Auto-generated for saved snippets |

### Speech-to-Text Transcription

| Feature | Description |
|---------|-------------|
| **Whisper Integration** | OpenAI Whisper (tiny → large-v3 models) |
| **90+ Languages** | Including Afrikaans, Zulu, Xhosa, and all major languages |
| **Subtitle Output** | Auto-generates VTT and SRT files |
| **Word Timestamps** | Click any word to jump to that position |
| **Full-text Search** | Transcriptions indexed for discovery |

### Metadata Extraction

| File Type | What's Extracted |
|-----------|------------------|
| **Images** | EXIF (camera, GPS, dates), IPTC (keywords, caption), XMP (Dublin Core) |
| **Audio/Video** | Codec, duration, bitrate, channels, sample rate via FFprobe |
| **PDF** | Title, author, pages, keywords via pdfinfo |
| **Office** | Document properties from DOCX, XLSX |

### TIFF to PDF Merge

| Feature | Description |
|---------|-------------|
| **Batch Upload** | Select multiple TIFF, JPEG, PNG files |
| **Drag-drop Reorder** | Arrange pages before merge |
| **PDF/A Standards** | Output as PDF/A-1b, PDF/A-2b, PDF/A-3b for archival |
| **Background Processing** | Jobs run via framework worker service |
| **Record Attachment** | Optionally attach output PDF to archival description |

### Component Locations

| Component | Path |
|-----------|------|
| 3D Viewer Templates | `plugins/ahgThemeB5Plugin/templates/` |
| 3D Thumbnail Tools | `atom-framework/tools/3d-thumbnail/` |
| Media Player JS | `plugins/ahgThemeB5Plugin/js/atom-media-player.js` |
| Transcription Job | `lib/task/job/arTranscriptionJob.class.php` |
| Metadata Extractor | `plugins/ahgThemeB5Plugin/lib/arUniversalMetadataExtractor.php` |
| TIFF-PDF Module | `plugins/ahgThemeB5Plugin/modules/tiffpdfmerge/` |
| Media Helper | `lib/helper/MediaHelper.php` |

---

## 🎨 Themes

**Bootstrap 5 Enhanced Theme** - A modern, responsive interface built on Bootstrap 5 with:
- Configurable color schemes and branding
- Enhanced dashboard with widgets
- Mobile-responsive design for public and staff interfaces
- Extended settings panel for institutional customization
- Integrated GLAM sector switching

---

## 🏛️ GLAM Sector Support

Full multi-sector support for cultural heritage institutions:

| Sector | Standard | Features |
|--------|----------|----------|
| **Archives** | ISAD(G), RAD, DACS | Hierarchical descriptions, finding aids, access points |
| **Museums** | CCO, Spectrum 5.0 | Object cataloging, acquisition, loans, condition reports |
| **Libraries** | RDA, MARC | Bibliographic records, holdings, circulation concepts |
| **Galleries** | CCO, VRA Core | Visual arts cataloging, exhibition management |
| **DAM** | Dublin Core, IPTC | Digital asset ingest, metadata extraction, derivatives |

Each sector includes:
- Customized data entry templates
- Sector-specific controlled vocabularies
- Level of description filtering
- Display mode switching between sectors

---

## 🔬 Research & Access

Comprehensive researcher management system:

| Feature | Description |
|---------|-------------|
| **Researcher Registration** | Self-registration with approval workflow |
| **Security Clearance** | Tiered access levels (Public → Top Secret) |
| **Access Requests** | Request, approve, and track access to restricted materials |
| **Reading Room** | Virtual reading room with document requests |
| **Researcher Workspace** | Personal workspace with saved searches and favorites |
| **Renewal & Expiry** | Automatic expiry notifications and renewal workflows |

---

## 🔒 Security Classification

Multi-level security classification system aligned with government standards:

| Level | Description |
|-------|-------------|
| **Public** | Open access, no restrictions |
| **Internal** | Staff access only |
| **Confidential** | Need-to-know basis |
| **Secret** | Classified, restricted access |
| **Top Secret** | Highest classification |

Features include:
- Hierarchical access control
- Watermarking for sensitive downloads
- Comprehensive audit trails
- Declassification scheduling
- Compartmentalization support

---

## 📋 Compliance & Governance

### Privacy Compliance (6 Jurisdictions)

| Region | Law | Features |
|--------|-----|----------|
| **Africa** | POPIA (South Africa) | PAIA integration, Information Officer management |
| | NDPA (Nigeria) | NITDA compliance |
| | Kenya DPA | ODPC reporting |
| **Europe** | GDPR | DPO management, cross-border transfers, DPIA |
| **North America** | PIPEDA (Canada) | OPC compliance |
| | CCPA/CPRA (California) | Consumer rights management |

Core privacy features:
- DSAR (Data Subject Access Request) tracking
- Breach register and notification management
- Consent management
- ROPA (Records of Processing Activities)
- Privacy impact assessments

### Heritage Asset Accounting (10 Standards)

| Standard | Region | Authority |
|----------|--------|-----------|
| **GRAP 103** | South Africa | ASB |
| **FRS 102** | United Kingdom | FRC |
| **GASB 34** | United States | GASB |
| **IPSAS 45** | International | IPSASB |
| **AASB 1059** | Australia | AASB |
| **PSAB** | Canada | PSAB |
| **NZ PBE** | New Zealand | XRB |
| **GBE** | Germany | GASB |
| **PCG** | France | ANC |
| **Custom** | User-defined | Configurable |

Heritage accounting features:
- Asset recognition and measurement
- Valuation tracking (historical, fair value, nominal)
- Impairment testing
- Movement records (acquisitions, disposals, transfers)
- Journal entries and audit trail
- Compliance reporting per standard

---

## 🛠️ Utility Plugins

| Plugin | Purpose |
|--------|---------|
| **Backup Plugin** | Scheduled database backups with retention policies |
| **Audit Trail Plugin** | Comprehensive change logging with before/after values |
| **Extended Rights Plugin** | Advanced rights statements with embargo management |
| **Donor Agreement Plugin** | Donor tracking, agreements, restrictions, and reminders |
| **Vendor Plugin** | Supplier management for conservation, digitization, storage |

---

## 🔄 Data Migration Tool

Universal data migration and field mapping system for importing from legacy systems:

### Supported Source Systems

| System | Formats | Auto-Detection |
|--------|---------|----------------|
| **Vernon CMS** | XML, CSV | ✅ Object Number, Primary Maker fields |
| **ArchivesSpace** | EAD, CSV | ✅ resource_id, ref_id fields |
| **DB/TextWorks** | CSV, XML | ✅ Flexible textbase structure |
| **PastPerfect** | CSV | ✅ objectid, objname fields |
| **CollectiveAccess** | XML | ✅ ca_objects, idno fields |
| **Generic** | CSV, XML, EAD | Manual mapping |

### Destination Sectors

| Sector | Standard | Key Fields |
|--------|----------|------------|
| **Archives** | ISAD(G) | Reference code, Fonds, Series, File, Item |
| **Museum** | CCO/Spectrum | Object number, Object name, Dimensions |
| **Library** | RDA/MARC | Call number, ISBN, Author, Publisher |
| **Gallery** | VRA Core | Artwork title, Artist, Medium, Dimensions |
| **DAM** | Dublin Core | Identifier, Title, Creator, Rights |

### Features

| Feature | Description |
|---------|-------------|
| **Auto-Detection** | Identifies source system from file headers/structure |
| **Visual Field Mapper** | Drag-drop interface for field mapping |
| **Transformations** | Split, combine, format dates, normalize values |
| **Concatenation** | Combine multiple source fields with separators |
| **Constants** | Add fixed values to any target field |
| **Save Templates** | Save custom mappings for reuse |
| **Preview** | Review transformed data before import |
| **Validation** | Check for required fields, data types, duplicates |
| **Direct Import** | Import directly to AtoM database |
| **Export CSV** | Generate AtoM-compatible CSV for manual import |

---

## ⚡ Quick Start

### Prerequisites

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| AtoM | 2.10.0 | 2.10.1+ |
| PHP | 8.1 | 8.3 |
| MySQL | 8.0 | 8.0+ |
| Composer | 2.x | Latest |
| Nginx | 1.18+ | Latest |

### Optional Dependencies (for Media Features)

| Tool | Purpose |
|------|---------|
| FFmpeg/FFprobe | Audio/video transcoding and metadata |
| Blender | 3D thumbnail generation |
| Whisper | Speech-to-text transcription |
| ImageMagick | Image processing, TIFF conversion |
| Ghostscript | PDF/A generation |
| exiftool | Advanced metadata extraction |
| Cantaloupe | IIIF image server |
| Apache Jena Fuseki | RiC triplestore for SPARQL queries |

### Installation Overview
```bash
# Clone framework
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
cd atom-framework
composer install

# Clone plugins
cd ..
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# Install
cd atom-framework
bash bin/install

# Restart services
sudo service php8.3-fpm restart

# Discover extensions
php bin/atom extension:discover
```

📖 **See [INSTALLATION.md](INSTALLATION.md) for detailed instructions**

---

## 📦 Available Extensions

### 🔒 Required (Installed by Default)

These are installed automatically with the framework:

| Extension | Description | Version | Locked |
|-----------|-------------|---------|--------|
| [ahgThemeB5Plugin](extensions/ahgThemeB5Plugin.md) | Bootstrap 5 enhanced theme | 1.1.x | Yes |
| [ahgSecurityClearancePlugin](extensions/ahgSecurityClearancePlugin.md) | Security classification system | 1.0.x | Yes |
| [ahgDisplayPlugin](extensions/ahgDisplayPlugin.md) | Display profiles & modes | 1.0.x | No |

### 🏛️ GLAM Sector Plugins

| Extension | Description | Version | Status |
|-----------|-------------|---------|--------|
| [ahgMuseumPlugin](extensions/ahgMuseumPlugin.md) | Museum cataloging with CCO support | 1.0.x | ✅ Stable |
| [ahgSpectrumPlugin](extensions/ahgSpectrumPlugin.md) | Spectrum 5.0 procedures | 1.0.x | ✅ Stable |
| [ahgLibraryPlugin](extensions/ahgLibraryPlugin.md) | Library cataloging with RDA/MARC | 1.0.x | ✅ Stable |
| [ahgGalleryPlugin](extensions/ahgGalleryPlugin.md) | Gallery/visual arts cataloging | 1.0.x | ✅ Stable |
| [ahgDAMPlugin](extensions/ahgDAMPlugin.md) | Digital Asset Management | 1.0.x | ✅ Stable |

### 🔬 Research & Access

| Extension | Description | Version | Status |
|-----------|-------------|---------|--------|
| [ahgResearchPlugin](extensions/ahgResearchPlugin.md) | Researcher portal & workspace | 1.0.x | ✅ Stable |
| [ahgAccessRequestPlugin](extensions/ahgAccessRequestPlugin.md) | Access request management | 1.0.x | ✅ Stable |

### 📋 Compliance & Governance

| Extension | Description | Version | Status |
|-----------|-------------|---------|--------|
| [ahgPrivacyPlugin](extensions/ahgPrivacyPlugin.md) | Multi-jurisdiction privacy compliance | 1.0.x | ✅ Stable |
| [ahgHeritageAccountingPlugin](extensions/ahgHeritageAccountingPlugin.md) | Heritage asset accounting | 1.1.x | ✅ Stable |
| [ahgExtendedRightsPlugin](extensions/ahgExtendedRightsPlugin.md) | Advanced rights & embargo management | 1.0.x | ✅ Stable |

### 🛠️ Utility Plugins

| Extension | Description | Version | Status |
|-----------|-------------|---------|--------|
| [ahgBackupPlugin](extensions/ahgBackupPlugin.md) | Database backup management | 1.0.x | ✅ Stable |
| [ahgAuditTrailPlugin](extensions/ahgAuditTrailPlugin.md) | Comprehensive audit logging | 1.0.x | ✅ Stable |
| [ahgDonorAgreementPlugin](extensions/ahgDonorAgreementPlugin.md) | Donor agreements & reminders | 1.0.x | ✅ Stable |
| [ahgVendorPlugin](extensions/ahgVendorPlugin.md) | Vendor/supplier management | 1.0.x | ✅ Stable |
| [ahgNerPlugin](extensions/ahgNerPlugin.md) | Named Entity Recognition | 1.0.x | ✅ Stable |

---

## 🔧 CLI Commands
```bash
# Framework commands
php bin/atom framework:install    # Initial framework setup
php bin/atom framework:update     # Update framework
php bin/atom framework:version    # Show version

# Extension commands
php bin/atom extension:discover   # Find available extensions
php bin/atom extension:list       # List installed extensions
php bin/atom extension:enable <name>   # Enable extension
php bin/atom extension:disable <name>  # Disable extension
php bin/atom extension:install <name>  # Install extension

# Media commands
php atom-framework/bin/generate-3d-thumbnails.php        # Batch 3D thumbnails
php atom-framework/bin/generate-3d-thumbnails.php --id=X # Single 3D thumbnail

# Migration commands
php bin/atom migrate run          # Run pending migrations
php bin/atom migrate status       # Show migration status

# Version release
./bin/release patch "message"     # Bump patch version
./bin/release minor "message"     # Bump minor version  
./bin/release major "message"     # Bump major version
```

---

## 📁 Repository Structure

| Repository | URL | Purpose |
|------------|-----|---------|
| atom-framework | [GitHub](https://github.com/ArchiveHeritageGroup/atom-framework) | Core Laravel foundation, CLI tools, services |
| atom-ahg-plugins | [GitHub](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins) | Theme + all extension plugins |
| atom-extensions-catalog | [GitHub](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog) | This directory/documentation |

### Server Paths

| Server | ATOM_ROOT | FRAMEWORK_PATH | PLUGINS_PATH |
|--------|-----------|----------------|--------------|
| Development | `/usr/share/nginx/atom` | `/usr/share/nginx/atom/atom-framework` | `/usr/share/nginx/atom/atom-ahg-plugins` |
| Test | `/usr/share/nginx/atom` | `/usr/share/nginx/atom/atom-framework` | `/usr/share/nginx/atom/atom-ahg-plugins` |

---

## 🌐 Nginx Configuration

📖 **See [INSTALLATION.md](INSTALLATION.md) for complete nginx configurations including:**
- HTTPS with SSL/TLS (Production)
- HTTP only (Development)
- IIIF/Cantaloupe integration
- RiC Explorer & SPARQL proxy
- Bot protection & rate limiting
- Media API routes

---

## 📚 Technical Documentation

- [Installation Guide](INSTALLATION.md)
- [Architecture Overview](docs/ARCHITECTURE.md)
- [Modified Files Reference](docs/MODIFIED_FILES.md)
- [Plugin Development](docs/PLUGIN_DEVELOPMENT.md)
- [RIC Integration Guide](docs/RIC_INTEGRATION_GUIDE.md)
- [CLI Reference](docs/CLI_REFERENCE.md)
- [API, Reporting and Data Export](docs/API_REPORTING_EXPORT.md)
- [Media Features Guide](docs/MEDIA_FEATURES.md)
- [Library Plugin Architecture](docs/AtoM_AHG_Framework_Library_Architecture_Diagrams.md)

---

## 🔑 Key Files Reference

| File | Purpose | Modifiable |
|------|---------|------------|
| `atom-framework/bin/install` | Main install script | Yes |
| `atom-framework/bin/atom` | CLI entry point | Yes |
| `atom-framework/config/ProjectConfiguration.class.php.template` | Template for AtoM config | Yes |
| `atom-framework/database/install.sql` | Database schema | Yes |
| `atom-framework/src/Extensions/ExtensionManager.php` | Plugin management | Yes |
| `plugins/sfPluginAdminPlugin/*/themesAction.class.php` | Theme listing | Patched |

---

## 🤝 Contributing

Contributions are welcome! Please read our contributing guidelines.

## 📄 License

This project is licensed under the GPL-3.0 License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

**The Archive and Heritage Group**

https://theahg.co.za

johan@theahg.co.za

---

<div align="center">

Made with ❤️ for the archival community

[Report Bug](https://github.com/ArchiveHeritageGroup/atom-framework/issues) • [Request Feature](https://github.com/ArchiveHeritageGroup/atom-framework/issues)

</div>


---

## 🤖 AI & NLP

Automated metadata extraction using machine learning:

| Feature | Description |
|---------|-------------|
| **Named Entity Recognition** | Extract people, places, organizations from text |
| **Entity Types** | Person, Organization, Place, Date, Event, Work |
| **Review Dashboard** | Staff review and approve extracted entities |
| **Authority Linking** | Link entities to existing authority records |
| **Batch Processing** | Process multiple records at once |
| **Python/spaCy Backend** | Industry-standard NLP with 90+ language support |

📖 **User Guide:** [Named Entity Recognition](docs/user-guide-ner.md)

---

## 📖 Technical Documentation

Detailed technical reference with architecture diagrams, ERD schemas, and service methods.

**[View All Technical Documentation →](docs/technical/README.md)**

| Plugin | Category | Documentation |
|--------|----------|---------------|
| ahgAPIPlugin | Integration | [Technical Docs](docs/technical/ahgAPIPlugin.md) |
| ahgAuditTrailPlugin | Compliance | [Technical Docs](docs/technical/ahgAuditTrailPlugin.md) |
| ahgBackupPlugin | Administration | [Technical Docs](docs/technical/ahgBackupPlugin.md) |
| ahgSecurityClearancePlugin | Security | [Technical Docs](docs/technical/ahgSecurityClearancePlugin.md) |
| ahgPrivacyPlugin | Compliance | [Technical Docs](docs/technical/ahgPrivacyPlugin.md) |
| ahgEmbargoPlugin | Access Control | [Technical Docs](docs/technical/ahgEmbargoPlugin.md) |
| ahgConditionPlugin | Conservation | [Technical Docs](docs/technical/ahgConditionPlugin.md) |
| ahgSpectrumPlugin | Museum | [Technical Docs](docs/technical/ahgSpectrumPlugin.md) |
| ahgGrapPlugin | Financial | [Technical Docs](docs/technical/ahgGrapPlugin.md) |
| ahgDonorPlugin | Acquisitions | [Technical Docs](docs/technical/ahgDonorPlugin.md) |
| ahgVendorPlugin | Administration | [Technical Docs](docs/technical/ahgVendorPlugin.md) |
| ahgResearchPlugin | Public Services | [Technical Docs](docs/technical/ahgResearchPlugin.md) |
| ahgNerPlugin | AI/NLP | [Technical Docs](docs/technical/ahgNerPlugin.md) |

---

## 🔌 API Documentation

| Document | Description |
|----------|-------------|
| [REST API User Guide](docs/api-user-guide.md) | Non-technical guide for using the API |
| [REST API Technical Reference](docs/api-technical-reference.md) | Detailed technical documentation |

### Quick Start

```bash
# Test API connection
curl -H "X-API-Key: your-key" https://your-site.com/api/v2

# Get archival descriptions
curl -H "X-API-Key: your-key" https://your-site.com/api/v2/descriptions?sector=archive

# Search collections
curl -X POST -H "X-API-Key: your-key" -H "Content-Type: application/json" \
    -d '{"query":"meeting minutes"}' \
    https://your-site.com/api/v2/search
```

