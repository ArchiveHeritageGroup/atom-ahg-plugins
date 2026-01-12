# AtoM AHG Framework - Complete Installation Guide

<div align="center">

**Version 1.6.0** | **Last Updated: 2026-01-12** | **AtoM 2.8.x - 2.10.x**

[![Installation Methods](https://img.shields.io/badge/Methods-7-blue.svg)](#installation-methods)
[![Docker](https://img.shields.io/badge/Docker-Supported-2496ED.svg)](#method-6-docker)
[![Ansible](https://img.shields.io/badge/Ansible-Supported-EE0000.svg)](#method-5-ansible-playbook)

</div>

---

## Quick Start

| I have... | Recommended Method | Time |
|-----------|-------------------|------|
| Existing AtoM, comfortable with Git | [Method 1: Git Clone](#method-1-git-clone) | ~5 min |
| Existing AtoM, Ubuntu/Debian | [Method 2: DEB Package](#method-2-deb-package) | ~3 min |
| Existing AtoM, any Linux | [Method 3: Self-Extracting](#method-3-self-extracting-installer) | ~3 min |
| Existing AtoM, prefer guided setup | [Method 4: Setup Wizard](#method-4-setup-wizard) | ~10 min |
| Multiple servers to deploy | [Method 5: Ansible](#method-5-ansible-playbook) | ~15 min |
| Want containerized environment | [Method 6: Docker](#method-6-docker) | ~10 min |
| Fresh Ubuntu server (no AtoM yet) | [Method 7: Full Stack](#method-7-full-stack) | ~20 min |

---

## Prerequisites

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **OS** | Ubuntu 20.04 | Ubuntu 22.04 LTS |
| **PHP** | 8.1 | 8.3 |
| **MySQL** | 8.0 | 8.0+ |
| **Elasticsearch** | 5.x | 6.x / 8.x |
| **Composer** | 2.x | Latest |

### Verify Your System
```bash
php -v                    # Should show 8.1+
mysql --version           # Should show 8.0+
```

---

## Method 1: Git Clone

**Best for:** Developers, custom setups, easy updates

```bash
# Navigate to AtoM root
cd /usr/share/nginx/atom

# Clone framework
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
cd atom-framework
composer install

# Clone plugins
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# Run installer
cd atom-framework
bash bin/install

# Restart services
sudo systemctl restart php8.3-fpm

# Verify
php bin/atom framework:version
php bin/atom extension:discover
```

**Update:**
```bash
cd /usr/share/nginx/atom/atom-framework && git pull
cd /usr/share/nginx/atom/atom-ahg-plugins && git pull
php bin/atom update
```

---

## Method 2: DEB Package

**Best for:** Ubuntu/Debian servers

### Download
Go to **[GitHub Releases](https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest)** and download `atom-ahg-framework_X.X.X_all.deb`

Or via command line:
```bash
VERSION=$(curl -s https://api.github.com/repos/ArchiveHeritageGroup/atom-framework/releases/latest | grep tag_name | cut -d'"' -f4)
wget "https://github.com/ArchiveHeritageGroup/atom-framework/releases/download/${VERSION}/atom-ahg-framework_all.deb"
```

### Install
```bash
sudo apt install ./atom-ahg-framework_all.deb
```

### Uninstall
```bash
sudo apt remove atom-ahg-framework
```

---

## Method 3: Self-Extracting Installer

**Best for:** Any Linux, portable single file

### Download & Install
```bash
wget https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest/download/atom-ahg-framework.run
chmod +x atom-ahg-framework.run
sudo ./atom-ahg-framework.run
```

---

## Method 4: Setup Wizard

**Best for:** Guided interactive installation

```bash
cd /usr/share/nginx/atom/atom-framework
bash bin/setup-wizard.sh
```

**Features:**
- ✅ Welcome screen with version info
- ✅ License agreement (GPL-3.0)
- ✅ Automatic AtoM path detection
- ✅ Component selection
- ✅ Progress bar during installation
- ✅ Completion summary

---

## Method 5: Ansible Playbook

**Best for:** Multiple servers, DevOps

```bash
# Clone playbook
git clone https://github.com/ArchiveHeritageGroup/atom-ansible.git
cd atom-ansible

# Edit inventory
nano inventory.yml

# Run
ansible-playbook -i inventory.yml site.yml
```

---

## Method 6: Docker

**Best for:** Containers, development, isolation

```bash
# Clone
git clone https://github.com/ArchiveHeritageGroup/atom-docker.git
cd atom-docker

# Configure & Start
cp .env.example .env
docker-compose up -d
```

### Services
| Service | Port | Description |
|---------|------|-------------|
| atom | 80/443 | AtoM + AHG Framework |
| mysql | 3306 | MySQL 8.0 |
| elasticsearch | 9200 | Search engine |

---

## Method 7: Full Stack

**Best for:** Fresh Ubuntu server

Installs **everything**: nginx, PHP 8.3, MySQL 8, Elasticsearch, AtoM 2.10, and AHG Framework.

### One-Command Install
```bash
curl -sSL https://raw.githubusercontent.com/ArchiveHeritageGroup/atom-framework/main/bin/ahg-installer.sh | sudo bash -s -- --full-stack
```

---

## What Gets Installed

| Step | Action |
|------|--------|
| 1 | Database tables (`atom_plugin`, `atom_extension`, migrations) |
| 2 | Plugin symlinks (`atom-ahg-plugins/*` → `plugins/*`) |
| 3 | ProjectConfiguration (with `loadPluginsFromDatabase()`) |
| 4 | Theme assets (CSS/JS to `dist/`) |
| 5 | PHP migrations |
| 6 | Required plugins (ahgThemeB5Plugin, ahgSecurityClearancePlugin) |

---

## Symlinks Setup

The framework uses symlinks to connect plugin directories and scripts. These are typically created automatically by the installer, but may need to be recreated or fixed manually.

### Plugin Symlinks

Plugins from `atom-ahg-plugins` need to be symlinked to the main `plugins` directory:

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

### Individual Plugin Symlinks
```bash
# Example: Link specific plugins
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgThemeB5Plugin /usr/share/nginx/atom/plugins/ahgThemeB5Plugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgLibraryPlugin /usr/share/nginx/atom/plugins/ahgLibraryPlugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgMuseumPlugin /usr/share/nginx/atom/plugins/ahgMuseumPlugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgDAMPlugin /usr/share/nginx/atom/plugins/ahgDAMPlugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgDisplayPlugin /usr/share/nginx/atom/plugins/ahgDisplayPlugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgSecurityClearancePlugin /usr/share/nginx/atom/plugins/ahgSecurityClearancePlugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgExtendedRightsPlugin /usr/share/nginx/atom/plugins/ahgExtendedRightsPlugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgConditionPlugin /usr/share/nginx/atom/plugins/ahgConditionPlugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgResearchPlugin /usr/share/nginx/atom/plugins/ahgResearchPlugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgBackupPlugin /usr/share/nginx/atom/plugins/ahgBackupPlugin
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgAuditTrailPlugin /usr/share/nginx/atom/plugins/ahgAuditTrailPlugin
```

### Script Symlinks

Some framework scripts reference other plugin scripts. These need correct symlinks:

```bash
cd /usr/share/nginx/atom/atom-framework/bin

# Library/Display sync script
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgDisplayPlugin/bin/sync-library-display.php sync-library-display.php

# 3D thumbnail generation
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahg3DModelPlugin/bin/cron-3d-thumbnails.sh cron-3d-thumbnails.sh

# Verify
ls -la sync-library-display.php cron-3d-thumbnails.sh
```

### Fix Broken Symlinks

If you see errors about missing files, check for broken symlinks:

```bash
# Find broken symlinks
find /usr/share/nginx/atom -type l ! -exec test -e {} \; -print

# Remove broken symlinks
find /usr/share/nginx/atom -type l ! -exec test -e {} \; -delete

# Recreate symlinks
cd /usr/share/nginx/atom/atom-framework
bash bin/install --symlinks-only
```

### Path Differences Between Servers

**Important:** If you copied from a different server, symlinks may point to wrong paths:

| Source Server | Target Server | Issue |
|--------------|---------------|-------|
| `/usr/share/nginx/archive` | `/usr/share/nginx/atom` | Symlinks point to `archive` |

**Fix:**
```bash
cd /usr/share/nginx/atom/atom-framework/bin
rm -f sync-library-display.php cron-3d-thumbnails.sh
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahgDisplayPlugin/bin/sync-library-display.php sync-library-display.php
ln -sf /usr/share/nginx/atom/atom-ahg-plugins/ahg3DModelPlugin/bin/cron-3d-thumbnails.sh cron-3d-thumbnails.sh
```

---

## Cron Jobs Setup

The AHG Framework requires several cron jobs for background processing.

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
# List current cron jobs
sudo crontab -l

# Check cron logs
sudo tail -f /var/log/syslog | grep CRON
```

---

## Log Files

Cron jobs write to these log files:

| Log File | Purpose |
|----------|---------|
| `/var/log/atom-sync.log` | Library/Display sync |
| `/var/log/atom-covers.log` | Library cover downloads |
| `/var/log/atom-donor-reminders.log` | Donor agreement reminders |
| `/var/log/atom-3d-thumbnails.log` | 3D model thumbnail generation |

### Create Log Files with Correct Permissions
```bash
sudo touch /var/log/atom-sync.log /var/log/atom-covers.log /var/log/atom-donor-reminders.log /var/log/atom-3d-thumbnails.log
sudo chown www-data:www-data /var/log/atom-*.log
sudo chmod 644 /var/log/atom-*.log
```

### Monitor Logs
```bash
# Watch all AtoM logs
tail -f /var/log/atom-*.log

# Watch specific log
tail -f /var/log/atom-covers.log
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

# Shortcuts
php bin/atom cc                      # Clear cache
php bin/atom help                    # Show all commands
```

---

## Update Process

### Git Clone (Method 1)
```bash
cd /usr/share/nginx/atom/atom-framework
git pull origin main
cd /usr/share/nginx/atom/atom-ahg-plugins
git pull origin main
php bin/atom update
sudo systemctl restart php8.3-fpm
```

### Package Installs (Methods 2-3)
Download and install the latest package - it will update in place.

---

## Uninstallation

```bash
# DEB Package
sudo apt remove atom-ahg-framework

# Other methods
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

### Broken Symlinks
```bash
# Find and list broken symlinks
find /usr/share/nginx/atom -type l ! -exec test -e {} \; -print

# Re-run installer to fix
cd /usr/share/nginx/atom/atom-framework
bash bin/install
```

### Cron Jobs Not Running
```bash
# Check if cron service is running
sudo systemctl status cron

# Check cron logs for errors
sudo grep CRON /var/log/syslog | tail -20

# Test a cron script manually
php /usr/share/nginx/atom/atom-framework/bin/process-library-covers.php
```

---

## Post-Installation Checklist

After installation, verify:

- [ ] All plugin symlinks exist: `ls -la /usr/share/nginx/atom/plugins/ahg*`
- [ ] Script symlinks work: `ls -la /usr/share/nginx/atom/atom-framework/bin/*.php`
- [ ] Cron jobs added: `sudo crontab -l`
- [ ] Log files created: `ls -la /var/log/atom-*.log`
- [ ] Cache cleared: `rm -rf /usr/share/nginx/atom/cache/*`
- [ ] PHP-FPM restarted: `sudo systemctl restart php8.3-fpm`
- [ ] Framework version: `php bin/atom framework:version`
- [ ] Extensions discovered: `php bin/atom extension:discover`

---

## Quick Reference Card

```
╔══════════════════════════════════════════════════════════════════════════╗
║                    AtoM AHG Framework Quick Reference                    ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║  DOWNLOAD:  github.com/ArchiveHeritageGroup/atom-framework/releases      ║
║                                                                          ║
║  INSTALL (choose one):                                                   ║
║  ────────────────────────────────────────────────────────────────────    ║
║  Git:         git clone ... && bash bin/install                          ║
║  DEB:         sudo apt install ./atom-ahg-framework_all.deb              ║
║  Shell:       sudo ./atom-ahg-framework.run                              ║
║  Full Stack:  curl ... | sudo bash -s -- --full-stack                    ║
║                                                                          ║
║  DAILY USE:                                                              ║
║  ────────────────────────────────────────────────────────────────────    ║
║  php bin/atom extension:discover      # List extensions                  ║
║  php bin/atom extension:enable <n>    # Enable plugin                    ║
║  php bin/atom update                  # Pull latest                      ║
║                                                                          ║
║  CRON JOBS:                                                              ║
║  ────────────────────────────────────────────────────────────────────    ║
║  sudo crontab -e                      # Edit cron jobs                   ║
║  sudo crontab -l                      # List cron jobs                   ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
```

---

## Support

- **GitHub Issues:** [Report a bug](https://github.com/ArchiveHeritageGroup/atom-framework/issues)
- **Email:** info@theahg.co.za
- **Website:** [theahg.co.za](https://theahg.co.za)

---

<div align="center">

**[The Archive And Heritage Digital Commons Group](https://theahg.co.za)**

</div>
