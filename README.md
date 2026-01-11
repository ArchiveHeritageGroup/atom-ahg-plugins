# AtoM AHG Extensions

**Transform Access to Memory into a Complete GLAM Solution**

## What is AtoM + AtoM Extensions?

[Access to Memory (AtoM)](https://www.accesstomemory.org) is a powerful, open-source archival management system trusted by institutions worldwide. **AtoM Extensions** transforms it into a complete GLAM solution (Galleries, Libraries, Archives, Museums) with modern architecture, international compliance, and enterprise features.

### Complete Feature Comparison

| Capability | Base AtoM | + AtoM Extensions |
|------------|:---------:|:-----------------:|
| **Core Archival Functions** | | |
| Archival Description (ISAD(G), RAD, DACS) | ✅ | ✅ |
| Authority Records (ISAAR-CPF) | ✅ | ✅ |
| Hierarchical Arrangement | ✅ | ✅ |
| Multi-Repository Support | ✅ | ✅ |
| Multilingual (20+ languages) | ✅ | ✅ |
| Digital Objects | ✅ | ✅ Enhanced |
| Finding Aids (EAD, Dublin Core) | ✅ | ✅ |
| Elasticsearch Search & Browse | ✅ | ✅ |
| Access Control & Permissions | ✅ | ✅ Enhanced |
| Accessions Tracking | ✅ | ✅ |
| Deaccessions Tracking | ✅ | ✅ |
| Physical Storage Locations | ✅ | ✅ |
| Import/Export (CSV, EAD, EAC-CPF, SKOS) | ✅ | ✅ |
| OAI-PMH Harvesting | ✅ | ✅ |
| Theming | ✅ Basic | ✅ Bootstrap 5 |
| REST API | ✅ | ✅ |
| **Modern Architecture** | | |
| Bootstrap 5 Responsive UI | ❌ | ✅ |
| Laravel Query Builder Integration | ❌ | ✅ |
| Plugin Extension System | ❌ | ✅ |
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

## Documentation

### User Guides

| Guide | Description |
|-------|-------------|
| [Researcher Portal](docs/researcher-user-guide.md) | Booking, workspace, collections |
| [Spectrum 5.0](docs/spectrum-user-guide.md) | Museum cataloguing procedures |
| [Donor Agreements](docs/donor-agreement-user-guide.md) | Managing donor relationships |
| [Security & Compliance](docs/security-compliance-user-guide.md) | Classifications, GDPR, POPIA |
| [Data Export](docs/export-data-user-guide.md) | CSV, EAD, reports |
| [Data Migration](docs/data-migration-user-guide.md) | Import from other systems |
| [Access Requests](docs/access-requests-user-guide.md) | Request workflow |
| [Records in Contexts](docs/ric-user-guide.md) | Graph visualization |

---

## Repositories

| Repository | Description |
|------------|-------------|
| [atom-framework](https://github.com/ArchiveHeritageGroup/atom-framework) | Core Laravel integration |
| [atom-ahg-plugins](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins) | All extension plugins |
| [atom-extensions-catalog](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog) | Documentation & guides |

---

## Installation

### Prerequisites

- AtoM 2.10+ installed
- PHP 8.1+
- MySQL 8.0+

### Quick Install
```bash
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

cd atom-framework
composer install
bash bin/install
```

### Alternative Installation Methods

| Method | Command |
|--------|---------|
| Interactive | `./bin/ahg-installer.sh` |
| Quick | `./bin/ahg-installer.sh --quick` |
| Full Stack | `./bin/ahg-installer.sh --full-stack` |
| Wizard | `./bin/setup-wizard.sh` |
| DEB Package | `sudo apt install ./atom-ahg-framework_*.deb` |
| Ansible | `ansible-playbook -i inventory.yml atom-ahg-install.yml` |
| Docker | `docker-compose up -d` |

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

- **Issues**: [GitHub Issues](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues)
- **Email**: support@theahg.co.za
- **Website**: [theahg.co.za](https://theahg.co.za)

---

## License

GPL-3.0

## Author

**The Archive and Heritage Group (Pty) Ltd**

Empowering cultural heritage institutions with modern archival solutions.

© 2024-2026 All rights reserved.
