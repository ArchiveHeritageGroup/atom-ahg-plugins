# AtoM AHG Framework - Installation Guide

<div align="center">

**Version 2.8.2** | **Last Updated: 2026-02-18** | **AtoM 2.8.x - 2.10.x**

</div>

---

## Prerequisites

### Core (Required)

| Component | Minimum | Recommended | Purpose |
|-----------|---------|-------------|---------|
| **AtoM** | 2.8.0 | 2.10.x | Base archival software |
| **PHP** | 8.1 | 8.3 | Runtime (with extensions: mysql, xml, mbstring, curl, gd, zip, intl, xsl, opcache, apcu) |
| **MySQL** | 8.0 | 8.0+ | Database |
| **Elasticsearch** | 5.x | 6.x / 8.x | Search engine (or OpenSearch) |
| **Nginx** | 1.18+ | latest | Web server |
| **Gearman** | 1.1+ | latest | Job server for background tasks |
| **Composer** | 2.x | latest | PHP dependency manager |
| **Node.js** | 16.x | LTS (20.x+) | Asset building |
| **Git** | 2.x | latest | Version control |
| **Python** | 3.10 | 3.12 | AI features, scripting |

### Media Processing (Required)

| Component | Purpose |
|-----------|---------|
| **ImageMagick** | Image processing, thumbnail generation |
| **FFmpeg** | Audio/video processing, derivatives |
| **Ghostscript** | PDF rendering, conversion |
| **Poppler-utils** | PDF text extraction (pdftotext) |

### AI & Processing (Optional — needed by AI plugins)

| Component | Purpose | Used By |
|-----------|---------|---------|
| **Tesseract OCR** | Optical character recognition | ahgAIPlugin, ahgIngestPlugin |
| **GNU Aspell** | Spell checking | ahgAIPlugin |
| **PyMuPDF** (pip) | PDF redaction, manipulation | ahgPrivacyPlugin |
| **spaCy** (pip) | Named Entity Recognition | ahgAIPlugin |
| **Argos Translate** (pip) | Offline machine translation | ahgAIPlugin, ahgTranslationPlugin |
| **Pillow** (pip) | Image processing | ahgAIPlugin |
| **OpenCV** (pip) | Face detection, image analysis | ahgAIPlugin |

### Digital Preservation (Optional — needed by preservation plugins)

| Component | Purpose | Used By |
|-----------|---------|---------|
| **Siegfried** | Format identification (PRONOM) | ahgPreservationPlugin |
| **ClamAV** | Virus scanning | ahgPreservationPlugin, ahgIngestPlugin |
| **BagIt** (pip) | Archival packaging | ahgPreservationPlugin |

### 3D Processing (Optional — needed by 3D plugin)

| Component | Purpose |
|-----------|---------|
| **Blender** | 3D rendering, thumbnail generation |
| **MeshLab** | 3D mesh processing, conversion |

### Optional Services

| Component | Purpose |
|-----------|---------|
| **Redis** | Caching (recommended for production) |
| **Memcached** | Caching (alternative to Redis) |
| **Cantaloupe** | IIIF image tile server |
| **Ollama** | Local LLM for AI suggestions |

---

## Installation

```bash
# Navigate to AtoM root
cd /usr/share/nginx/atom

# 1. Clone repositories
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# 2. Install system dependencies (requires sudo)
sudo bash atom-framework/bin/install-deps          # Required only
sudo bash atom-framework/bin/install-deps --all    # Required + optional (AI, preservation, 3D)

# 3. Install PHP dependencies
cd atom-framework && composer install && cd ..

# 4. Check all dependencies are satisfied
bash atom-framework/bin/check-dependencies

# 5. Install framework (DB tables, symlinks, plugins)
bash atom-framework/bin/install

# 6. Restart and verify
sudo systemctl restart php8.3-fpm
php bin/atom framework:version
php bin/atom extension:discover
```

### Selective Dependency Install

Install only the optional dependency groups you need:

```bash
sudo bash atom-framework/bin/install-deps --ai         # AI/NLP: spaCy, PyMuPDF, Tesseract, etc.
sudo bash atom-framework/bin/install-deps --preserve   # Siegfried, ClamAV, BagIt
sudo bash atom-framework/bin/install-deps --3d         # Blender, MeshLab
sudo bash atom-framework/bin/install-deps --check      # Check only, don't install
```

