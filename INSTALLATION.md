# AtoM AHG Framework - Complete Installation Guide

<div align="center">

**Version 1.4.0** | **Last Updated: 2026-01-11** | **AtoM 2.8.x - 2.10.x**

[![Installation Methods](https://img.shields.io/badge/Methods-7-blue.svg)](#installation-methods)
[![Docker](https://img.shields.io/badge/Docker-Supported-2496ED.svg)](#method-6-docker)
[![Ansible](https://img.shields.io/badge/Ansible-Supported-EE0000.svg)](#method-5-ansible-playbook)

</div>

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Prerequisites](#prerequisites)
3. [Installation Methods](#installation-methods)
   - [Method 1: Manual (Git Clone)](#method-1-manual-git-clone)
   - [Method 2: DEB Package](#method-2-deb-package)
   - [Method 3: Self-Extracting Installer](#method-3-self-extracting-installer)
   - [Method 4: Setup Wizard (TUI)](#method-4-setup-wizard-tui)
   - [Method 5: Ansible Playbook](#method-5-ansible-playbook)
   - [Method 6: Docker](#method-6-docker)
   - [Method 7: Full Stack (New Server)](#method-7-full-stack-new-server)
4. [What Gets Installed](#what-gets-installed)
5. [Database Tables](#database-tables)
6. [Plugin System](#plugin-system)
7. [CLI Reference](#cli-reference)
8. [Update Process](#update-process)
9. [Uninstallation](#uninstallation)
10. [Modified Core Files](#modified-core-files)
11. [Troubleshooting](#troubleshooting)
12. [Quick Reference Card](#quick-reference-card)

---

## Quick Start

| I have... | Recommended Method | Time |
|-----------|-------------------|------|
| Existing AtoM, comfortable with Git | [Manual (Git Clone)](#method-1-manual-git-clone) | ~5 min |
| Existing AtoM, Ubuntu/Debian | [DEB Package](#method-2-deb-package) | ~3 min |
| Existing AtoM, any Linux | [Self-Extracting Installer](#method-3-self-extracting-installer) | ~3 min |
| Existing AtoM, prefer guided setup | [Setup Wizard](#method-4-setup-wizard-tui) | ~10 min |
| Multiple servers to deploy | [Ansible](#method-5-ansible-playbook) | ~15 min |
| Want containerized environment | [Docker](#method-6-docker) | ~10 min |
| Fresh Ubuntu server (no AtoM yet) | [Full Stack](#method-7-full-stack-new-server) | ~20 min |

---

## Prerequisites

### System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **OS** | Ubuntu 20.04 | Ubuntu 22.04 LTS |
| **PHP** | 8.1 | 8.3 |
| **MySQL** | 8.0 | 8.0+ |
| **Elasticsearch** | 5.x | 6.x / 8.x |
| **Composer** | 2.x | Latest |
| **nginx** | 1.18+ | Latest |

### For Methods 1-5: Existing AtoM Required

- AtoM 2.8.x, 2.9.x, or 2.10.x installed and working
- Web interface accessible
- SSH/terminal access to server
- sudo privileges

### Verify Your System
```bash
php -v                    # Should show 8.1+
mysql --version           # Should show 8.0+
cd /usr/share/nginx/atom && php symfony --version  # AtoM check
```

---

## Installation Methods

---

### Method 1: Manual (Git Clone)

**Best for:** Developers, those comfortable with Git, custom setups

**Time:** ~5 minutes

**Requirements:** Git, Composer
```bash
# Step 1: Navigate to AtoM root
cd /usr/share/nginx/atom

# Step 2: Clone framework
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
cd atom-framework
composer install

# Step 3: Clone plugins
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git

# Step 4: Run installer
cd atom-framework
bash bin/install

# Step 5: Restart services
sudo service php8.3-fpm restart

# Step 6: Verify
php bin/atom framework:version
php bin/atom extension:discover
```

**Pros:** Always latest version, easy updates with `git pull`  
**Cons:** Requires Git knowledge

---

### Method 2: DEB Package

**Best for:** Ubuntu/Debian servers, system administrators

**Time:** ~3 minutes

**Requirements:** Ubuntu 20.04+ or Debian 11+

#### Download

Download the latest `.deb` package from GitHub Releases:

👉 **[Download atom-ahg-framework_latest_all.deb](https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest)**

Or via command line:
```bash
# Get latest release URL
wget https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest/download/atom-ahg-framework_all.deb
```

#### Install
```bash
# Install with apt (recommended - handles dependencies)
sudo apt install ./atom-ahg-framework_all.deb

# Restart services
sudo service php8.3-fpm restart
```

#### Uninstall
```bash
sudo apt remove atom-ahg-framework
# This restores original AtoM files from backup
```

**Pros:** Native package management, automatic dependency handling, clean uninstall  
**Cons:** Ubuntu/Debian only

---

### Method 3: Self-Extracting Installer

**Best for:** Any Linux distribution, portable single-file installer

**Time:** ~3 minutes

**Requirements:** Any Linux with bash

#### Download

Download the latest `.run` installer from GitHub Releases:

👉 **[Download atom-ahg-framework-latest.run](https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest)**

Or via command line:
```bash
wget https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest/download/atom-ahg-framework.run
chmod +x atom-ahg-framework.run
```

#### Install
```bash
# Run installer (will prompt for AtoM path)
sudo ./atom-ahg-framework.run

# Or specify path
sudo ./atom-ahg-framework.run --atom-root=/usr/share/nginx/atom
```

#### Extract Only (No Install)
```bash
./atom-ahg-framework.run --extract-only --target /tmp/ahg-extract
```

#### Uninstall
```bash
sudo /usr/share/nginx/atom/atom-framework/bin/uninstall.sh
```

**Pros:** Works on any Linux, single portable file, no Git required  
**Cons:** Manual updates

---

### Method 4: Setup Wizard (TUI)

**Best for:** Interactive installation, users who prefer guided setup

**Time:** ~10 minutes

**Requirements:** Terminal access, `dialog` or `whiptail` (auto-installed if missing)

#### Download & Run
```bash
# Download wizard
wget https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest/download/setup-wizard.sh
chmod +x setup-wizard.sh

# Run wizard
sudo ./setup-wizard.sh
```

#### Features

- ✅ Welcome screen with version info
- ✅ License agreement (GPL-3.0)
- ✅ Automatic AtoM path detection
- ✅ Warning about modified core files
- ✅ Component selection (checkboxes)
- ✅ Database configuration
- ✅ Progress bar during installation
- ✅ Completion summary with next steps

**Pros:** User-friendly, guided process, no command-line expertise needed  
**Cons:** Requires terminal interaction

---

### Method 5: Ansible Playbook

**Best for:** Multiple servers, DevOps, automated deployment

**Time:** ~15 minutes (first run)

**Requirements:** Ansible 2.9+ on control machine

#### Download Playbook
```bash
# Download Ansible files
wget https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest/download/ansible-playbook.tar.gz
tar -xzf ansible-playbook.tar.gz
cd ansible
```

#### Configure Inventory

Edit `inventory.yml`:
```yaml
all:
  children:
    production:
      hosts:
        archive-server:
          ansible_host: your.server.com
          ansible_user: deploy
          ansible_become: yes
          atom_root: /usr/share/nginx/atom
          
    staging:
      hosts:
        staging-server:
          ansible_host: staging.server.com
          ansible_user: deploy
          atom_root: /usr/share/nginx/atom
```

#### Run Playbook
```bash
# Install on single server
ansible-playbook -i inventory.yml atom-ahg-install.yml --limit archive-server

# Install on all production servers
ansible-playbook -i inventory.yml atom-ahg-install.yml --limit production

# Dry run (check mode)
ansible-playbook -i inventory.yml atom-ahg-install.yml --check

# Uninstall
ansible-playbook -i inventory.yml atom-ahg-install.yml --tags "uninstall"
```

**Pros:** Automation, multiple servers, idempotent, infrastructure as code  
**Cons:** Requires Ansible knowledge

---

### Method 6: Docker

**Best for:** Containerized environments, development, isolation

**Time:** ~10 minutes

**Requirements:** Docker, Docker Compose

#### Quick Start
```bash
# Download Docker files
wget https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest/download/docker-compose.tar.gz
tar -xzf docker-compose.tar.gz
cd docker

# Configure
cp .env.example .env
nano .env  # Edit your settings

# Start
docker-compose up -d
```

#### Services Included

| Service | Port | Description |
|---------|------|-------------|
| atom | 80/443 | AtoM + AHG Framework |
| mysql | 3306 | MySQL 8.0 database |
| elasticsearch | 9200 | Search engine |
| memcached | 11211 | Cache |

#### Optional Services
```bash
# With Apache Jena Fuseki (Records in Contexts)
docker-compose --profile ric up -d

# With Cantaloupe (IIIF Image Server)
docker-compose --profile iiif up -d

# All services
docker-compose --profile all up -d
```

#### Commands
```bash
docker-compose logs -f atom      # View logs
docker-compose exec atom bash    # Enter container
docker-compose down              # Stop
docker-compose down -v           # Stop + remove data
```

**Pros:** Isolation, reproducible, includes all dependencies  
**Cons:** Docker overhead, volume management

---

### Method 7: Full Stack (New Server)

**Best for:** Fresh Ubuntu server with nothing installed

**Time:** ~20-30 minutes

**Requirements:** Fresh Ubuntu 22.04 server with sudo access

This installs **everything**: system packages, nginx, PHP 8.3, MySQL 8, Elasticsearch, AtoM 2.10, and the AHG Framework.

#### One-Command Install
```bash
wget -qO- https://raw.githubusercontent.com/ArchiveHeritageGroup/atom-framework/main/bin/ahg-installer.sh | sudo bash -s -- --full-stack
```

#### Or Download and Run
```bash
wget https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest/download/ahg-installer.sh
chmod +x ahg-installer.sh
sudo ./ahg-installer.sh --full-stack
```

#### What Gets Installed

1. **System packages:** nginx, PHP 8.3, MySQL 8, required extensions
2. **Elasticsearch:** Version 8.x
3. **AtoM 2.10:** From Artefactual's official repository
4. **AHG Framework:** Core framework + CLI tools
5. **AHG Plugins:** Theme + all sector plugins
6. **Configuration:** Optimized nginx, PHP-FPM, MySQL settings

#### Interactive Menu
```bash
sudo ./ahg-installer.sh
# Shows menu with all options
```

**Pros:** Complete setup with one command, optimized configuration  
**Cons:** Long install time, less control over individual components

---

## What Gets Installed

### Installation Steps (bin/install)

| Step | Action | Details |
|------|--------|---------|
| 1 | Database tables | Creates `atom_plugin`, `atom_extension`, migrations tables |
| 2 | Plugin symlinks | Links `atom-ahg-plugins/*` → `plugins/*` |
| 3 | ProjectConfiguration | Copies template with `loadPluginsFromDatabase()` |
| 4 | Dist assets | Copies theme CSS/JS to `dist/` |
| 5 | Legacy themes | Updates `setting_i18n` for AtoM theme switcher |
| 6 | Clear cache | Removes `cache/*` |
| 6b | Plugin data | Executes each plugin's `data/install.sql` |
| 6c | PHP migrations | Runs `database/migrations/*.php` |
| 6d | Register plugins | Reads `extension.json`, populates `atom_plugin` |
| 7 | Required plugins | Enables ahgThemeB5Plugin + ahgSecurityClearancePlugin |
| 8 | Install log | Creates `logs/install-TIMESTAMP.log` |

---

## Database Tables

### Core Framework Tables

| Table | Purpose |
|-------|---------|
| `atom_plugin` | Plugin registry - source of truth for Symfony |
| `atom_plugin_audit` | Plugin enable/disable history |
| `atom_extension` | Extension registry for CLI management |
| `atom_extension_setting` | Per-extension configuration |
| `atom_extension_pending_deletion` | Uninstall grace period queue |
| `atom_extension_audit` | Extension change audit log |
| `ahg_settings` | Framework-wide settings |
| `framework_migrations` | Migration tracking |

---

## Plugin System

### Required Plugins (Locked)

| Plugin | Purpose | Can Disable? |
|--------|---------|--------------|
| ahgThemeB5Plugin | Bootstrap 5 theme | ❌ No |
| ahgSecurityClearancePlugin | Security classification | ❌ No |

### Plugin Loading
```
ProjectConfiguration::setup()
└── loadPluginsFromDatabase()
    └── SELECT name FROM atom_plugin WHERE is_enabled = 1
    └── enablePlugins($plugins)
```

---

## CLI Reference

### Framework Commands
```bash
php bin/atom framework:version       # Show version
php bin/atom framework:install       # Run migrations
php bin/atom framework:update        # Update from GitHub
```

### Extension Commands
```bash
php bin/atom extension:discover      # List available extensions
php bin/atom extension:list          # List installed/enabled
php bin/atom extension:install <n>   # Install extension
php bin/atom extension:uninstall <n> # Uninstall extension
php bin/atom extension:enable <n>    # Enable extension
php bin/atom extension:disable <n>   # Disable extension
php bin/atom extension:update --all  # Update all
```

### Migration Commands
```bash
php bin/atom migrate run             # Run pending migrations
php bin/atom migrate status          # Show migration status
```

### Shortcuts
```bash
php bin/atom update                  # Pull latest + run migrations
php bin/atom help                    # Show all commands
```

---

## Update Process

### For Git Clone Installs (Method 1)
```bash
cd /usr/share/nginx/atom/atom-framework
git pull origin main
composer install

cd /usr/share/nginx/atom/atom-ahg-plugins
git pull origin main

php bin/atom migrate run
sudo service php8.3-fpm restart
```

### For Package Installs (Methods 2-3)

Download and install the latest package - it will update in place.

### Quick Update Command
```bash
php bin/atom update
sudo service php8.3-fpm restart
```

---

## Uninstallation

### DEB Package (Method 2)
```bash
sudo apt remove atom-ahg-framework
```

### Other Methods
```bash
cd /usr/share/nginx/atom/atom-framework
sudo ./bin/uninstall.sh
```

### What Uninstall Does

1. ✅ Restores original AtoM core files from `.ahg-backups/`
2. ✅ Removes plugin symlinks
3. ✅ Optionally removes database tables
4. ✅ Clears cache

---

## Modified Core Files

⚠️ **Important:** The framework modifies these AtoM core files:

| File | Modification | Purpose |
|------|--------------|---------|
| `config/ProjectConfiguration.class.php` | Add `loadPluginsFromDatabase()` | Database-driven plugin loading |
| `lib/routing/QubitMetadataRoute.class.php` | Add GLAM sectors | Museum/Library/Gallery/DAM routing |
| `plugins/sfPluginAdminPlugin/.../themesAction.class.php` | Comment out unset loop | Theme visibility fix |
| `plugins/qbAclPlugin/lib/QubitAcl.class.php` | Add `in_array` check | Role 99 duplicate fix |

### Backups Location
```
{ATOM_ROOT}/.ahg-backups/
├── ProjectConfiguration.class.php.bak.YYYYMMDD
├── QubitMetadataRoute.class.php.bak.YYYYMMDD
├── themesAction.class.php.bak.YYYYMMDD
└── QubitAcl.class.php.bak.YYYYMMDD
```

### After AtoM Upgrades

Re-run the installer to reapply patches:
```bash
cd /usr/share/nginx/atom/atom-framework
bash bin/install
```

---

## Troubleshooting

### Plugins Not Loading
```bash
# Check function exists
grep "loadPluginsFromDatabase" /usr/share/nginx/atom/config/ProjectConfiguration.class.php

# Fix: Re-run install
bash bin/install
```

### Theme Not Showing
```bash
# Check symlink
ls -la /usr/share/nginx/atom/plugins/ | grep ahgThemeB5

# Check enabled
mysql -u root atom -e "SELECT name, is_enabled FROM atom_plugin WHERE name = 'ahgThemeB5Plugin';"
```

### Permission Errors
```bash
sudo chown -R www-data:www-data /usr/share/nginx/atom
sudo chmod -R 755 /usr/share/nginx/atom
```

### Clear All Caches
```bash
rm -rf /usr/share/nginx/atom/cache/*
php symfony cc
sudo service php8.3-fpm restart
```

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
║  DEB:         sudo apt install ./atom-ahg-framework_all.deb             ║
║  Shell:       sudo ./atom-ahg-framework.run                             ║
║  Wizard:      sudo ./setup-wizard.sh                                    ║
║  Docker:      docker-compose up -d                                       ║
║  Full Stack:  sudo ./ahg-installer.sh --full-stack                      ║
║                                                                          ║
║  DAILY USE:                                                              ║
║  ────────────────────────────────────────────────────────────────────    ║
║  php bin/atom extension:discover      # Check for updates                ║
║  php bin/atom extension:enable <n>    # Enable plugin                    ║
║  php bin/atom migrate status          # Check migrations                 ║
║                                                                          ║
║  UPDATE:                                                                 ║
║  ────────────────────────────────────────────────────────────────────    ║
║  php bin/atom update                  # Pull latest                      ║
║  sudo service php8.3-fpm restart      # Restart PHP                      ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
```

---

## Support

- **GitHub Issues:** [Report a bug](https://github.com/ArchiveHeritageGroup/atom-framework/issues)
- **Documentation:** [docs/](docs/)
- **Email:** info@theahg.co.za
- **Website:** [theahg.co.za](https://theahg.co.za)

---

<div align="center">

**Document Version:** 1.4.0 | **Compatible with:** AtoM 2.8.x - 2.10.x | **License:** GPL-3.0

**[The Archive And Heritage Digital Commons Group](https://theahg.co.za)**

</div>
