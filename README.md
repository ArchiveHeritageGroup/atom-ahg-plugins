<div align="center">

# 🏛️ AtoM Extensions

**Extending Access to Memory for Modern Archives**

[![AtoM Version](https://img.shields.io/badge/AtoM-2.10.x-blue.svg)](https://www.accesstomemory.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.3-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--3.0-green.svg)](LICENSE)
[![Extensions](https://img.shields.io/badge/Extensions-19-orange.svg)](#-available-extensions)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3.svg)](https://getbootstrap.com/)
[![Laravel](https://img.shields.io/badge/Laravel-Query%20Builder-red.svg)](https://laravel.com/)

**The most comprehensive extension suite for AtoM archival software**

[Quick Start](#-quick-start) • [Extensions](#-available-extensions) • [Why AtoM Extensions?](#-why-atom-extensions) • [Documentation](docs/) • [Provenance AI](#-provenance-ai)

---

**Built by [The Archive And Heritage Digital Commons Group](https://theahg.co.za)**

</div>

---

## 🎯 Why AtoM Extensions?

AtoM Extensions transforms Access to Memory into a complete **GLAM solution** (Galleries, Libraries, Archives, Museums) with modern architecture, international compliance, and features not available in base AtoM.

| Capability | Base AtoM | AtoM Extensions |
|------------|:---------:|:---------------:|
| Modern Bootstrap 5 UI | ❌ | ✅ |
| Laravel Query Builder Integration | ❌ | ✅ |
| Security Classification System | ❌ | ✅ |
| International Compliance (GDPR, POPIA, CCPA, PIPEDA) | ❌ | ✅ |
| Heritage Asset Accounting | ❌ | ✅ |
| Research Portal with Booking | ❌ | ✅ |
| Donor Agreement Management | ❌ | ✅ |
| Condition Assessment & Conservation | ❌ | ✅ |
| Comprehensive Audit Trail | ❌ | ✅ |
| Landing Page Builder | ❌ | ✅ |
| Unified GLAM/DAM Support | Partial | ✅ |
| Vendor/Supplier Management | ❌ | ✅ |
| AI Entity Extraction (Provenance AI) | ❌ | ✅ |

---

## 📊 Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     AtoM 2.10 BASE (Symfony 1.x)                │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 1: atom-framework (REQUIRED)                             │
│  ├── Laravel Query Builder (Illuminate\Database)                │
│  ├── Extension Manager CLI                                      │
│  ├── Base Repository/Service Classes                            │
│  └── Helper Utilities                                           │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 2: atom-ahg-plugins                                      │
│  ├── ahgThemeB5Plugin (Required - Locked)                       │
│  ├── ahgSecurityClearancePlugin (Required - Locked)             │
│  └── [Optional Plugins...]                                      │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 3: Provenance AI (Optional - Paid API)                   │
│  └── AI-powered entity extraction for archivists                │
└─────────────────────────────────────────────────────────────────┘
```

---

## ⚡ Quick Start

### Prerequisites

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| AtoM | 2.10.0 | 2.10.1+ |
| PHP | 8.1 | 8.3 |
| MySQL | 8.0 | 8.0+ |
| Elasticsearch | 5.x | 6.x |
| Composer | 2.x | Latest |

### Installation

```bash
# Clone the framework
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
cd atom-framework && composer install

# Clone plugins
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# Run installer
cd atom-framework
bash bin/install

# Restart services
sudo systemctl restart php8.3-fpm nginx

# Discover available extensions
php bin/atom extension:discover
```

📖 **[Full Installation Guide](docs/INSTALLATION.md)**

---

## 📦 Available Extensions

### 🔒 Required (Installed by Default)

| Extension | Description | Version | Locked |
|-----------|-------------|---------|:------:|
| [ahgThemeB5Plugin](docs/extensions/ahgThemeB5Plugin.md) | Modern Bootstrap 5 responsive theme | 1.1.x | ✅ |
| [ahgSecurityClearancePlugin](docs/extensions/ahgSecurityClearancePlugin.md) | Multi-level security classification system | 1.0.x | ✅ |
| [ahgDisplayPlugin](docs/extensions/ahgDisplayPlugin.md) | Display profiles & modes | 1.0.x | ❌ |

### 🏛️ GLAM Sector Plugins

| Extension | Description | Version | Status |
|-----------|-------------|---------|--------|
| [ahgMuseumPlugin](docs/extensions/ahgMuseumPlugin.md) | Museum cataloging with CCO support | 1.0.x | ✅ Stable |
| [ahgSpectrumPlugin](docs/extensions/ahgSpectrumPlugin.md) | Spectrum 5.0 procedures | 1.0.x | ✅ Stable |
| [ahgLibraryPlugin](docs/extensions/ahgLibraryPlugin.md) | Library cataloging with RDA/MARC | 1.0.x | ✅ Stable |
| [ahgGalleryPlugin](docs/extensions/ahgGalleryPlugin.md) | Gallery/visual arts cataloging | 1.0.x | ✅ Stable |
| [ahgDAMPlugin](docs/extensions/ahgDAMPlugin.md) | Digital Asset Management | 1.0.x | ✅ Stable |

### 🔬 Research & Access

| Extension | Description | Version | Status |
|-----------|-------------|---------|--------|
| [ahgResearchPlugin](docs/extensions/ahgResearchPlugin.md) | Researcher portal & workspace | 1.0.x | ✅ Stable |
| [ahgAccessRequestPlugin](docs/extensions/ahgAccessRequestPlugin.md) | Access request management | 1.0.x | ✅ Stable |

### 📋 Compliance & Governance

| Extension | Description | Jurisdictions | Status |
|-----------|-------------|---------------|--------|
| [ahgPrivacyPlugin](docs/extensions/ahgPrivacyPlugin.md) | Multi-jurisdiction privacy compliance | GDPR, POPIA, CCPA, PIPEDA, LGPD | ✅ Stable |
| [ahgHeritageAccountingPlugin](docs/extensions/ahgHeritageAccountingPlugin.md) | Heritage asset accounting (10 standards) | International | ✅ Stable |
| [ahgExtendedRightsPlugin](docs/extensions/ahgExtendedRightsPlugin.md) | Advanced rights & embargo management | International | ✅ Stable |

### 🛠️ Utility Plugins

| Extension | Description | Version | Status |
|-----------|-------------|---------|--------|
| [ahgBackupPlugin](docs/extensions/ahgBackupPlugin.md) | Automated backup management | 1.0.x | ✅ Stable |
| [ahgAuditTrailPlugin](docs/extensions/ahgAuditTrailPlugin.md) | Comprehensive audit logging | 1.0.x | ✅ Stable |
| [ahgDonorAgreementPlugin](docs/extensions/ahgDonorAgreementPlugin.md) | Donor agreements & reminders | 1.0.x | ✅ Stable |
| [ahgVendorPlugin](docs/extensions/ahgVendorPlugin.md) | Vendor/supplier management | 1.0.x | ✅ Stable |
| [ahgConditionPlugin](docs/extensions/ahgConditionPlugin.md) | Condition assessment & conservation | 1.0.x | ✅ Stable |
| [ahgLandingPagePlugin](docs/extensions/ahgLandingPagePlugin.md) | Drag-and-drop landing page builder | 1.0.x | ✅ Stable |

### 🤖 AI & Automation

| Extension | Description | Version | Status |
|-----------|-------------|---------|--------|
| [ahgNerPlugin](docs/extensions/ahgNerPlugin.md) | Named Entity Recognition integration | 1.0.x | ✅ Stable |

> **Note:** ahgNerPlugin requires a [Provenance AI](https://provenance.theahg.co.za) subscription for full functionality.

---

## 🌍 International Compliance

AtoM Extensions helps archives comply with privacy regulations worldwide:

| Region | Regulation | Module |
|--------|------------|--------|
| 🇪🇺 European Union | GDPR | ahgPrivacyPlugin |
| 🇬🇧 United Kingdom | UK GDPR | ahgPrivacyPlugin |
| 🇿🇦 South Africa | POPIA | ahgPrivacyPlugin |
| 🇺🇸 California | CCPA | ahgPrivacyPlugin |
| 🇨🇦 Canada | PIPEDA | ahgPrivacyPlugin |
| 🇧🇷 Brazil | LGPD | ahgPrivacyPlugin |
| 🇳🇬 Nigeria | NDPR | ahgPrivacyPlugin |
| 🇰🇪 Kenya | DPA | ahgPrivacyPlugin |

---

## 🤖 Provenance AI

**AI-powered entity extraction for archivists**

Automatically extract Names, Places, Organizations, and People from your archival descriptions to create access points.

```
┌─────────────────────────────────────────────────────┐
│  "John Smith worked at the University of Cape Town │
│   from 1950 to 1975..."                            │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
                  [Provenance AI]
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│  Extracted Entities:                               │
│  • Person: John Smith                              │
│  • Organization: University of Cape Town           │
│  • Date Range: 1950-1975                           │
└─────────────────────────────────────────────────────┘
```

**Features:**
- Named Entity Recognition (NER) optimized for archival content
- VIAF/Wikidata linking for authority control
- Batch processing support
- REST API integration

[Learn more about Provenance AI →](https://provenance.theahg.co.za)

---

## 🔧 CLI Commands

```bash
# Extension management
php bin/atom extension:discover          # List all available extensions
php bin/atom extension:enable <name>     # Enable a plugin
php bin/atom extension:disable <name>    # Disable a plugin

# Framework management
php bin/atom framework:version           # Show framework version
php bin/atom framework:update            # Update framework

# Database operations
php bin/atom backup:create               # Create database backup
php bin/atom backup:restore <file>       # Restore from backup
```

📖 **[Full CLI Reference](docs/CLI_REFERENCE.md)**

---

## 📖 Documentation

| Document | Description |
|----------|-------------|
| [Installation Guide](docs/INSTALLATION.md) | Complete setup instructions |
| [Architecture](docs/ARCHITECTURE.md) | Technical architecture overview |
| [CLI Reference](docs/CLI_REFERENCE.md) | Command line interface documentation |
| [Plugin Development](docs/PLUGIN_DEVELOPMENT.md) | Creating custom plugins |
| [API Reference](docs/API_REFERENCE.md) | REST API documentation |

---

## 🤝 Contributing

We welcome contributions from the archival community!

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please read our [Contributing Guide](CONTRIBUTING.md) for details.

---

## 📄 License

This project is licensed under the GPL-3.0 License - see the [LICENSE](LICENSE) file for details.

---

## 🏢 About

**AtoM Extensions** is developed and maintained by **The Archive And Heritage Digital Commons Group**, specialists in archival software solutions for GLAM institutions worldwide.

- 🌐 **Website:** [theahg.co.za](https://theahg.co.za)
- 📧 **Email:** info@theahg.co.za
- 🐙 **GitHub:** [ArchiveHeritageGroup](https://github.com/ArchiveHeritageGroup)

### Acknowledgments

- [Artefactual Systems](https://www.artefactual.com/) for Access to Memory
- The global AtoM community
- All contributors and users

---

<div align="center">

**Made with ❤️ for the archival community**

[![AtoM](https://img.shields.io/badge/Powered%20by-AtoM-blue.svg)](https://www.accesstomemory.org/)
[![The Archive And Heritage Digital Commons Group](https://img.shields.io/badge/by-TAHDCG-green.svg)](https://theahg.co.za)

</div>
