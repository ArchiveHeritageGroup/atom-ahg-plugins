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

[Installation](#-installation) • [Extensions](#-available-extensions) • [Why AtoM Extensions?](#-why-atom-extensions) • [CLI Reference](#-cli-reference) • [Documentation](#-documentation)

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
| Heritage Asset Accounting | ❌ | ✅ |
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

### Prerequisites

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| AtoM | 2.8.0 | 2.10.x |
| PHP | 8.1 | 8.3 |
| MySQL | 8.0 | 8.0+ |
| Elasticsearch | 5.x | 6.x |
| Composer | 2.x | Latest |

### Installation Methods

| Method | Best For | Time |
|--------|----------|------|
| [Quick Install](#quick-install) | Existing AtoM | ~5 min |
| [Master Installer](#master-installer) | All scenarios | ~5-15 min |
| [Docker](#docker) | Testing/Development | ~10 min |
| [Ansible](#ansible-playbook) | Multiple servers | ~15 min |
| [Full Stack](#full-stack) | New server | ~30 min |

### Quick Install

```bash
# 1. Clone repositories
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# 2. Install dependencies
cd atom-framework
composer install

# 3. Run installer
bash bin/install

# 4. Restart services
sudo systemctl restart php8.3-fpm

# 5. Discover extensions
php bin/atom extension:discover
```

### Master Installer

The master installer provides an interactive menu with all options:

```bash
cd /usr/share/nginx/atom/atom-framework
./bin/ahg-installer.sh
```

**Menu Options:**
1. Full Install (Interactive) - Step-by-step prompts
2. Quick Install (Auto) - Automated with defaults
3. Setup Wizard (TUI) - Dialog-based interface
4. Build .run Package - Self-extracting installer
5. Build .deb Package - Debian/Ubuntu package

### Docker

```bash
cd /usr/share/nginx/atom/atom-framework/docker
cp .env.example .env
# Edit .env with your settings
docker-compose up -d
```

### Ansible Playbook

```bash
cd /usr/share/nginx/atom/atom-framework/ansible
# Edit inventory.yml with your servers
ansible-playbook -i inventory.yml atom-ahg-install.yml
```

### Full Stack (New Server)

Installs AtoM + Framework + Plugins on a fresh Ubuntu server:

```bash
./bin/ahg-installer.sh --full-stack
```

📖 **See [INSTALLATION.md](INSTALLATION.md) for detailed instructions**

---

## 📦 Available Extensions

### 🔒 Required (Core - Locked)

| Extension | Description | Version |
|-----------|-------------|---------|
| **ahgThemeB5Plugin** | Bootstrap 5 responsive theme with modern UI | 1.1.x |
| **ahgSecurityClearancePlugin** | Security classification (Public to Top Secret) | 1.0.x |

### 🏛️ GLAM Sector Plugins

| Extension | Description | Standards | Status |
|-----------|-------------|-----------|--------|
| **ahgLibraryPlugin** | Library cataloging | RDA, MARC21 | ✅ Stable |
| **ahgMuseumPlugin** | Museum cataloging | CCO, Spectrum 5.0 | ✅ Stable |
| **arGalleryPlugin** | Visual arts/gallery | CCO | ✅ Stable |
| **ahgDAMPlugin** | Digital Asset Management | Dublin Core | ✅ Stable |

### 🔬 Research & Access

| Extension | Description | Status |
|-----------|-------------|--------|
| **ahgResearchPlugin** | Researcher portal, workspace, reading room | ✅ Stable |
| **ahgAccessRequestPlugin** | Access request workflow | ✅ Stable |

### 📋 Compliance & Governance

| Extension | Description | Jurisdictions/Standards |
|-----------|-------------|------------------------|
| **ahgPrivacyPlugin** | Multi-jurisdiction privacy | GDPR, POPIA, CCPA, PIPEDA, LGPD |
| **ahgHeritageAccountingPlugin** | Heritage asset accounting | GRAP 103, IPSAS 17, FRS 102, FASAB, + 6 more |
| **ahgExtendedRightsPlugin** | Rights & embargo management | RightsStatements.org, Creative Commons |

### 🛠️ Utility Plugins

| Extension | Description | Status |
|-----------|-------------|--------|
| **ahgBackupPlugin** | Automated backup & restore | ✅ Stable |
| **ahgAuditTrailPlugin** | Comprehensive audit logging | ✅ Stable |
| **ahgDisplayPlugin** | Display profiles & viewing modes | ✅ Stable |
| **ahgDonorAgreementPlugin** | Donor agreements with reminders | ✅ Stable |
| **ahgVendorPlugin** | Vendor/supplier management | ✅ Stable |
| **ahgConditionPlugin** | Condition assessment & conservation | ✅ Stable |
| **ahgLandingPagePlugin** | Drag-and-drop landing page builder | ✅ Stable |

### 🤖 AI & Advanced Features

| Extension | Description | Status |
|-----------|-------------|--------|
| **ahgNerPlugin** | Named Entity Recognition | ✅ Stable |
| **ahg3DModelPlugin** | 3D model viewer & thumbnails | ✅ Stable |
| **IiifViewerFramework** | IIIF deep zoom image viewer | ✅ Stable |
| **ahgRicExplorerPlugin** | Records in Contexts graph viewer | ✅ Stable |

---

## 🔧 CLI Reference

### Extension Management

```bash
# List available extensions
php bin/atom extension:discover

# Enable/disable extensions
php bin/atom extension:enable ahgResearchPlugin
php bin/atom extension:disable ahgResearchPlugin

# Install extension
php bin/atom extension:install ahgMuseumPlugin
```

### Framework Management

```bash
# Check version
php bin/atom framework:version

# Update framework
php bin/atom update

# Clear cache
php bin/atom cc
```

### Backup & Maintenance

```bash
# Create backup
php bin/atom backup:create

# Restore backup
php bin/atom backup:restore <backup-file>

# Database migrations
php bin/atom migrate
```

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

### Heritage Accounting Standards

| Standard | Region | Status |
|----------|--------|--------|
| GRAP 103 | South Africa | ✅ Full |
| IPSAS 17 | International | ✅ Full |
| FRS 102 | UK | ✅ Full |
| FASAB | USA | ✅ Full |
| AASB 116 | Australia | ✅ Full |
| PSAB | Canada | ✅ Full |
| + 4 more | Various | ✅ Full |

---

## 📖 Documentation

| Document | Description |
|----------|-------------|
| [INSTALLATION.md](INSTALLATION.md) | Complete installation guide |
| [QUICKSTART.md](QUICKSTART.md) | 5-minute getting started |
| [docs/](docs/) | User guides for each plugin |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Contribution guidelines |
| [CHANGELOG.md](CHANGELOG.md) | Version history |

### User Guides

- [Spectrum 5.0 Guide](docs/spectrum-user-guide.md)
- [Donor Agreements Guide](docs/donor-agreement-user-guide.md)
- [Security & Compliance Guide](docs/security-compliance-user-guide.md)
- [Export & Data Guide](docs/export-data-user-guide.md)
- [Data Migration Guide](docs/data-migration-user-guide.md)
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
cd /usr/share/nginx/atom/atom-framework
./bin/release patch "Bug fixes"
./bin/release minor "New features"
./bin/release major "Breaking changes"
```

### Build Packages

```bash
# Build self-extracting installer
./bin/build-installer.sh

# Build Debian package
./bin/build-deb.sh
```

---

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Follow PHP CS Fixer standards
4. Test on development server
5. Commit changes (`git commit -m 'Add amazing feature'`)
6. Push to branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

---

## 📄 License

GPL-3.0 License - see [LICENSE](LICENSE) for details.

---

## 🏢 About

**AtoM Extensions** is developed by **The Archive And Heritage Digital Commons Group**.

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