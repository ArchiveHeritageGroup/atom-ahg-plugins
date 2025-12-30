# AtoM AHG Plugins

Plugin collection for Access to Memory (AtoM) 2.10 by The Archive and Heritage Group.

## Requirements

- AtoM 2.10.x
- [atom-framework](https://github.com/ArchiveHeritageGroup/atom-framework) (required)
- PHP 8.1+
- MySQL 8.0+

## Installation
```bash
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# Run framework install (creates symlinks and enables plugins)
cd atom-framework
bash bin/install
```

## Plugins (21)

### Required (Locked)
| Plugin | Description |
|--------|-------------|
| ahgThemeB5Plugin | Bootstrap 5 theme - UI foundation |
| ahgSecurityClearancePlugin | Security classification system |

### Sector Plugins
| Plugin | Description |
|--------|-------------|
| ahgMuseumPlugin | Museum cataloging (CCO/Spectrum) |
| ahgLibraryPlugin | Library-specific features |
| ahgGalleryPlugin | Gallery/CCO features |
| ahgDAMPlugin | Digital Asset Management |

### Feature Plugins
| Plugin | Description |
|--------|-------------|
| ahg3DModelPlugin | 3D model viewer and thumbnails |
| ahgAccessRequestPlugin | Research access requests |
| ahgAuditTrailPlugin | Audit logging |
| ahgBackupPlugin | Backup management |
| ahgConditionPlugin | Condition assessment |
| ahgDisplayPlugin | Display mode switching |
| ahgDonorAgreementPlugin | Donor agreement management |
| ahgExtendedRightsPlugin | Extended rights management |
| ahgGrapPlugin | GRAP 103 compliance |
| ahgIiifCollectionPlugin | IIIF integration |
| ahgResearchPlugin | Researcher portal |
| ahgRicExplorerPlugin | RiC-O semantic explorer |
| ahgRightsPlugin | Rights statements |
| ahgSpectrumPlugin | Spectrum 5.0 procedures |

### Deprecated
| Plugin | Status |
|--------|--------|
| ahgDonorPlugin | Disabled - replaced by ahgDonorAgreementPlugin |

## Structure

Each plugin follows this structure:
```
ahg{Name}Plugin/
├── bin/                    # CLI scripts (if any)
├── config/
│   └── ahg{Name}PluginConfiguration.class.php
├── data/
│   └── install.sql         # Schema + seed data
├── docs/                   # Documentation
├── extension.json          # Extension manifest
├── lib/                    # PHP classes
├── modules/                # Symfony modules
├── templates/              # Shared templates
└── css/ js/                # Assets
```

## Plugin Management
```bash
# List all plugins
php bin/atom extension:discover

# Enable/disable
php bin/atom extension:enable ahgMuseumPlugin
php bin/atom extension:disable ahgMuseumPlugin
```

## Cron Jobs

Each plugin with cron requirements includes scripts in `bin/`:

| Plugin | Script | Schedule |
|--------|--------|----------|
| ahgRicExplorerPlugin | bin/ric_sync.sh | */5 * * * * |
| ahg3DModelPlugin | bin/cron-3d-thumbnails.sh | 0 * * * * |
| ahgDisplayPlugin | bin/sync-library-display.php | */1 * * * * |

## Related

- [atom-framework](https://github.com/ArchiveHeritageGroup/atom-framework) - Required framework
- [AtoM Documentation](https://www.accesstomemory.org/docs/)

## License

GPL-3.0 - See [LICENSE](LICENSE)

## Author

The Archive and Heritage Group
