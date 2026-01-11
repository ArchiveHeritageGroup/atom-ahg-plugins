# AtoM AHG Framework - Complete Installation Guide

<div align="center">

**Version 1.5.0** | **Last Updated: 2026-01-11** | **AtoM 2.8.x - 2.10.x**

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
# Download latest release
VERSION=$(curl -s https://api.github.com/repos/ArchiveHeritageGroup/atom-framework/releases/latest | grep tag_name | cut -d'"' -f4)
wget "https://github.com/ArchiveHeritageGroup/atom-framework/releases/download/${VERSION}/atom-ahg-framework_all.deb"
```

### Install
```bash
# Install with apt (handles dependencies)
sudo apt install ./atom-ahg-framework_all.deb

# Restart services
sudo systemctl restart php8.3-fpm
```

### Uninstall
```bash
sudo apt remove atom-ahg-framework
```

---

## Method 3: Self-Extracting Installer

**Best for:** Any Linux, portable single file

### Download
Go to **[GitHub Releases](https://github.com/ArchiveHeritageGroup/atom-framework/releases/latest)** and download `atom-ahg-framework-X.X.X.run`

Or via command line:
```bash
VERSION=$(curl -s https://api.github.com/repos/ArchiveHeritageGroup/atom-framework/releases/latest | grep tag_name | cut -d'"' -f4)
wget "https://github.com/ArchiveHeritageGroup/atom-framework/releases/download/${VERSION}/atom-ahg-framework.run"
chmod +x atom-ahg-framework.run
```

### Install
```bash
# Run installer (will prompt for AtoM path)
sudo ./atom-ahg-framework.run

# Or specify path
sudo ./atom-ahg-framework.run --atom-root=/usr/share/nginx/atom
```

### Extract Only
```bash
./atom-ahg-framework.run --extract-only --target /tmp/ahg-extract
```

---

## Method 4: Setup Wizard

**Best for:** Interactive, guided installation

### Download & Run
```bash
VERSION=$(curl -s https://api.github.com/repos/ArchiveHeritageGroup/atom-framework/releases/latest | grep tag_name | cut -d'"' -f4)
wget "https://github.com/ArchiveHeritageGroup/atom-framework/releases/download/${VERSION}/setup-wizard.sh"
chmod +x setup-wizard.sh
sudo ./setup-wizard.sh
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

### Download
```bash
VERSION=$(curl -s https://api.github.com/repos/ArchiveHeritageGroup/atom-framework/releases/latest | grep tag_name | cut -d'"' -f4)
wget "https://github.com/ArchiveHeritageGroup/atom-framework/releases/download/${VERSION}/ansible-playbook.tar.gz"
tar -xzf ansible-playbook.tar.gz
cd ansible
```

### Configure Inventory
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
```

### Run
```bash
# Install on single server
ansible-playbook -i inventory.yml atom-ahg-install.yml --limit archive-server

# Dry run
ansible-playbook -i inventory.yml atom-ahg-install.yml --check
```

---

## Method 6: Docker

**Best for:** Containers, development, isolation

### Download
```bash
VERSION=$(curl -s https://api.github.com/repos/ArchiveHeritageGroup/atom-framework/releases/latest | grep tag_name | cut -d'"' -f4)
wget "https://github.com/ArchiveHeritageGroup/atom-framework/releases/download/${VERSION}/docker-compose.tar.gz"
tar -xzf docker-compose.tar.gz
cd docker
```

### Configure & Start
```bash
cp .env.example .env
nano .env  # Edit settings

docker-compose up -d
```

### Services
| Service | Port | Description |
|---------|------|-------------|
| atom | 80/443 | AtoM + AHG Framework |
| mysql | 3306 | MySQL 8.0 |
| elasticsearch | 9200 | Search engine |

### Commands
```bash
docker-compose logs -f atom      # View logs
docker-compose exec atom bash    # Enter container
docker-compose down              # Stop
```

---

## Method 7: Full Stack

**Best for:** Fresh Ubuntu server

Installs **everything**: nginx, PHP 8.3, MySQL 8, Elasticsearch, AtoM 2.10, and AHG Framework.

### One-Command Install
```bash
curl -fsSL https://raw.githubusercontent.com/ArchiveHeritageGroup/atom-framework/main/bin/ahg-installer.sh | sudo bash -s -- --full-stack
```

### Or Download & Run
```bash
wget https://raw.githubusercontent.com/ArchiveHeritageGroup/atom-framework/main/bin/ahg-installer.sh
chmod +x ahg-installer.sh
sudo ./ahg-installer.sh --full-stack
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

## CLI Reference

```bash
# Extensions
php bin/atom extension:discover      # List available
php bin/atom extension:enable <n> # Enable
php bin/atom extension:disable <n># Disable

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
║  Git:         git clone ... && bash bin/install                         ║
║  DEB:         sudo apt install ./atom-ahg-framework_all.deb             ║
║  Shell:       sudo ./atom-ahg-framework.run                             ║
║  Full Stack:  curl ... | sudo bash -s -- --full-stack                   ║
║                                                                          ║
║  DAILY USE:                                                              ║
║  ────────────────────────────────────────────────────────────────────    ║
║  php bin/atom extension:discover      # List extensions                  ║
║  php bin/atom extension:enable <n>    # Enable plugin                    ║
║  php bin/atom update                  # Pull latest                      ║
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