---

## What Gets Installed

| Step | Action |
|------|--------|
| 1 | Database tables (`atom_plugin`, `atom_extension`, migrations) |
| 2 | Plugin symlinks (`atom-ahg-plugins/*` → `plugins/*`) |
| 3 | ProjectConfiguration (with `loadPluginsFromDatabase()`) |
| 4 | Theme assets (CSS/JS to `dist/`) |
| 5 | Plugin data files (taxonomies, settings, seed data) |
| 6 | Required plugins enabled (ahgThemeB5Plugin, ahgSecurityClearancePlugin, etc.) |

---

## Symlinks Setup

The framework uses symlinks to connect plugin directories. These are created automatically by `bin/install`, but may need manual fixing.

### Plugin Symlinks

```bash
cd /usr/share/nginx/atom

# Create symlinks for all AHG plugins
for plugin in atom-ahg-plugins/ahg*Plugin; do
    name=$(basename "$plugin")
    ln -sf "$(pwd)/$plugin" "plugins/$name"
done

# Verify symlinks
ls -la plugins/ahg*
```

### Fix Broken Symlinks

```bash
# Find broken symlinks
find /usr/share/nginx/atom -type l ! -exec test -e {} \; -print

# Remove broken symlinks
find /usr/share/nginx/atom -type l ! -exec test -e {} \; -delete

# Recreate via installer
cd /usr/share/nginx/atom/atom-framework
bash bin/install
```

### Path Differences Between Servers

If you copied from a different server, symlinks may point to wrong paths:

| Source Server | Target Server | Issue |
|--------------|---------------|-------|
| `/usr/share/nginx/archive` | `/usr/share/nginx/atom` | Symlinks point to `archive` |

**Fix:** Re-run `bash bin/install` on the target server to recreate symlinks with correct paths.

---

## Cron Jobs Setup

### Edit Crontab
```bash
sudo crontab -e
```

### Required Cron Jobs

Add the following entries (adjust `/usr/share/nginx/atom` to your AtoM path):

```cron
# ============================================================================
# AtoM Core Cron Jobs
# ============================================================================

# Search cleanup - removes stale search data
0 3 * * * cd /usr/share/nginx/atom && php symfony search:cleanup

# Saved search notifications
0 6 * * * cd /usr/share/nginx/atom && php symfony search:notify-saved --frequency=daily
0 7 * * 1 cd /usr/share/nginx/atom && php symfony search:notify-saved --frequency=weekly
0 8 1 * * cd /usr/share/nginx/atom && php symfony search:notify-saved --frequency=monthly

# ============================================================================
# AHG Framework Cron Jobs
# ============================================================================

# RIC (Rights in Context) queue processing - every minute
* * * * * cd /usr/share/nginx/atom && php symfony ric:queue-process --limit=50

# RIC integrity check - weekly on Sunday at 2am
0 2 * * 0 cd /usr/share/nginx/atom && php symfony ric:integrity-check --fix

# RIC cleanup - monthly on the 1st at 3am
0 3 1 * * cd /usr/share/nginx/atom && php symfony ric:cleanup

# RIC sync (if using remote integration) - every 5 minutes
*/5 * * * * /usr/share/nginx/atom/ric_sync.sh --cron

# Donor agreement reminders - daily at 8am
0 8 * * * cd /usr/share/nginx/atom && php symfony agreement:process-reminders

# Library/Display sync - every minute
*/1 * * * * php /usr/share/nginx/atom/atom-framework/bin/sync-library-display.php >> /var/log/atom-sync.log 2>&1

# 3D thumbnail generation - hourly
0 * * * * /usr/share/nginx/atom/atom-framework/bin/cron-3d-thumbnails.sh

# Library cover download processing - every 5 minutes
*/5 * * * * php /usr/share/nginx/atom/atom-framework/bin/process-library-covers.php >> /var/log/atom-covers.log 2>&1

# ============================================================================
# Backup Cron Jobs (Optional but Recommended)
# ============================================================================

# Daily backup with 30-day retention
0 2 * * * /usr/share/nginx/atom/atom-framework/scripts/run-backup.sh --retention=30

# Weekly full backup with 90-day retention (Sunday at 3am)
0 3 * * 0 /usr/share/nginx/atom/atom-framework/scripts/run-backup.sh --retention=90

# Cleanup old backups - daily at 4am
0 4 * * * /usr/share/nginx/atom/atom-framework/scripts/cleanup-backups.sh
```

