# AtoM AHG Framework - Extensions Catalog

[![Framework Version](https://img.shields.io/badge/Framework-1.0.0-blue.svg)](https://github.com/ArchiveHeritageGroup/atom-framework)
[![AtoM Version](https://img.shields.io/badge/AtoM-2.10-green.svg)](https://www.accesstomemory.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.3-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)

## Overview

The **AtoM AHG Framework** is a non-invasive modernization layer for [Access to Memory (AtoM)](https://www.accesstomemory.org/) 2.10 archival software, developed by [The Archive and Heritage Group](https://theahg.co.za).

**Key Principle: We enhance AtoM, we don't fork it.**

### What This Framework Provides

- **Laravel Query Builder** integration (Illuminate\Database) alongside Symfony 1.x
- **Database-driven plugin management** replacing hardcoded configurations
- **Bootstrap 5 theme** with modern responsive design
- **26+ extension plugins** for GLAM institutions
- **South African regulatory compliance** (POPIA, NARSSA, PAIA, GRAP 103)
- **100% backward compatibility** with core AtoM functionality

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    AtoM 2.10 BASE (Symfony 1.x)                 │
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
│  • ahgLibraryPlugin, ahgDisplayPlugin, etc. (optional)          │
└─────────────────────────────────────────────────────────────────┘
```

## Repository Structure

| Repository | Purpose |
|------------|---------|
| [atom-framework](https://github.com/ArchiveHeritageGroup/atom-framework) | Core Laravel foundation, CLI tools, services |
| [atom-ahg-plugins](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins) | All AHG plugins (theme + extensions) |
| [atom-extensions-catalog](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog) | Documentation and technical specifications |

## Quick Start

```bash
# 1. Clone framework
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
cd atom-framework && composer install

# 2. Clone plugins
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# 3. Run install
cd atom-framework
bash bin/install

# 4. Restart services
sudo service php8.3-fpm restart

# 5. Discover extensions
php bin/atom extension:discover
```

## CLI Commands

```bash
# Extension Management
php bin/atom extension:discover      # List all extensions
php bin/atom extension:enable <name> # Enable plugin
php bin/atom extension:disable <name># Disable plugin
php bin/atom extension:list          # Show status

# Framework Management
php bin/atom framework:install       # Initial setup
php bin/atom framework:update        # Pull updates
php bin/atom framework:version       # Show version
```

## Available Extensions

### Core (Locked - Cannot be disabled)

| Plugin | Description |
|--------|-------------|
| ahgThemeB5Plugin | Bootstrap 5 theme - UI foundation |
| ahgSecurityClearancePlugin | Security classification system |

### Sector Plugins

| Plugin | Description |
|--------|-------------|
| ahgLibraryPlugin | Library-specific features |
| ahgMuseumPlugin | Museum/Spectrum 5.0 functionality |
| ahgGalleryPlugin | Gallery/CCO features |
| ahgDAMPlugin | Digital Asset Management |

### Feature Extensions

| Plugin | Description |
|--------|-------------|
| ahgLandingPagePlugin | Drag-and-drop landing page builder |
| ahgResearchPlugin | Researcher portal and workspace |
| ahgDonorPlugin | Donor management and agreements |
| ahgConditionPlugin | Condition assessment tracking |
| ahgProvenancePlugin | Provenance tracking |
| ahgAccessRequestPlugin | Access request management |
| ahgAuditTrailPlugin | Comprehensive audit logging |
| ahgBackupPlugin | Backup and restore functionality |

### Compliance Extensions

| Plugin | Description |
|--------|-------------|
| ahgPrivacyPlugin | Base privacy compliance |
| ahgPOPIAModule | South African POPIA compliance |
| ahgGDPRModule | EU GDPR compliance |
| ahgGrapPlugin | GRAP 103 heritage asset compliance |
| ahgNARSSAPlugin | National Archives regulations |

## Technical Specifications

| Component | Technology |
|-----------|------------|
| Base Platform | AtoM 2.10 |
| PHP Version | 8.3 |
| Framework ORM | Laravel Query Builder (Illuminate\Database) |
| Core ORM | Propel (Symfony 1.x) - unchanged |
| Database | MySQL 8 |
| Search | Elasticsearch 7.10 |
| Web Server | nginx |
| Theme | Bootstrap 5 |

## Documentation

- [ARCHITECTURE.md](ARCHITECTURE.md) - Detailed technical architecture
- [MEETING-NOTES.md](MEETING-NOTES.md) - Technical discussion points

## Target Market

The AtoM AHG Framework is designed for **GLAM institutions** (Galleries, Libraries, Archives, Museums) with specific focus on:

- South African regulatory compliance
- Multi-sector support
- Enhanced security classification
- AI-powered features (NER, metadata extraction)
- International standards support (ISAD(G), ISAAR(CPF), RIC)

## License

Proprietary - The Archive and Heritage Group (Pty) Ltd

## Contact

- **Website:** [https://theahg.co.za](https://theahg.co.za)
- **Demo:** [https://psis.theahg.co.za](https://psis.theahg.co.za)

---

*Last Updated: January 2026*
