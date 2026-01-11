# AtoM AHG Framework - Complete Installation Guide

<div align="center">

**Version 1.3.0** | **Last Updated: 2026-01-11** | **AtoM 2.8.x - 2.10.x**

[![Installation Methods](https://img.shields.io/badge/Methods-7-blue.svg)](#installation-methods)
[![Docker](https://img.shields.io/badge/Docker-Supported-2496ED.svg)](#method-6-docker)
[![Ansible](https://img.shields.io/badge/Ansible-Supported-EE0000.svg)](#method-5-ansible-playbook)

</div>

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Prerequisites](#prerequisites)
3. [Installation Methods](#installation-methods)
   - [Method 1: Manual Installation](#method-1-manual-installation)
   - [Method 2: DEB Package](#method-2-deb-package)
   - [Method 3: Self-Extracting Shell (.run)](#method-3-self-extracting-shell-run)
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

**Choose your path:**

| I have... | Recommended Method | Time |
|-----------|-------------------|------|
| Existing AtoM installation | [Manual Install](#method-1-manual-installation) | ~5 min |
| Fresh Ubuntu server | [Full Stack](#method-7-full-stack-new-server) | ~20 min |
| Docker environment | [Docker](#method-6-docker) | ~10 min |
| Multiple servers to manage | [Ansible](#method-5-ansible-playbook) | ~15 min |
| Preference for GUI | [Setup Wizard](#method-4-setup-wizard-tui) | ~10 min |

### Fastest Path (Existing AtoM)

```bash
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
cd atom-framework && composer install
cd .. && git clone https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git
cd atom-framework && bash bin/install
sudo service php8.3-fpm restart
php bin/atom extension:discover
```

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
- Database credentials available
- SSH/terminal access

### Verify Your System

```bash
php -v                    # Should show 8.1+
mysql --version           # Should show 8.0+
composer --version        # Should show 2.x
cd /usr/share/nginx/atom && php symfony --version  # AtoM check
```

---

## Installation Methods

### Method 1: Manual Installation

**Best for:** Developers, custom setups, full control

**Time:** ~5 minutes

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

---

### Method 2: DEB Package

**Best for:** Ubuntu/Debian servers, package management

**Time:** ~5 minutes

#### Build the Package (on dev server)

```bash
cd /usr/share/nginx/atom/atom-framework
./bin/build-deb.sh
# Output: atom-ahg-framework_X.X.X_all.deb
```

#### Install

```bash
# With apt (recommended - handles dependencies)
sudo apt install ./atom-ahg-framework_X.X.X_all.deb

# Or with dpkg
sudo dpkg -i atom-ahg-framework_X.X.X_all.deb
sudo apt-get install -f  # Fix dependencies if needed
```

#### Uninstall

```bash
sudo apt remove atom-ahg-framework
```

---

### Method 3: Self-Extracting Shell (.run)

**Best for:** Any Linux distro, portable installer

**Time:** ~5 minutes

#### Build the Package

```bash
cd /usr/share/nginx/atom/atom-framework
./bin/build-installer.sh
# Output: atom-ahg-framework-X.X.X.run
```

#### Install

```bash
# Copy to target server
scp atom-ahg-framework-X.X.X.run user@server:/tmp/

# Run installer
sudo /tmp/atom-ahg-framework-X.X.X.run

# Or extract only (no install)
./atom-ahg-framework-X.X.X.run --extract-only --target /path/to/extract
```

#### Uninstall

```bash
sudo /usr/share/nginx/atom/atom-framework/bin/uninstall.sh
```

---

### Method 4: Setup Wizard (TUI)

**Best for:** Interactive installation, guided setup

**Time:** ~10 minutes

```bash
cd /usr/share/nginx/atom/atom-framework
sudo ./bin/setup-wizard.sh
```

#### Features

- Welcome screen with version info
- License agreement
- Automatic AtoM path detection
- Core file modification warnings
- Component selection (checkboxes)
- Database configuration
- Progress bar
- Completion summary

**Requires:** `dialog` or `whiptail` (auto-installed if missing)

---

### Method 5: Ansible Playbook

**Best for:** Multiple servers, automation, DevOps

**Time:** ~15 minutes (first run)

#### Setup Inventory

```yaml
# ansible/inventory.yml
all:
  children:
    development:
      hosts:
        dev-server:
          ansible_host: 192.168.0.112
          ansible_user: root
          atom_root: /usr/share/nginx/archive
    
    test:
      hosts:
        test-server:
          ansible_host: 192.168.0.154
          ansible_user: root
          atom_root: /usr/share/nginx/atom
    
    production:
      hosts:
        prod-server:
          ansible_host: your.domain.com
          ansible_user: deploy
          ansible_become: yes
          atom_root: /usr/share/nginx/atom
```

#### Run Playbook

```bash
cd /usr/share/nginx/atom/atom-framework/ansible

# Install on single server
ansible-playbook -i inventory.yml atom-ahg-install.yml --limit dev-server

# Install on multiple servers
ansible-playbook -i inventory.yml atom-ahg-install.yml --limit "development,test"

# Dry run (check mode)
ansible-playbook -i inventory.yml atom-ahg-install.yml --check

# Specific tags only
ansible-playbook -i inventory.yml atom-ahg-install.yml --tags "framework"

# Uninstall
ansible-playbook -i inventory.yml atom-ahg-install.yml --tags "uninstall"
```

---

### Method 6: Docker

**Best for:** Containerized environments, isolation, development

**Time:** ~10 minutes

#### Quick Start

```bash
cd /usr/share/nginx/atom/atom-framework/docker

# Configure environment
cp .env.example .env
nano .env  # Edit settings

# Start basic stack
docker-compose up -d

# Start with optional services
docker-compose --profile ric up -d     # + Apache Jena Fuseki (RiC)
docker-compose --profile iiif up -d    # + Cantaloupe (IIIF)
docker-compose --profile all up -d     # All services
```

#### Services

| Service | Port | Description |
|---------|------|-------------|
| atom | 80/443 | AtoM web application |
| mysql | 3306 | MySQL database |
| elasticsearch | 9200 | Search engine |
| memcached | 11211 | Cache |
| fuseki | 3030 | RiC triplestore (optional) |
| cantaloupe | 8182 | IIIF image server (optional) |

#### Commands

```bash
docker-compose logs -f atom           # View logs
docker-compose exec atom bash         # Enter container
docker-compose down                   # Stop
docker-compose down -v                # Stop + remove volumes
```

---

### Method 7: Full Stack (New Server)

**Best for:** Fresh Ubuntu server, complete setup

**Time:** ~20-30 minutes

This installs **everything**: Ubuntu packages, nginx, PHP, MySQL, Elasticsearch, AtoM, and the AHG Framework.

#### One-Command Install

```bash
wget https://raw.githubusercontent.com/ArchiveHeritageGroup/atom-framework/main/bin/ahg-installer.sh
chmod +x ahg-installer.sh
sudo ./ahg-installer.sh --full-stack
```

#### Or Interactive Menu

```bash
sudo ./bin/ahg-installer.sh
# Select option 4: Full Stack
```

#### What Gets Installed

1. **System packages:** nginx, PHP 8.3, MySQL 8, dependencies
2. **Elasticsearch:** Version 8.x
3. **AtoM 2.10:** From Artefactual's repository
4. **AHG Framework:** Core + all plugins
5. **Configuration:** nginx, PHP-FPM, MySQL optimizations

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

### atom_plugin Structure

```sql
CREATE TABLE atom_plugin (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    class_name VARCHAR(255) NOT NULL,
    version VARCHAR(50),
    description TEXT,
    is_enabled TINYINT(1) DEFAULT 0,
    is_core TINYINT(1) DEFAULT 0,      -- Cannot be disabled
    is_locked TINYINT(1) DEFAULT 0,    -- Cannot be modified
    load_order INT DEFAULT 100,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Plugin System

### Required Plugins (Locked)

| Plugin | Purpose | Locked |
|--------|---------|--------|
| ahgThemeB5Plugin | Bootstrap 5 theme | ✅ |
| ahgSecurityClearancePlugin | Security classification | ✅ |

### Plugin Loading

```
ProjectConfiguration::setup()
└── loadPluginsFromDatabase($corePlugins)
    └── SELECT name FROM atom_plugin WHERE is_enabled = 1
    └── enablePlugins($plugins)
```

### Plugin Structure

```
ahgExamplePlugin/
├── extension.json              # Manifest
├── config/
│   └── ahgExamplePluginConfiguration.class.php
├── lib/
│   ├── Repositories/
│   ├── Services/
│   └── Helpers/
├── modules/
├── templates/
├── css/
├── js/
└── data/
    └── install.sql
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
php bin/atom extension:audit         # Show audit log
```

### Migration Commands

```bash
php bin/atom migrate run             # Run pending migrations
php bin/atom migrate status          # Show migration status
```

### Shortcut Commands

```bash
php bin/atom update                  # Pull latest from GitHub
php bin/atom help                    # Show all commands
```

---

## Update Process

### Quick Update

```bash
php bin/atom update
sudo service php8.3-fpm restart
```

### Manual Update

```bash
cd /usr/share/nginx/atom/atom-framework
git pull origin main
composer install

cd /usr/share/nginx/atom/atom-ahg-plugins
git pull origin main

php bin/atom migrate run
sudo service php8.3-fpm restart
```

### Via Master Installer

```bash
./bin/ahg-installer.sh --update
```

---

## Uninstallation

### Method 1: Script

```bash
cd /usr/share/nginx/atom/atom-framework
sudo ./bin/uninstall.sh
```

### Method 2: DEB Package

```bash
sudo apt remove atom-ahg-framework
```

### What Uninstall Does

1. Restores original core files from `.ahg-backups/`
2. Removes plugin symlinks
3. Optionally removes database tables
4. Clears cache

---

## Modified Core Files

⚠️ **The framework modifies these AtoM core files:**

| File | Change | Purpose |
|------|--------|---------|
| `config/ProjectConfiguration.class.php` | Add `loadPluginsFromDatabase()` | Database-driven plugin loading |
| `lib/routing/QubitMetadataRoute.class.php` | Add GLAM sectors | Museum/Library/Gallery/DAM routing |
| `plugins/sfPluginAdminPlugin/.../themesAction.class.php` | Comment out unset loop | Theme visibility fix |
| `plugins/qbAclPlugin/lib/QubitAcl.class.php` | Add `in_array` check | Role 99 duplicate fix |

### Backups Location

```
{ATOM_ROOT}/.ahg-backups/
├── ProjectConfiguration.class.php.bak.20260111
├── QubitMetadataRoute.class.php.bak.20260111
├── themesAction.class.php.bak.20260111
└── QubitAcl.class.php.bak.20260111
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

# Check database
mysql -u root atom -e "SELECT name, is_enabled FROM atom_plugin WHERE name = 'ahgThemeB5Plugin';"
```

### Plugin Enabled But Not Working

```bash
# Clear all caches
rm -rf /usr/share/nginx/atom/cache/*
php symfony cc
sudo service php8.3-fpm restart
```

### Migration Errors

```bash
php bin/atom migrate status
php bin/atom migrate run --force
```

### Permission Errors

```bash
sudo chown -R www-data:www-data /usr/share/nginx/atom
sudo chmod -R 755 /usr/share/nginx/atom
```

---

## Quick Reference Card

```
╔══════════════════════════════════════════════════════════════════════════╗
║                    AtoM AHG Framework Quick Reference                    ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║  INSTALL:                                                                ║
║  ────────────────────────────────────────────────────────────────────    ║
║  Manual:      bash bin/install                                           ║
║  DEB:         sudo apt install ./atom-ahg-framework_X.X.X_all.deb       ║
║  Shell:       sudo ./atom-ahg-framework-X.X.X.run                       ║
║  Wizard:      sudo ./bin/setup-wizard.sh                                ║
║  Ansible:     ansible-playbook -i inventory.yml atom-ahg-install.yml    ║
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
║  php bin/atom migrate run             # Run migrations                   ║
║  sudo service php8.3-fpm restart      # Restart PHP                      ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
```

---

## File Structure

```
atom-framework/
├── bin/
│   ├── ahg-installer.sh          # Master installer
│   ├── install                   # Core install script
│   ├── atom                      # CLI entry point
│   ├── release                   # Version bumping
│   ├── setup-wizard.sh           # TUI installer
│   ├── build-installer.sh        # Build .run
│   ├── build-deb.sh              # Build .deb
│   └── uninstall.sh              # Uninstaller
├── ansible/
│   ├── atom-ahg-install.yml      # Playbook
│   └── inventory.yml             # Servers
├── docker/
│   ├── Dockerfile
│   ├── docker-compose.yml
│   └── .env.example
├── config/
│   └── ProjectConfiguration.class.php.template
├── database/
│   ├── install.sql
│   └── migrations/
├── src/
│   ├── Extensions/
│   ├── Repositories/
│   ├── Services/
│   └── Helpers/
└── version.json
```

---

## Support

- **Documentation:** [docs/](docs/)
- **Issues:** [GitHub Issues](https://github.com/ArchiveHeritageGroup/atom-framework/issues)
- **AtoM Forum:** [Google Groups](https://groups.google.com/g/ica-atom-users)
- **Email:** info@theahg.co.za

---

<div align="center">

**Document Version:** 1.3.0 | **Framework Version:** See `version.json`

**Compatible with:** AtoM 2.8.x - 2.10.x | **License:** GPL-3.0

**The Archive And Heritage Digital Commons Group**

</div>
