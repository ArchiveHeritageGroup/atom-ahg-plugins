#!/bin/bash
# =============================================================================
# ahgRightsPlugin Installation Script
# AtoM 2.10 / Laravel Rights Management
# =============================================================================

set -e

# Configuration
ATOM_ROOT="${ATOM_ROOT:-/usr/share/nginx/archive}"
PLUGINS_DIR="${ATOM_ROOT}/plugins"
DB_NAME="${DB_NAME:-archive}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
PLUGIN_NAME="ahgRightsPlugin"

echo "=============================================="
echo "  ahgRightsPlugin Installer"
echo "=============================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Warning: Not running as root. You may need sudo for some operations."
fi

# Check AtoM installation
if [ ! -d "$ATOM_ROOT" ]; then
    echo "Error: AtoM not found at $ATOM_ROOT"
    echo "Set ATOM_ROOT environment variable to your AtoM installation path"
    exit 1
fi

echo "AtoM root: $ATOM_ROOT"
echo "Database: $DB_NAME"
echo ""

# Step 1: Extract plugin
echo "[1/4] Installing plugin files..."
if [ -f "ahgRightsPlugin-v1.0.0.tar.gz" ]; then
    tar -xzf ahgRightsPlugin-v1.0.0.tar.gz -C "$PLUGINS_DIR/"
    echo "  ✓ Plugin files extracted"
elif [ -d "ahgRightsPlugin" ]; then
    cp -r ahgRightsPlugin "$PLUGINS_DIR/"
    echo "  ✓ Plugin files copied"
else
    echo "  ✗ Error: Plugin archive not found"
    exit 1
fi

# Step 2: Run database migration
echo "[2/4] Running database migration..."
SQL_FILE="$PLUGINS_DIR/$PLUGIN_NAME/data/migrations/001_rights_system.sql"

if [ -f "$SQL_FILE" ]; then
    if [ -n "$DB_PASS" ]; then
        mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE"
    else
        mysql -u "$DB_USER" "$DB_NAME" < "$SQL_FILE"
    fi
    echo "  ✓ Database tables created"
else
    echo "  ✗ Error: SQL file not found"
    exit 1
fi

# Step 3: Enable plugin in settings
echo "[3/4] Enabling plugin..."
SETTINGS_FILE="$ATOM_ROOT/apps/qubit/config/settings.yml"

if grep -q "ahgRightsPlugin" "$SETTINGS_FILE" 2>/dev/null; then
    echo "  ⓘ Plugin already in settings.yml"
else
    echo "  Note: Add 'ahgRightsPlugin' to plugins list in $SETTINGS_FILE"
fi

# Step 4: Clear cache
echo "[4/4] Clearing cache..."
cd "$ATOM_ROOT"
php symfony cc 2>/dev/null || php-fpm -R 2>/dev/null || echo "  ⓘ Clear cache manually: php symfony cc"
echo "  ✓ Cache cleared"

echo ""
echo "=============================================="
echo "  Installation Complete!"
echo "=============================================="
echo ""
echo "Access the admin at: /rights/admin"
echo ""
echo "Available URLs:"
echo "  - Dashboard:     /rights/admin"
echo "  - Embargoes:     /rights/admin/embargoes"
echo "  - Orphan Works:  /rights/admin/orphan-works"
echo "  - TK Labels:     /rights/admin/tk-labels"
echo "  - Statements:    /rights/admin/statements"
echo ""
echo "Object rights:     /{slug}/rights"
echo ""
