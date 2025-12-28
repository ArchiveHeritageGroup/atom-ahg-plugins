# Installation Guide

Complete guide for installing the AtoM Extension Framework and extensions.

---

## Prerequisites

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| AtoM | 2.10.0 | 2.10.1+ |
| PHP | 8.1 | 8.3 |
| MySQL | 8.0 | 8.0+ |
| Composer | 2.x | Latest |

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

### Step 3: Run Framework Installer
```bash
php bin/atom framework:install
```

This will:
- ✅ Create all required database tables
- ✅ Run pending migrations
- ✅ Configure default settings

### Step 4: Verify
```bash
php bin/atom extension:list
php bin/atom migrate status
```

---

## Install Extensions

### Discover Available Extensions
```bash
php bin/atom extension:discover
```

### Install from Local or GitHub
```bash
# Install by machine name
php bin/atom extension:install arAHGThemeB5Plugin

# Enable the extension
php bin/atom extension:enable arAHGThemeB5Plugin

# Clear Symfony cache
php symfony cc
```

### Install Multiple Extensions
```bash
# Theme + GLAM sectors
php bin/atom extension:install arAHGThemeB5Plugin
php bin/atom extension:install sfMuseumPlugin
php bin/atom extension:install arDAMPlugin

# Enable all
php bin/atom extension:enable arAHGThemeB5Plugin
php bin/atom extension:enable sfMuseumPlugin
php bin/atom extension:enable arDAMPlugin

php symfony cc
```

---

## Upgrading

When updating the framework:
```bash
cd /usr/share/nginx/atom/atom-framework
git pull
php bin/atom migrate run
```

This runs only new migrations - already-executed migrations are tracked and skipped.

### Check Migration Status
```bash
php bin/atom migrate status
```

---

## CLI Reference

| Command | Description |
|---------|-------------|
| `php bin/atom framework:install` | Install/upgrade framework |
| `php bin/atom migrate run` | Run pending migrations |
| `php bin/atom migrate status` | Show migration status |
| `php bin/atom extension:discover` | Find available extensions |
| `php bin/atom extension:list` | List installed extensions |
| `php bin/atom extension:install <name>` | Install extension |
| `php bin/atom extension:enable <name>` | Enable extension |
| `php bin/atom extension:disable <name>` | Disable extension |
| `php bin/atom extension:uninstall <name>` | Uninstall (30-day grace) |
| `php bin/atom extension:audit` | View audit log |

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Extension not found | Run `php bin/atom extension:discover` |
| Database errors | Run `php bin/atom framework:install` |
| Migration failed | Check `php bin/atom migrate status` |
| Permission denied | Run `chown -R www-data:www-data plugins/` |
| Theme not loading | Set `is_enabled=0` in `atom_plugin` table |

### Reset Migrations (Development Only)
```bash
# View what's been run
mysql -u root -p archive -e "SELECT * FROM atom_framework_migrations;"

# To re-run a migration (use with caution!)
mysql -u root -p archive -e "DELETE FROM atom_framework_migrations WHERE migration='migration_name';"
php bin/atom migrate run
```

---

## Get Help

- [GitHub Issues](https://github.com/ArchiveHeritageGroup/atom-framework/issues)
- [AtoM Community](https://groups.google.com/g/ica-atom-users)
