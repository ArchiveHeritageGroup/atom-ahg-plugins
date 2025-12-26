#!/bin/bash
# =============================================================================
# arDisplayPlugin Installation Script (with Elasticsearch 7 support)
# =============================================================================

ATOM_DIR="${ATOM_DIR:-/usr/share/nginx/atom}"
PLUGIN_DIR="$ATOM_DIR/plugins/arDisplayPlugin"
DB_NAME="${DB_NAME:-atom}"
DB_USER="${DB_USER:-atom}"

echo "============================================"
echo "   arDisplayPlugin INSTALLATION"
echo "   (with Elasticsearch 7 Integration)"
echo "============================================"
echo ""
echo "AtoM Directory: $ATOM_DIR"
echo "Database: $DB_NAME"
echo ""

# Check if plugin directory exists
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "ERROR: Plugin directory not found at $PLUGIN_DIR"
    echo "Please copy the plugin to your AtoM plugins directory first."
    exit 1
fi

# Enable plugin in ProjectConfiguration
PROJ_CONFIG="$ATOM_DIR/config/ProjectConfiguration.class.php"
if [ -f "$PROJ_CONFIG" ]; then
    if ! grep -q "arDisplayPlugin" "$PROJ_CONFIG"; then
        echo "Enabling plugin in ProjectConfiguration..."
        sed -i "/sfEadPlugin/a\            'arDisplayPlugin'," "$PROJ_CONFIG"
        echo "✓ Plugin enabled"
    else
        echo "✓ Plugin already enabled in ProjectConfiguration"
    fi
else
    echo "WARNING: ProjectConfiguration.class.php not found"
    echo "Please manually add 'arDisplayPlugin' to your enabled plugins."
fi

# Install database schema
echo ""
echo "Installing database schema..."
if mysql -u "$DB_USER" "$DB_NAME" < "$PLUGIN_DIR/lib/install.sql" 2>/dev/null; then
    echo "✓ Database schema installed"
else
    echo "WARNING: Could not auto-install schema"
    echo "Run manually: mysql -u $DB_USER -p $DB_NAME < $PLUGIN_DIR/lib/install.sql"
fi

# Clear cache
echo ""
echo "Clearing cache..."
if [ -f "$ATOM_DIR/symfony" ]; then
    php "$ATOM_DIR/symfony" cc 2>/dev/null
    echo "✓ Cache cleared"
else
    echo "WARNING: symfony command not found"
    echo "Please clear cache manually: php symfony cc"
fi

echo ""
echo "============================================"
echo "   INSTALLATION COMPLETE"
echo "============================================"
echo ""
echo "NEXT STEPS - Elasticsearch 7:"
echo ""
echo "1. Update ES mapping:"
echo "   php symfony display:reindex --update-mapping"
echo ""
echo "2. Reindex display data:"
echo "   php symfony display:reindex --batch=100"
echo ""
echo "3. (Optional) Set object types for collections:"
echo "   Visit: /display/bulkSetType"
echo ""
echo "============================================"
echo ""
echo "URLs:"
echo "  Admin:  /display"
echo "  Browse: /displaySearch/browse?type=archive"
echo "  Search: /displaySearch/search"
echo ""
echo "Object Types: archive, museum, gallery, library, dam"
echo ""