### Verify Cron Jobs
```bash
sudo crontab -l
sudo grep CRON /var/log/syslog | tail -20
```

---

## Log Files

| Log File | Purpose |
|----------|---------|
| `/var/log/atom-sync.log` | Library/Display sync |
| `/var/log/atom-covers.log` | Library cover downloads |
| `/var/log/atom-donor-reminders.log` | Donor agreement reminders |
| `/var/log/atom-3d-thumbnails.log` | 3D model thumbnail generation |

### Create Log Files
```bash
sudo touch /var/log/atom-sync.log /var/log/atom-covers.log /var/log/atom-donor-reminders.log /var/log/atom-3d-thumbnails.log
sudo chown www-data:www-data /var/log/atom-*.log
sudo chmod 644 /var/log/atom-*.log
```

---

## CLI Reference

```bash
# Extensions
php bin/atom extension:discover      # List available
php bin/atom extension:enable <n>    # Enable
php bin/atom extension:disable <n>   # Disable

# Framework
php bin/atom framework:version       # Show version
php bin/atom update                  # Update from GitHub
php bin/atom migrate run             # Run migrations

# Dependencies
bash atom-framework/bin/check-dependencies   # Verify all dependencies
sudo bash atom-framework/bin/install-deps    # Install dependencies

# Shortcuts
php bin/atom cc                      # Clear cache
php bin/atom help                    # Show all commands
```

---

## Update Process

```bash
cd /usr/share/nginx/atom/atom-framework
git pull origin main
cd /usr/share/nginx/atom/atom-ahg-plugins
git pull origin main
php bin/atom update
sudo systemctl restart php8.3-fpm
```

---

## Uninstallation

```bash
sudo /usr/share/nginx/atom/atom-framework/bin/uninstall.sh
```

---

## Troubleshooting

### Plugins Not Loading
```bash
grep "loadPluginsFromDatabase" /usr/share/nginx/atom/config/ProjectConfiguration.class.php
# If missing, re-run: bash bin/install
```

### Permission Errors
```bash
sudo chown -R www-data:www-data /usr/share/nginx/atom
sudo chmod -R 755 /usr/share/nginx/atom
```

### Clear All Caches
```bash
rm -rf /usr/share/nginx/atom/cache/*
sudo systemctl restart php8.3-fpm
```

### Missing Dependencies
```bash
# Check what's missing
bash atom-framework/bin/check-dependencies

# Install missing
sudo bash atom-framework/bin/install-deps --all
```

### Cron Jobs Not Running
```bash
sudo systemctl status cron
sudo grep CRON /var/log/syslog | tail -20

# Test a cron script manually
php /usr/share/nginx/atom/atom-framework/bin/process-library-covers.php
```

---

## Post-Installation Checklist

- [ ] Dependencies verified: `bash atom-framework/bin/check-dependencies`
- [ ] All plugin symlinks exist: `ls -la /usr/share/nginx/atom/plugins/ahg*`
- [ ] Cron jobs added: `sudo crontab -l`
- [ ] Log files created: `ls -la /var/log/atom-*.log`
- [ ] Cache cleared: `rm -rf /usr/share/nginx/atom/cache/*`
- [ ] PHP-FPM restarted: `sudo systemctl restart php8.3-fpm`
- [ ] Framework version: `php bin/atom framework:version`
- [ ] Extensions discovered: `php bin/atom extension:discover`

---

## Support

- **GitHub Issues:** [Report a bug](https://github.com/ArchiveHeritageGroup/atom-framework/issues)
- **Email:** info@theahg.co.za
- **Website:** [theahg.co.za](https://theahg.co.za)

---

<div align="center">

**[The Archive And Heritage Digital Commons Group](https://theahg.co.za)**

</div>
