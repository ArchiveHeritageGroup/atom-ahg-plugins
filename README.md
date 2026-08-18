# AtoM AHG Extensions

**Transform Access to Memory into a Complete GLAM Solution**

> ### ➜ [Download the plugins](docs/plugins/README.md)
>
> Nine plugins for AtoM 2.9 and 2.10, each installable on its own, with what
> they do and what they look like. That page carries the current version of
> each; the [releases page](https://github.com/ArchiveHeritageGroup/atom-ahg-plugins/releases)
> holds the full history.

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
| Museums (Collections Procedures) | ❌ | ✅ |
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
| **ahgMuseumPlugin** | Museum/Collections Procedures support |
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

Verified on 18 August 2026 by installing from a **bare Ubuntu 24.04 server** to a working
site, unattended. Every command below ran on that machine and produced the result stated.
Nothing here is written from intention.

### Base AtoM is not modified

No file under `apps/`, `lib/`, `vendor/` or `config/` is touched, and
`config/ProjectConfiguration.class.php` stays exactly as upstream ships it. The plugins
load through AtoM's own `plugins` setting - the same list Admin > Plugins writes.

### Prerequisites

- AtoM 2.10 installed per the [official instructions](https://www.accesstomemory.org/en/docs/2.10/admin-manual/installation/ubuntu/)
- MySQL 8.0+, Elasticsearch 7.10, Composer 2.x
- **PHP 8.3 from Ubuntu's own repositories.** Do not add the `ondrej/php` PPA: it offers
  8.4 and 8.5 and makes one of them the default. AtoM 2.10 does not run on 8.5.
- **`php8.3-gd`** - required by the framework and **absent from AtoM's documented package
  list**. Without it `composer install` fails with *"the requested PHP extension gd is
  missing"* and the install stops dead. This is the single most common cause of a failed
  AHG install.

  ```bash
  apt install php8.3-gd
  ```

- `php8.3-imagick` is **optional**. It is needed for digital-object derivatives, not for
  installation - the verified install ran without it.

The plugins carry no Composer dependencies of their own; PHP libraries live in
`atom-framework/composer.json`. JavaScript and CSS libraries are vendored and committed,
so there is no npm install at deploy time and nothing is fetched from a CDN at runtime.

### Install the framework

```bash
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

cd atom-framework
composer install --no-dev --optimize-autoloader
bin/build-runtime-plugin
cd ..
```

`ahgRuntimePlugin` is **generated** by `bin/build-runtime-plugin`, not cloned. It is not in
the plugins repository and a `git pull` will never produce it.

If composer falls back to `Cloning ... from cache` because GitHub is rate limiting (HTTP
429), the result carries a `.git` directory per package and is roughly ten times the size.
Either re-run later, or strip them: `find vendor -type d -name .git -prune -exec rm -rf {} +`

### Install a plugin

```bash
ln -sfn /usr/share/nginx/atom/atom-ahg-plugins/<Plugin> plugins/<Plugin>
chown -h www-data:www-data plugins/<Plugin>

php atom-framework/bin/install-plugin-schema.php --plugin=<Plugin> \
    --database=<db> --user=<user> --password=<pw>
```

Read the line it prints. `verified all N declared tables exist` is success. A run that
applies nothing exits 2 rather than claiming success, a plugin whose declared dependency
is not installed is **refused** with the dependency named (`--force` overrides), and a
plugin that legitimately owns no tables says `no schema - nothing to install`.

On a server whose MySQL has no TCP listener, add
`--socket=/var/run/mysqld/mysqld.sock`.

**Never run `mysql < install.sql`.** The client stops at the first error and abandons every
statement after it, leaving a half-built schema and saying nothing.

### Enable

In **Admin > Plugins**, in dependency order: `ahgRuntimePlugin`, then `ahgCorePlugin`, then
the rest. Afterwards:

```bash
php symfony cc
systemctl reload php8.3-fpm
```

The theme is enabled under **Admin > Themes**, not Plugins. AtoM deliberately excludes any
plugin whose `$summary` contains the word "theme" from the plugin list, so
`ahgThemeB5Plugin` will never appear there.

Every plugin configuration class must declare both `$summary` and `$version`. AtoM's plugin
admin renders `$plugin::$version`, and in PHP 8 reading an undeclared static property is a
fatal error - it kills that page part way down, taking the save button with it, with a 200
response and nothing in any log.

### Post-install

> **AHG symfony tasks do not exist on a stock AtoM.** Stock `ProjectConfiguration` enables a
> hardcoded plugin list and never reads the `plugins` setting, so symfony discovers no
> plugin tasks. `php symfony display:auto-detect` and `php symfony ahg:refresh-facet-cache`
> produce no output at all. Use `php atom-framework/bin/atom` for plugin management
> (`discover`, `install`, `enable`, `disable`, `update`, `migrate`).

Base AtoM's own tasks work normally and are worth running after enabling plugins:

```bash
sudo -u www-data php symfony propel:build-nested-set
sudo -u www-data php symfony propel:generate-slugs
sudo -u www-data php symfony search:populate
sudo systemctl restart php8.3-fpm nginx
```

### If the AtoM root is under /usr/share/nginx

Some php-fpm packaging sets `ProtectSystem=full`, which mounts `/usr` read-only for the
worker; the site then cannot write its cache or logs and every page returns 500 with an
empty body. Stock Ubuntu 24.04 ships `ProtectSystem=no` and is unaffected. Where it does
apply, grant the paths and **prefix each with `-`**:

```ini
# /etc/systemd/system/php8.3-fpm.service.d/atom-storage.conf
[Service]
ReadWritePaths=-/usr/share/nginx/atom/log
ReadWritePaths=-/usr/share/nginx/atom/cache
ReadWritePaths=-/usr/share/nginx/atom/uploads
```

Without the `-`, a path that does not exist yet makes systemd refuse to start php-fpm at
all (`226/NAMESPACE`) and the web server stays down.

### Verified standalone set

Eighteen plugins install onto an empty database, each with exactly one `install.sql` and a
manifest that matches it: ahgRuntime, ahgCore, ahgContact, ahgSecurityClearance, ahgDisplay,
ahgSettings, ahgUiOverrides, ahgAuditTrail, ahgBackup, ahgThemeB5, ahgProvenance,
ahgCondition, ahgCart, ahgRequestToPublish, ahgResearch, ahgUserRegistration,
ahgAccessRequest, ahgMetadataExtraction.

Proven by dropping all 253 tables and reinstalling from zero, then running the end-to-end
suites: 74 checks passed, 4 failed, every failure traced to a plugin deliberately outside
this set.

Plugins outside it may still carry schema that the installer does not create. They are not
yet certified for standalone installation.

> **Note:** `ahgMultiTenantPlugin` is disabled by default and should stay off unless you
> configure tenant to domain mappings - it routes requests by hostname and returns
> "Tenant Not Found" for any host it does not recognise.

---

## Version Compatibility

| Version | AtoM | PHP |
|---------|------|-----|
| 2.x | 2.10+ | 8.3 |
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
