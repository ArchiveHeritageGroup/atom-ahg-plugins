<div align="center">

# 🏛️ AtoM Extensions Catalog

**A curated collection of extensions for [Access to Memory](https://www.accesstomemory.org/) 2.8 to 2.10**

[![AtoM Version](https://img.shields.io/badge/AtoM-2.8.x--2.10.x-blue.svg)](https://www.accesstomemory.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.3-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--3.0-green.svg)](LICENSE)
[![Extensions](https://img.shields.io/badge/Extensions-18+-orange.svg)](#available-extensions)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3.svg)](https://getbootstrap.com/)

[Quick Start](#-quick-start) • [Extensions](#-available-extensions) • [Installation](#installation) • [Documentation](#-documentation) • [Nginx Config](#-nginx-configuration)

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

### Migration Workflow
```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   UPLOAD    │ →  │   DETECT    │ →  │    MAP      │ →  │   OUTPUT    │
│  CSV/XML/   │    │   Source    │    │   Fields    │    │  Preview &  │
│    EAD      │    │   System    │    │  to Sector  │    │   Import    │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
```

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

### CLI Commands
```bash
php bin/atom migration:import <file> --template=vernon    # Import with template
php bin/atom migration:validate <file>                    # Validate without importing
php bin/atom migration:templates                          # List available templates
```

### Component Locations

| Component | Path |
|-----------|------|
| Plugin | `atom-ahg-plugins/ahgDataMigrationPlugin/` |
| Parsers | `lib/Parsers/` (CsvParser, XmlParser, EadParser) |
| Sectors | `lib/Sectors/` (ArchivesSector, MuseumSector, etc.) |
| Templates | `data/templates/` (vernon.json, archivesspace.json, etc.) |
| UI Module | `modules/dataMigration/` |

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

### HTTPS Configuration (Production)

Full-featured nginx configuration with IIIF support, RiC integration, bot protection, and rate limiting:

```nginx
##
# Access to Memory (AtoM) - HTTPS with IIIF Support
# Updated: 2025-01-08 - Added bot protection
##

server {
    listen 80;
    listen [::]:80;
    server_name your-domain.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name your-domain.example.com;

    client_max_body_size 2G;

    root /usr/share/nginx/atom;
    index index.php index.html;

    # SSL Configuration - Update paths to your certificates
    ssl_certificate     /path/to/your/fullchain.pem;
    ssl_certificate_key /path/to/your/privkey.pem;
    ssl_trusted_certificate /path/to/your/chain.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'EECDH+AESGCM:EDH+AESGCM:AES256+EECDH:AES256+EDH';
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_prefer_server_ciphers on;

    access_log /var/log/nginx/atom_access.log;
    error_log  /var/log/nginx/atom_error.log;

    # ======================================
    # SECURITY HEADERS
    # ======================================
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # ======================================
    # BOT PROTECTION (uses maps from conf.d)
    # ======================================
    
    # Block bad bots by user agent
    if ($bad_bot) {
        return 444;
    }
    
    # Block listed IPs
    if ($blocked_ip) {
        return 403;
    }

    # ======================================
    # BLOCK COMMON PHP EXPLOIT SCANNERS
    # ======================================
    location ~* (eval-stdin\.php|phpunit|pearcmd|thinkphp|invokefunction|\.env|\.git|shell|cmd) {
        return 444;
    }

    # Block attempts to include remote files
    if ($query_string ~* "allow_url_include" ) { return 444; }
    if ($query_string ~* "auto_prepend_file" ) { return 444; }

    # ======================================
    # BLOCK PATH TRAVERSAL
    # ======================================
    if ($request_uri ~ "\.\./") { return 444; }
    if ($request_uri ~ "\.%2e%2e/") { return 444; }

    # ======================================
    # RATE-LIMITED BROWSE ENDPOINTS (bot targets)
    # ======================================
    
    # GLAM browse - heavy bot target
    location ~ ^/index\.php/glam/browse {
        limit_req zone=browse_limit burst=10 nodelay;
        limit_conn conn_limit 5;
        
        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
    
    # Information object browse
    location ~ ^/index\.php/informationobject/browse {
        limit_req zone=browse_limit burst=10 nodelay;
        limit_conn conn_limit 5;
        
        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
    
    # Search endpoint
    location ~ ^/index\.php/.*/search {
        limit_req zone=search_limit burst=15 nodelay;
        limit_conn conn_limit 10;
        
        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # ======================================
    # RiC EXPLORER & APIs
    # ======================================
    
    # RiC Explorer - serve static files (must be before PHP handlers)
    location ^~ /ric/ {
        alias /usr/share/nginx/atom/web/ric/;
        index index.html;
        try_files $uri $uri/ =404;
    }
    
    # RiC Semantic Search API proxy
    location ^~ /api/ric/ {
        proxy_pass http://127.0.0.1:5001/api/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_connect_timeout 30;
        proxy_read_timeout 30;
    }

    # RiC Provenance API
    location ^~ /api/provenance/ {
        proxy_pass http://127.0.0.1:5003/api/provenance/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # RiC Editor API
    location ^~ /api/editor/ {
        proxy_pass http://127.0.0.1:5002/api/editor/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # SPARQL Proxy for RiC Explorer
    location ^~ /sparql/ {
        proxy_pass http://127.0.0.1:3030/ric/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        add_header Access-Control-Allow-Origin "*" always;
        add_header Access-Control-Allow-Methods "GET, POST, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Content-Type" always;
    }

    # RiC Dashboard
    location ^~ /ric-dashboard/ {
        alias /usr/share/nginx/atom/web/ric-dashboard/;
        index index.php index.html;
        
        location ~ \.php$ {
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }

    # RiC Provenance UI
    location ^~ /ric-provenance/ {
        alias /usr/share/nginx/atom/web/ric-provenance/;
        index index.html;
        try_files $uri $uri/ =404;
    }

    # RiC Editor UI
    location ^~ /ric-editor/ {
        alias /usr/share/nginx/atom/web/ric-editor/;
        index index.html;
        try_files $uri $uri/ =404;
    }

    # ======================================
    # MEDIA API ROUTES (snippets, streaming, etc)
    # ======================================
    location ~ ^/media/ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/Media/public/routes.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ ^/media/(metadata|extract|waveform)/([0-9]+)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/media/(transcription|transcribe)/([0-9]+)(.*)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        fastcgi_read_timeout 600;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/media/search {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    # ======================================
    # PHP HANDLING
    # ======================================
    
    # PHP handling with PATH_INFO support for AtoM
    location ~ ^/(index|qubit_dev)\.php(/|$) {
        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_read_timeout 3600;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # ======================================
    # DIGITAL OBJECT VIEWERS
    # ======================================
    
    # Fix black viewer – allow inline JS on all digitalobject pages
    location ~* /index\.php/.*/digitalobject/ {
        add_header Content-Security-Policy "default-src 'self' data: blob:; 
                                            script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; 
                                            style-src 'self' 'unsafe-inline'; 
                                            img-src 'self' data: blob: https:;" always;
    }

    # ZoomPan viewer — must be routed by Symfony (NO ^~ !!!)
    location /zoompan/ {
        add_header Content-Security-Policy "default-src 'self' data: blob:;
                                            script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net;
                                            style-src 'self' 'unsafe-inline';
                                            img-src 'self' data: blob: https:;" always;
        try_files $uri $uri/ /index.php$args;
    }

    # 3D Viewer – Allow inline JS
    location = /3D-image.php {
        root /usr/share/nginx/atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/3D-image.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        add_header Content-Security-Policy "default-src 'self' data: blob:; 
                                            script-src 'self' 'unsafe-inline'; 
                                            img-src 'self' data: blob:; 
                                            style-src 'self' 'unsafe-inline';" always;
    }

    # 3D folder
    location ^~ /atom/3d/ {
        alias /usr/share/nginx/atom/3d/;
        try_files $uri =404;
    }

    # ======================================
    # STANDALONE REPORT EXTENSION
    # ======================================
    location ^~ /ext/reports/ {
        alias /usr/share/nginx/atom/atom-extensions/extensions/reports/public/;
        index index.php index.html;
        
        location ~ \.php$ {
            try_files $uri =404;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            fastcgi_param DOCUMENT_ROOT /usr/share/nginx/atom/atom-extensions/extensions/reports/public;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        }
    }

    # ======================================
    # IIIF (Cantaloupe)
    # ======================================

    # IIIF Viewer Framework Routes (must be before generic /iiif/ proxy)
    location ~ ^/iiif/manifest/(.+)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/iiif/collection/(.+)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/iiif/viewer/(.+)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location ~ ^/iiif/embed/(.+)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location ~ ^/iiif/annotations {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/iiif/ocr {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location ~ ^/iiif/text {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location ~ ^/iiif/search {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location /atom-framework/src/Extensions/IiifViewer/public/ {
        alias /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/;
        expires 7d;
    }

    # IIIF Cantaloupe proxy
    location /iiif/ {
        proxy_pass http://127.0.0.1:8182/iiif/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        add_header Access-Control-Allow-Origin "*" always;
    }

    location ^~ /atom/iiif/ {
        rewrite ^/atom/iiif/(.*)$ /iiif/$1 break;
        proxy_pass http://127.0.0.1:8182/iiif/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        add_header Access-Control-Allow-Origin "*" always;
        add_header Access-Control-Allow-Methods "GET, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept" always;
    }

    # IIIF Manifest endpoint
    location = /iiif-manifest.php {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        add_header Access-Control-Allow-Origin * always;
    }

    # ======================================
    # STATIC ASSETS
    # ======================================
    
    # Theme Plugin JS & CSS
    location ^~ /plugins/ahgThemeB5Plugin/js/dist/ {
        alias /usr/share/nginx/atom/dist/js/;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ^~ /plugins/ahgThemeB5Plugin/css/dist/ {
        alias /usr/share/nginx/atom/dist/css/;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ^~ /atom/dist/js/ {
        alias /usr/share/nginx/atom/dist/js/;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ^~ /atom/dist/css/ {
        alias /usr/share/nginx/atom/dist/css/;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Static content
    location ~* ^/(css|dist|js|images|plugins|vendor|ahgThemeB5Plugin)/.*\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|pdf)$ {
        root /usr/share/nginx/atom;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # ======================================
    # SECURITY - BLOCK DIRECT PHP ACCESS
    # ======================================
    location ~ \.php$ {
        deny all;
        return 404;
    }

    location ~ /\.(ht|git|svn) {
        deny all;
    }

    # ======================================
    # MAIN ROUTER (catch-all with rate limiting)
    # ======================================
    location / {
        limit_req zone=slow burst=40 nodelay;
        try_files $uri /index.php?$args;
    }
}
```

### Rate Limiting Configuration

Add to `/etc/nginx/conf.d/rate-limits.conf`:

```nginx
# Rate limiting zones
limit_req_zone $binary_remote_addr zone=browse_limit:10m rate=5r/s;
limit_req_zone $binary_remote_addr zone=search_limit:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=slow:10m rate=20r/s;
limit_conn_zone $binary_remote_addr zone=conn_limit:10m;

# Bot blocking map (add to http block or conf.d)
map $http_user_agent $bad_bot {
    default 0;
    ~*bot 0;          # Allow legitimate bots
    ~*crawl 0;
    ~*spider 0;
    ~*Googlebot 0;
    ~*Bingbot 0;
    ~*python-requests 1;
    ~*curl 1;
    ~*wget 1;
    ~*libwww 1;
    ~*Scrapy 1;
    ~*sqlmap 1;
    ~*nikto 1;
}

map $remote_addr $blocked_ip {
    default 0;
    # Add specific IPs to block:
    # "1.2.3.4" 1;
}
```

---

### HTTP Configuration (Development/Internal)

Simplified configuration for development or internal networks without SSL:

```nginx
##
# Access to Memory (AtoM) - HTTP Only (Development)
# Use for internal/development environments only
##

server {
    listen 80;
    listen [::]:80;
    server_name localhost atom.local;

    client_max_body_size 2G;

    root /usr/share/nginx/atom;
    index index.php index.html;

    access_log /var/log/nginx/atom_access.log;
    error_log  /var/log/nginx/atom_error.log;

    # ======================================
    # SECURITY HEADERS (basic)
    # ======================================
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    # ======================================
    # PHP HANDLING
    # ======================================
    
    location ~ ^/(index|qubit_dev)\.php(/|$) {
        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_read_timeout 3600;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # ======================================
    # RiC EXPLORER & APIs (if needed)
    # ======================================
    
    location ^~ /ric/ {
        alias /usr/share/nginx/atom/web/ric/;
        index index.html;
        try_files $uri $uri/ =404;
    }
    
    location ^~ /api/ric/ {
        proxy_pass http://127.0.0.1:5001/api/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    location ^~ /sparql/ {
        proxy_pass http://127.0.0.1:3030/ric/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        add_header Access-Control-Allow-Origin "*" always;
    }

    # ======================================
    # MEDIA API ROUTES
    # ======================================
    location ~ ^/media/ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/Media/public/routes.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ ^/media/(metadata|extract|waveform)/([0-9]+)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/media/(transcription|transcribe)/([0-9]+)(.*)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        fastcgi_read_timeout 600;
        add_header Access-Control-Allow-Origin * always;
    }

    # ======================================
    # DIGITAL OBJECT VIEWERS
    # ======================================
    
    location ~* /index\.php/.*/digitalobject/ {
        add_header Content-Security-Policy "default-src 'self' data: blob:; 
                                            script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; 
                                            style-src 'self' 'unsafe-inline'; 
                                            img-src 'self' data: blob: https:;" always;
    }

    location /zoompan/ {
        add_header Content-Security-Policy "default-src 'self' data: blob:;
                                            script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net;
                                            style-src 'self' 'unsafe-inline';
                                            img-src 'self' data: blob: https:;" always;
        try_files $uri $uri/ /index.php$args;
    }

    location = /3D-image.php {
        root /usr/share/nginx/atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/3D-image.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        add_header Content-Security-Policy "default-src 'self' data: blob:; 
                                            script-src 'self' 'unsafe-inline'; 
                                            img-src 'self' data: blob:; 
                                            style-src 'self' 'unsafe-inline';" always;
    }

    # ======================================
    # IIIF (Cantaloupe)
    # ======================================
    
    location ~ ^/iiif/manifest/(.+)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/iiif/collection/(.+)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/iiif/(viewer|embed)/(.+)$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location ~ ^/iiif/(annotations|ocr|text|search) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location /iiif/ {
        proxy_pass http://127.0.0.1:8182/iiif/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        add_header Access-Control-Allow-Origin "*" always;
    }

    location = /iiif-manifest.php {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        add_header Access-Control-Allow-Origin * always;
    }

    # ======================================
    # STATIC ASSETS
    # ======================================
    
    location ~* ^/(css|dist|js|images|plugins|vendor)/.*\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|pdf)$ {
        root /usr/share/nginx/atom;
        try_files $uri $uri/ =404;
        expires 7d;
        add_header Cache-Control "public";
    }

    # ======================================
    # SECURITY
    # ======================================
    location ~ \.php$ {
        deny all;
        return 404;
    }

    location ~ /\.(ht|git|svn) {
        deny all;
    }

    # ======================================
    # MAIN ROUTER
    # ======================================
    location / {
        try_files $uri /index.php?$args;
    }
}
```

---

## 📚 Documentation

- [Installation Guide](INSTALLATION.md)
- [Architecture Overview](docs/ARCHITECTURE.md)
- [Plugin Development](docs/PLUGIN_DEVELOPMENT.md)
- [RIC - Records In Context](docs/RIC_INTEGRATION_GUIDE.md)
- [CLI Reference](docs/CLI_REFERENCE.md)
- [API, Reporting and Data Export](docs/API_REPORTING_EXPORT.md)
- [Media Features Guide](docs/MEDIA_FEATURES.md)
- [Library Plugin Explained](docs/AtoM_AHG_Framework_Library_Architecture_Diagrams.md)

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

---

<div align="center">

Made with ❤️ for the archival community

[Report Bug](https://github.com/ArchiveHeritageGroup/atom-framework/issues) • [Request Feature](https://github.com/ArchiveHeritageGroup/atom-framework/issues)

</div>
