<div align="center">

# 🏛️ AtoM Extensions

**Extending Access to Memory for Modern Archives**

[![AtoM Version](https://img.shields.io/badge/AtoM-2.8.x--2.10.x-blue.svg)](https://www.accesstomemory.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.3-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--3.0-green.svg)](LICENSE)
[![Extensions](https://img.shields.io/badge/Extensions-19+-orange.svg)](#-available-extensions)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3.svg)](https://getbootstrap.com/)
[![Laravel](https://img.shields.io/badge/Laravel-Query%20Builder-red.svg)](https://laravel.com/)

**The most comprehensive extension suite for AtoM archival software**

[Installation](#-installation) • [Extensions](#-available-extensions) • [Compliance](#-compliance-support) • [CLI Reference](#-cli-reference) • [Documentation](#-documentation)

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
| International Privacy Compliance | ❌ | ✅ |
| Heritage Asset Accounting (10 standards) | ❌ | ✅ |
| Research Portal with Booking | ❌ | ✅ |
| Donor Agreement Management | ❌ | ✅ |
| Condition Assessment & Conservation | ❌ | ✅ |
| Comprehensive Audit Trail | ❌ | ✅ |
| Landing Page Builder | ❌ | ✅ |
| Unified GLAM/DAM Support | Partial | ✅ |
| Vendor/Supplier Management | ❌ | ✅ |
| AI Entity Extraction (Provenance AI) | ❌ | ✅ |
| Records in Contexts (RiC) Support | ❌ | ✅ |
| IIIF Deep Zoom Viewer | ❌ | ✅ |
| 3D Model Viewer | ❌ | ✅ |

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
│  LAYER 3: Provenance AI (Optional - API)                        │
│  └── AI-powered entity extraction for archivists                │
└─────────────────────────────────────────────────────────────────┘
```

### Repository Structure

| Repository | Purpose |
|------------|---------|
| [atom-framework](https://github.com/ArchiveHeritageGroup/atom-framework) | Core Laravel foundation, CLI tools, services |
| [atom-ahg-plugins](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins) | All AHG plugins (themes + extensions) |
| [atom-extensions-catalog](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog) | Documentation and user guides |

---

## ⚡ Installation

### Quick Start (Existing AtoM - Git Clone)
```bash
# Navigate to your AtoM installation
cd /usr/share/nginx/atom

# Clone framework and plugins
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# Install dependencies
cd atom-framework
composer install

# Run installer
bash bin/install

# Restart services
sudo systemctl restart php8.3-fpm

# Verify installation
php bin/atom framework:version
php bin/atom extension:discover
```

### All Installation Methods

| Method | Best For | Time | Description |
|--------|----------|------|-------------|
| **Git Clone** | Developers | ~5 min | Clone repos, run installer |
| **DEB Package** | Ubuntu/Debian | ~3 min | Download from [Releases](https://github.com/ArchiveHeritageGroup/atom-framework/releases) |
| **Self-Extracting** | Any Linux | ~3 min | Download `.run` from [Releases](https://github.com/ArchiveHeritageGroup/atom-framework/releases) |
| **Setup Wizard** | Guided Setup | ~10 min | Interactive TUI interface |
| **Ansible** | Multiple Servers | ~15 min | Automated remote deployment |
| **Docker** | Containers | ~10 min | Full stack containerized |
| **Full Stack** | Fresh Server | ~20 min | Installs AtoM + Framework + Everything |

📖 **[See INSTALLATION.md for complete instructions →](INSTALLATION.md)**

---

## 📦 Available Extensions

### Required (Core - Locked)

| Extension | Description |
|-----------|-------------|
| **ahgThemeB5Plugin** | Bootstrap 5 responsive theme with modern UI |
| **ahgSecurityClearancePlugin** | Security classification (Public to Top Secret) |

### GLAM Sector Plugins

| Extension | Description | Standards |
|-----------|-------------|-----------|
| **ahgLibraryPlugin** | Library cataloging | RDA, MARC21 |
| **ahgMuseumPlugin** | Museum cataloging | CCO, Spectrum 5.0 |
| **arGalleryPlugin** | Visual arts/gallery | CCO |
| **ahgDAMPlugin** | Digital Asset Management | Dublin Core |

### Research & Access

| Extension | Description |
|-----------|-------------|
| **ahgResearchPlugin** | Researcher portal, workspace, reading room booking |
| **ahgAccessRequestPlugin** | Access request workflow management |

### Compliance & Governance

| Extension | Description |
|-----------|-------------|
| **ahgPrivacyPlugin** | Multi-jurisdiction privacy compliance |
| **ahgHeritageAccountingPlugin** | Heritage asset accounting (10 standards) |
| **ahgExtendedRightsPlugin** | Rights statements & embargo management |

### Utilities

| Extension | Description |
|-----------|-------------|
| **ahgBackupPlugin** | Automated backup & restore |
| **ahgAuditTrailPlugin** | Comprehensive audit logging |
| **ahgDisplayPlugin** | Display profiles & viewing modes |
| **ahgDonorAgreementPlugin** | Donor agreements with reminders |
| **ahgVendorPlugin** | Vendor/supplier management |
| **ahgConditionPlugin** | Condition assessment & conservation |
| **ahgLandingPagePlugin** | Drag-and-drop landing page builder |

### AI & Advanced Features

| Extension | Description |
|-----------|-------------|
| **ahgNerPlugin** | Named Entity Recognition |
| **ahg3DModelPlugin** | 3D model viewer & thumbnails |
| **IiifViewerFramework** | IIIF deep zoom image viewer |
| **ahgRicExplorerPlugin** | Records in Contexts graph viewer |

---

## 🌍 Compliance Support

### Privacy Regulations

| Jurisdiction | Regulation | Status |
|--------------|------------|--------|
| 🇿🇦 South Africa | POPIA, PAIA, NARSSA | ✅ Full |
| 🇪🇺 European Union | GDPR | ✅ Full |
| 🇺🇸 California | CCPA | ✅ Full |
| 🇨🇦 Canada | PIPEDA | ✅ Full |
| 🇧🇷 Brazil | LGPD | ✅ Full |
| 🇳🇬 Nigeria | NDPA | ✅ Full |
| 🇰🇪 Kenya | DPA | ✅ Full |

### Heritage Accounting Standards

| Standard | Region | Coverage |
|----------|--------|----------|
| **GRAP 103** | South Africa | ✅ Full - Detailed |
| **IPSAS 17** | International | ✅ Full |
| **FRS 102** | United Kingdom | ✅ Full |
| **FASAB** | USA Federal | ✅ Full |
| **GASB 34** | USA State/Local | ✅ Full |
| **AASB 116** | Australia | ✅ Full |
| **PSAB/PS 3150** | Canada | ✅ Full |
| **NZ PBE IPSAS 17** | New Zealand | ✅ Full |
| **mGAAP** | Germany | ✅ Full |
| **Custom** | Any | ✅ Configurable |

---

## 🔧 CLI Reference
```bash
# Extension Management
php bin/atom extension:discover          # List available extensions
php bin/atom extension:enable <name>     # Enable an extension
php bin/atom extension:disable <name>    # Disable an extension
php bin/atom extension:install <name>    # Install extension

# Framework Management
php bin/atom framework:version           # Show version
php bin/atom update                      # Update from GitHub
php bin/atom migrate run                 # Run database migrations

# Maintenance
php bin/atom backup:create               # Create backup
php bin/atom backup:restore <file>       # Restore backup
php bin/atom cc                          # Clear cache
php bin/atom help                        # Show all commands
```

---

## 🤖 Provenance AI

AI-powered entity extraction for archivists. Extract and link:

- **People** - Names, roles, relationships
- **Places** - Locations, addresses, coordinates
- **Organizations** - Companies, institutions, agencies
- **Dates** - Events, periods, timelines

[Learn more about Provenance AI →](https://theahg.co.za/provenance-ai)

---

## 📖 Documentation

| Document | Description |
|----------|-------------|
| **[INSTALLATION.md](INSTALLATION.md)** | Complete installation guide (7 methods) |
| [docs/](docs/) | User guides for each plugin |
| [CHANGELOG.md](CHANGELOG.md) | Version history |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Contribution guidelines |

### User Guides

- [Spectrum 5.0 Guide](docs/spectrum-user-guide.md)
- [Donor Agreements Guide](docs/donor-agreement-user-guide.md)
- [Security & Compliance Guide](docs/security-compliance-user-guide.md)
- [Heritage Accounting Guide](docs/heritage-accounting-user-guide.md)
- [Privacy Compliance Guide](docs/privacy-compliance-user-guide.md)
- [Access Requests Guide](docs/access-requests-user-guide.md)
- [Records in Contexts Guide](docs/ric-user-guide.md)

---

## 🧪 Development

### Server Environment

| Server | IP | Purpose | Database |
|--------|----|---------|---------
| Dev | 192.168.0.112 | Development | archive |
| Test | 192.168.0.154 | Testing | atom |

### Version Release
```bash
./bin/release patch "Bug fixes"
./bin/release minor "New features"
./bin/release major "Breaking changes"
```

---

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Follow PHP CS Fixer standards
4. Test on development server
5. Commit changes
6. Push to branch
7. Open a Pull Request

---

## 📄 License

GPL-3.0 - see [LICENSE](LICENSE) for details.

---

## 🏢 About

Developed by **The Archive And Heritage Digital Commons Group**

| | |
|---|---|
| 🌐 Website | [theahg.co.za](https://theahg.co.za) |
| 📧 Email | [info@theahg.co.za](mailto:info@theahg.co.za) |
| 🐙 GitHub | [ArchiveHeritageGroup](https://github.com/ArchiveHeritageGroup) |

---

<div align="center">

**Made with ❤️ for the archival community**

[![Powered by AtoM](https://img.shields.io/badge/Powered%20by-AtoM-blue.svg)](https://www.accesstomemory.org/)
[![by TAHDCG](https://img.shields.io/badge/by-TAHDCG-green.svg)](https://theahg.co.za)

</div>
