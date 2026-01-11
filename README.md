# AtoM AHG Extensions

**Transform Access to Memory into a Complete GLAM Solution**

## What is AtoM?

[Access to Memory (AtoM)](https://www.accesstomemory.org) is a powerful, open-source archival management system trusted by institutions worldwide. AtoM provides:

### Core AtoM Capabilities

| Feature | Description |
|---------|-------------|
| **Archival Description** | Full ISAD(G), RAD, DACS standards support |
| **Authority Records** | ISAAR(CPF) compliant entity management |
| **Hierarchical Arrangement** | Unlimited fonds/series/file/item levels |
| **Multi-Repository** | Host multiple archives in one instance |
| **Multilingual** | 20+ languages with i18n support |
| **Digital Objects** | Upload and link files to descriptions |
| **Finding Aids** | Generate EAD, Dublin Core exports |
| **Search & Browse** | Elasticsearch-powered discovery |
| **Access Control** | User groups and permissions |
| **Accessions** | Track incoming materials |
| **Deaccessions** | Document disposals |
| **Physical Storage** | Location tracking |
| **Import/Export** | CSV, EAD, EAC-CPF, SKOS |
| **OAI-PMH Harvesting** | Share metadata with aggregators |
| **Theming** | Customizable appearance |
| **API Access** | REST API for integrations |

**AtoM is excellent archival software.** It handles the core archival workflow beautifully.

---

## What AtoM Extensions Adds

AtoM Extensions transforms AtoM into a **complete GLAM solution** (Galleries, Libraries, Archives, Museums) with modern architecture, international compliance, and enterprise features.

### Feature Comparison

| Capability | Base AtoM | + AtoM Extensions |
|------------|:---------:|:-----------------:|
| Core Archival Functions | ✅ Full | ✅ Enhanced |
| Modern Bootstrap 5 UI | ❌ | ✅ |
| Laravel Query Builder Integration | ❌ | ✅ |
| **Security & Compliance** | | |
| Security Classification System | ❌ | ✅ |
| GDPR Compliance (EU) | ❌ | ✅ |
| POPIA Compliance (South Africa) | ❌ | ✅ |
| CCPA Compliance (California) | ❌ | ✅ |
| PIPEDA Compliance (Canada) | ❌ | ✅ |
| Comprehensive Audit Trail | ❌ | ✅ |
| **GLAM Sector Support** | | |
| Archives | ✅ | ✅ Enhanced |
| Libraries | Partial | ✅ Full |
| Museums (Spectrum 5.0) | ❌ | ✅ |
| Galleries (CCO) | ❌ | ✅ |
| Digital Asset Management | ❌ | ✅ |
| **Heritage & Finance** | | |
| GRAP 103 Heritage Accounting | ❌ | ✅ |
| Asset Valuation & Depreciation | ❌ | ✅ |
| Insurance Management | ❌ | ✅ |
| **Research & Access** | | |
| Research Portal | ❌ | ✅ |
| Reading Room Booking | ❌ | ✅ |
| Access Request Workflow | ❌ | ✅ |
| Embargo Management | ❌ | ✅ |
| **Collection Management** | | |
| Donor Agreement Tracking | ❌ | ✅ |
| Condition Assessment | ❌ | ✅ |
| Conservation Tracking | ❌ | ✅ |
| Provenance Research | ❌ | ✅ |
| Vendor/Supplier Management | ❌ | ✅ |
| **Advanced Features** | | |
| Landing Page Builder | ❌ | ✅ |
| Display Profile System | ❌ | ✅ |
| IIIF Image Viewer | ❌ | ✅ |
| Records in Contexts (RiC) | ❌ | ✅ |
| AI Entity Extraction | ❌ | ✅ |
| Automated Backups | ❌ | ✅ |

### Why Both Together?

**AtoM** = Rock-solid archival foundation trusted by national archives, universities, and cultural institutions.

**AtoM Extensions** = Modern enhancements for institutions needing:
- Multi-sector GLAM support
- International regulatory compliance
- Enterprise security features
- Advanced collection management
- Public engagement tools

---

## Available Plugins

### Required (Core)

| Plugin | Description |
|--------|-------------|
| **ahgThemeB5Plugin** | Bootstrap 5 theme - Modern UI foundation |
| **ahgSecurityClearancePlugin** | Security classification system |

### GLAM Sector Plugins

| Plugin | Description |
|--------|-------------|
| **ahgLibraryPlugin** | Library catalog features |
| **ahgMuseumPlugin** | Museum/Spectrum 5.0 support |
| **ahgGalleryPlugin** | Gallery/CCO features |

### Feature Plugins

| Plugin | Description |
|--------|-------------|
| **ahgResearchPlugin** | Researcher portal & reading room booking |
| **ahgBackupPlugin** | Automated backup & restore |
| **ahgAuditTrailPlugin** | Compliance audit logging |
| **ahgDisplayPlugin** | Display profiles & layouts |
| **ahgAccessRequestPlugin** | Access request workflow |
| **ahgDonorPlugin** | Donor agreement management |
| **ahgConditionPlugin** | Condition assessment |
| **ahgProvenancePlugin** | Provenance tracking |
| **ahgVendorPlugin** | Vendor/supplier management |

### Compliance Plugins

| Plugin | Description |
|--------|-------------|
| **ahgPOPIAPlugin** | South African POPIA compliance |
| **ahgGDPRPlugin** | EU GDPR compliance |
| **ahgGRAPPlugin** | GRAP 103 heritage asset accounting |

---

## Installation

### Prerequisites

- AtoM 2.10+ installed
- PHP 8.1+
- MySQL 8.0+
- [atom-framework](https://github.com/ArchiveHeritageGroup/atom-framework)

### Quick Install
```bash
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

cd atom-framework
composer install
bash bin/install
```

### Enable Plugins
```bash
php bin/atom extension:discover
php bin/atom extension:enable ahgLibraryPlugin
```

---

## Version Compatibility

| Version | AtoM | PHP |
|---------|------|-----|
| 2.x | 2.10+ | 8.1+ |
| 1.x | 2.8-2.9 | 7.4+ |

---

## Support

- **Documentation**: [User Guides](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog)
- **Issues**: [GitHub Issues](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/issues)
- **Email**: support@theahg.co.za
- **Website**: [theahg.co.za](https://theahg.co.za)

---

## License

GPL-3.0 - See [LICENSE](LICENSE) file.

## Author

**The Archive and Heritage Group (Pty) Ltd**

Empowering cultural heritage institutions with modern archival solutions.

© 2024-2026 All rights reserved.
