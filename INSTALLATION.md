# Installation Guide

Complete guide for installing the AtoM Extension Framework and extensions.

---

## Prerequisites

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| AtoM | 2.10.0 | 2.10.1+ |
| PHP | 8.1 | 8.3 |
| MySQL | 8.0 | 8.0+ |
| Memory | 2GB | 4GB+ |

---

## Install Framework

The `atom-framework` is **required** for all extensions.

### Step 1: Clone Framework
```bash
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
```

### Step 2: Install Dependencies
```bash
cd atom-framework
composer install --no-dev
```

### Step 3: Run Installer
```bash
php bin/atom framework:install
```

### Step 4: Verify
```bash
php bin/atom extension:list
```

---

## Install Extensions

### From GitHub
```bash
# Discover available extensions
php bin/atom extension:discover

# Install by name
php bin/atom extension:install arAHGThemeB5Plugin

# Enable
php bin/atom extension:enable arAHGThemeB5Plugin

# Clear cache
php symfony cc
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Extension not found | Run `php bin/atom extension:discover` |
| Database errors | Check credentials in `config/config.php` |
| Permission denied | Run `chown -R www-data:www-data plugins/` |
| Theme not loading | Set `is_enabled=0` in `atom_plugin` table |

---

## Get Help

- [GitHub Issues](https://github.com/ArchiveHeritageGroup/atom-framework/issues)
- [AtoM Community](https://groups.google.com/g/ica-atom-users)
