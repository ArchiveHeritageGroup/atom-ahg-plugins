<div align="center">

# 🏛️ AtoM Extensions Catalog

**A curated collection of extensions for [Access to Memory](https://www.accesstomemory.org/) 2.10**

[![AtoM Version](https://img.shields.io/badge/AtoM-2.10.x-blue.svg)](https://www.accesstomemory.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.3-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--3.0-green.svg)](LICENSE)
[![Extensions](https://img.shields.io/badge/Extensions-18+-orange.svg)](#available-extensions)

[Quick Start](#quick-start) • [Extensions](#available-extensions) • [Installation](#installation) • [Documentation](#documentation)

</div>

---

## 📊 Overview

The AtoM AHG Framework extends Access to Memory 2.10 with Laravel Query Builder integration while maintaining full Symfony compatibility. This catalog provides a modular plugin ecosystem designed for GLAM institutions (Galleries, Libraries, Archives, Museums) with comprehensive support for South African legislative compliance and international standards.

### Category Summary

| Category | Count | Description |
|----------|-------|-------------|
| 🎨 [Themes](#-themes) | 1 | Bootstrap 5 enhanced theme with responsive design |
| 🏛️ [GLAM Sectors](#-glam-sector-plugins) | 4 | Sector-specific cataloging standards and workflows |
| 📁 [DAM](#-glam-sector-plugins) | 1 | Digital Asset Management with metadata extraction |
| 🔬 [Research](#-research--access) | 2 | Researcher registration, workspace, and access control |
| 🔒 [Security](#-required-installed-by-default) | 1 | Multi-level security classification (Public to Top Secret) |
| 📋 [Compliance](#-compliance--governance) | 2 | Privacy (6 jurisdictions) and Heritage Accounting (10 standards) |
| 🛠️ [Utilities](#-utility-plugins) | 5 | Backup, audit, rights, vendor, and donor management |
| 🎬 [Media](#-media--enhanced-features) | 6 | 3D viewers, audio/video players, transcription, metadata |

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
| 3D Viewer Templates | `plugins/arAHGThemeB5Plugin/templates/` |
| 3D Thumbnail Tools | `atom-framework/tools/3d-thumbnail/` |
| Media Player JS | `plugins/arAHGThemeB5Plugin/js/atom-media-player.js` |
| Transcription Job | `lib/task/job/arTranscriptionJob.class.php` |
| Metadata Extractor | `plugins/arAHGThemeB5Plugin/lib/arUniversalMetadataExtractor.php` |
| TIFF-PDF Module | `plugins/arAHGThemeB5Plugin/modules/tiffpdfmerge/` |
| Media Helper | `lib/helper/MediaHelper.php` |

---

### 🎨 Themes

**Bootstrap 5 Enhanced Theme** - A modern, responsive interface built on Bootstrap 5 with:
- Configurable color schemes and branding
- Enhanced dashboard with widgets
- Mobile-responsive design for public and staff interfaces
- Extended settings panel for institutional customization
- Integrated GLAM sector switching

---

### 🏛️ GLAM Sector Support

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

### 🔬 Research & Access

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

### 🔒 Security Classification

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

### 📋 Compliance & Governance

#### Privacy Compliance (6 Jurisdictions)

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

#### Heritage Asset Accounting (10 Standards)

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

### 🛠️ Utility Plugins

| Plugin | Purpose |
|--------|---------|
| **Backup Plugin** | Scheduled database backups with retention policies |
| **Audit Trail Plugin** | Comprehensive change logging with before/after values |
| **Extended Rights Plugin** | Advanced rights statements with embargo management |
| **Donor Agreement Plugin** | Donor tracking, agreements, restrictions, and reminders |
| **Vendor Plugin** | Supplier management for conservation, digitization, storage |

---

## ⚡ Quick Start

### Prerequisites

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| AtoM | 2.10.0 | 2.10.1+ |
| PHP | 8.1 | 8.3 |
| MySQL | 8.0 | 8.0+ |
| Composer | 2.x | Latest |

### Optional Dependencies (for Media Features)

| Tool | Purpose |
|------|---------|
| FFmpeg/FFprobe | Audio/video transcoding and metadata |
| Blender | 3D thumbnail generation |
| Whisper | Speech-to-text transcription |
| ImageMagick | Image processing, TIFF conversion |
| Ghostscript | PDF/A generation |
| exiftool | Advanced metadata extraction |

### Installation Overview
```bash
# Clone framework
cd /usr/share/nginx/atom  # or /usr/share/nginx/archive
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

# Shortcut commands
php bin/atom discover             # Same as extension:discover
php bin/atom install <name>       # Same as extension:install
php bin/atom enable <name>        # Same as extension:enable
```

---

## 📁 Repository Structure

| Repository | URL | Purpose |
|------------|-----|---------|
| atom-framework | [GitHub](https://github.com/ArchiveHeritageGroup/atom-framework) | Core Laravel foundation, CLI tools, services |
| atom-ahg-plugins | [GitHub](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins) | Theme + all extension plugins |
| atom-extensions-catalog | [GitHub](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog) | This directory/documentation |

---
### Development  and play environment
```
Path: /usr/share/nginx/atom
URL: https://psis.theahg.co.za
```

---

## 📚 Documentation

- [Installation Guide](INSTALLATION.md)
- [Architecture Overview](docs/ARCHITECTURE.md)
- [Plugin Development](docs/PLUGIN_DEVELOPMENT.md)
- [CLI Reference](docs/CLI_REFERENCE.md)
- [Media Features Guide](docs/MEDIA_FEATURES.md)

---

## 🤝 Contributing

Contributions are welcome! Please read our contributing guidelines.

## 📄 License

This project is licensed under the GPL-3.0 License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

**The Archive and Heritage Group**

---

<div align="center">

Made with ❤️ for the archival community

</div>