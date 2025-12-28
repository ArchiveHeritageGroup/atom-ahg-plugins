<div align="center">

# 🏛️ AtoM Extensions Catalog

**A curated collection of extensions for [Access to Memory](https://www.accesstomemory.org/) 2.10**

[![AtoM Version](https://img.shields.io/badge/AtoM-2.10.x-blue.svg)](https://www.accesstomemory.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--3.0-green.svg)](LICENSE)
[![Extensions](https://img.shields.io/badge/Extensions-15-orange.svg)](#available-extensions)

[Quick Start](#quick-start) • [Extensions](#available-extensions) • [Installation](#installation) • [Documentation](#documentation)

</div>

---

## 📊 Overview

| Category | Count | Description |
|----------|-------|-------------|
| 🎨 Themes | 1 | Visual themes and UI customizations |
| 🏛️ GLAM/DAM | 4 | Sector-specific cataloging standards |
| 🔬 Research | 2 | Researcher portals and access tools |
| 🔒 Security | 2 | Security clearance and access control |
| 📋 Compliance | 3 | POPIA, GDPR, GRAP 103 compliance |
| 🛠️ Utilities | 3 | Tools for content management |

---

## ⚡ Quick Start

### Prerequisites

- AtoM 2.10.x installed and running
- PHP 8.1 or higher
- MySQL 8.0
- Composer

### 1. Install the Framework (Required)
```bash
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
cd atom-framework
composer install
php bin/atom framework:install
```

### 2. Browse & Install Extensions
```bash
# List available extensions
php bin/atom extension:discover

# Install an extension
php bin/atom extension:install arAHGThemeB5Plugin

# Enable the extension
php bin/atom extension:enable arAHGThemeB5Plugin
```

---

## 📦 Available Extensions

### 🎨 Themes

| Extension | Version | Status | Description |
|-----------|---------|--------|-------------|
| [arAHGThemeB5Plugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arAHGThemeB5Plugin) | 1.0.0 | ✅ Stable | Modern Bootstrap 5 theme with dashboard widgets |

---

### 🏛️ GLAM/DAM Sector Plugins

Specialized cataloging for Galleries, Libraries, Archives, Museums, and Digital Assets.

| Extension | Version | Status | Standards | Description |
|-----------|---------|--------|-----------|-------------|
| [sfMuseumPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/sfMuseumPlugin) | 1.0.0 | ✅ Stable | Spectrum 5.0 | Museum object cataloging, acquisitions, loans |
| [arLibraryPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arLibraryPlugin) | 1.0.0 | ✅ Stable | MARC, RDA | Bibliographic records, serials, holdings |
| [arGalleryPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arGalleryPlugin) | 1.0.0 | ✅ Stable | CCO | Visual arts cataloging, exhibitions |
| [arDAMPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arDAMPlugin) | 1.0.0 | ✅ Stable | - | Digital Asset Management, metadata extraction |

> ℹ️ **Note:** The DAM module in arAHGThemeB5Plugin requires arDAMPlugin to be installed.

#### Typical GLAM Configurations

| Institution Type | Recommended Plugins |
|------------------|---------------------|
| Archive | arAHGThemeB5Plugin |
| Museum | arAHGThemeB5Plugin + sfMuseumPlugin |
| Library | arAHGThemeB5Plugin + arLibraryPlugin |
| Gallery | arAHGThemeB5Plugin + arGalleryPlugin |
| Digital Repository | arAHGThemeB5Plugin + arDAMPlugin |
| Multi-sector | arAHGThemeB5Plugin + any combination |

---

### 🔬 Research & Access

| Extension | Version | Status | Description |
|-----------|---------|--------|-------------|
| [arResearchPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arResearchPlugin) | 1.0.0 | ✅ Stable | Researcher portal with workspace and requests |
| [arAccessRequestPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arAccessRequestPlugin) | 1.0.0 | 🚧 Dev | Access request workflow management |

---

### 🔒 Security & Permissions

| Extension | Version | Status | Description |
|-----------|---------|--------|-------------|
| [arSecurityClearancePlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arSecurityClearancePlugin) | 1.0.0 | ✅ Stable | Security classification and clearance levels |
| [arAuditLogPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arAuditLogPlugin) | 1.0.0 | 🚧 Dev | Comprehensive audit trail logging |

---

### 📋 Compliance & Governance

| Extension | Version | Status | Description |
|-----------|---------|--------|-------------|
| [arPrivacyPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arPrivacyPlugin) | 1.0.0 | 🚧 Dev | Base privacy compliance (DSAR, breach register) |
| [arPOPIAPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arPOPIAPlugin) | 1.0.0 | 🚧 Dev | South African POPIA compliance module |
| [arGRAP103Plugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arGRAP103Plugin) | 1.0.0 | 🚧 Dev | GRAP 103 heritage asset compliance |

---

### 🛠️ Content & Asset Management

| Extension | Version | Status | Description |
|-----------|---------|--------|-------------|
| [arDonorManagementPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arDonorManagementPlugin) | 1.0.0 | 🚧 Dev | Donor agreements and tracking |
| [arConditionAssessmentPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arConditionAssessmentPlugin) | 1.0.0 | 🚧 Dev | Physical condition assessment with annotations |
| [arLandingPageBuilderPlugin](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/tree/main/arLandingPageBuilderPlugin) | 1.0.0 | 🅿️ Parked | Drag-and-drop landing page builder |

---

## 🔧 Installation

See [INSTALLATION.md](INSTALLATION.md) for detailed instructions.

### Install Theme + GLAM Sector
```bash
# Install theme first
php bin/atom extension:install arAHGThemeB5Plugin
php bin/atom extension:enable arAHGThemeB5Plugin

# Add museum functionality
php bin/atom extension:install sfMuseumPlugin
php bin/atom extension:enable sfMuseumPlugin

# Add DAM functionality
php bin/atom extension:install arDAMPlugin
php bin/atom extension:enable arDAMPlugin
```

---

## 📋 Dependency Matrix
```
atom-framework (REQUIRED)
│
├── arAHGThemeB5Plugin
│   └── dam module ──requires──▶ arDAMPlugin
│
├── sfMuseumPlugin (standalone)
├── arLibraryPlugin (standalone)
├── arGalleryPlugin (standalone)
├── arDAMPlugin (standalone)
│
├── arSecurityClearancePlugin (standalone)
│
├── arResearchPlugin
│   └── (recommends arSecurityClearancePlugin)
│
├── arAccessRequestPlugin
│   └── requires ──▶ arSecurityClearancePlugin
│
└── arPrivacyPlugin
    ├── arPOPIAPlugin (optional module)
    └── arGDPRPlugin (optional module)
```

---

## 📋 Compatibility Matrix

| Extension | AtoM 2.10 | PHP 8.1 | PHP 8.2 | PHP 8.3 |
|-----------|-----------|---------|---------|---------|
| atom-framework | ✅ | ✅ | ✅ | ✅ |
| arAHGThemeB5Plugin | ✅ | ✅ | ✅ | ✅ |
| sfMuseumPlugin | ✅ | ✅ | ✅ | ✅ |
| arLibraryPlugin | ✅ | ✅ | ✅ | ✅ |
| arGalleryPlugin | ✅ | ✅ | ✅ | ✅ |
| arDAMPlugin | ✅ | ✅ | ✅ | ✅ |
| arSecurityClearancePlugin | ✅ | ✅ | ✅ | ✅ |
| arResearchPlugin | ✅ | ✅ | ✅ | ✅ |

---

## 🏢 About

Developed by **[The Archive and Heritage Group](https://theahg.co.za/)** for GLAM institutions (Galleries, Libraries, Archives, Museums).

### South African Compliance

These extensions support South African regulatory requirements:
- **POPIA** - Protection of Personal Information Act
- **PAIA** - Promotion of Access to Information Act
- **NARSSA** - National Archives and Records Service Act
- **GRAP 103** - Heritage Asset Accounting Standards

---

## 📄 License

GNU General Public License v3.0 - see [LICENSE](LICENSE) for details.

---

<div align="center">

**[⬆ Back to Top](#-atom-extensions-catalog)**

Made with ❤️ in South Africa 🇿🇦

</div>
