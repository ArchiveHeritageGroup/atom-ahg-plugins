#!/bin/bash
#
# ar3DModelPlugin Installation Script
# 
# Run this script on the AtoM server to install the 3D model plugin
#
# Usage: sudo bash install.sh
#

set -e

# Configuration
ATOM_DIR="/usr/share/nginx/archive"
PLUGIN_DIR="$ATOM_DIR/plugins/ar3DModelPlugin"
FRAMEWORK_DIR="$ATOM_DIR/atom-framework"
UPLOAD_DIR="$ATOM_DIR/uploads/3d"
WEB_USER="www-data"
MYSQL_USER="root"
MYSQL_PASS="Merlot@123"
MYSQL_DB="archive"

echo "=============================================="
echo "ar3DModelPlugin Installation"
echo "=============================================="

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root (sudo)"
    exit 1
fi

# Check if AtoM directory exists
if [ ! -d "$ATOM_DIR" ]; then
    echo "Error: AtoM directory not found at $ATOM_DIR"
    exit 1
fi

echo ""
echo "Step 1: Creating plugin directory..."
mkdir -p "$PLUGIN_DIR"

echo "Step 2: Copying plugin files..."
# Copy config
cp -r config "$PLUGIN_DIR/"
# Copy modules
cp -r modules "$PLUGIN_DIR/"
# Copy lib
cp -r lib "$PLUGIN_DIR/"
# Copy assets
mkdir -p "$PLUGIN_DIR/css"
mkdir -p "$PLUGIN_DIR/js"
cp css/model3d.css "$PLUGIN_DIR/css/"
cp js/model3d.js "$PLUGIN_DIR/js/"
# Copy README
cp README.md "$PLUGIN_DIR/"

echo "Step 3: Copying service file to atom-framework..."
if [ -d "$FRAMEWORK_DIR/src/Services" ]; then
    cp services/Model3DService.php "$FRAMEWORK_DIR/src/Services/"
    echo "  Service file copied successfully"
else
    echo "  Warning: Framework services directory not found"
    echo "  Please manually copy services/Model3DService.php to your framework"
fi

echo "Step 4: Creating upload directory..."
mkdir -p "$UPLOAD_DIR"
chown -R "$WEB_USER:$WEB_USER" "$UPLOAD_DIR"
chmod 755 "$UPLOAD_DIR"

echo "Step 5: Installing database schema..."
mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" "$MYSQL_DB" < schema/install.sql
if [ $? -eq 0 ]; then
    echo "  Database schema installed successfully"
else
    echo "  Warning: Database installation failed. Please run manually:"
    echo "  mysql -u root -p archive < schema/install.sql"
fi

echo "Step 6: Setting permissions..."
chown -R "$WEB_USER:$WEB_USER" "$PLUGIN_DIR"
find "$PLUGIN_DIR" -type f -exec chmod 644 {} \;
find "$PLUGIN_DIR" -type d -exec chmod 755 {} \;

echo "Step 7: Creating symlinks for web assets..."
ASSETS_WEB="$ATOM_DIR/plugins/ar3DModelPlugin/js"
if [ -d "$ASSETS_WEB" ]; then
    echo "  Assets directory ready"
fi

echo "Step 8: Clearing Symfony cache..."
php "$ATOM_DIR/symfony" cc
if [ $? -eq 0 ]; then
    echo "  Cache cleared successfully"
else
    echo "  Warning: Cache clear failed. Please run manually:"
    echo "  php $ATOM_DIR/symfony cc"
fi

echo ""
echo "=============================================="
echo "Installation Complete!"
echo "=============================================="
echo ""
echo "Next steps:"
echo "1. Enable the plugin in AtoM Admin > Plugins"
echo "2. Configure settings at Admin > 3D Viewer Settings"
echo "3. Add MIME types to nginx config if not present"
echo ""
echo "To add 3D models:"
echo "- Navigate to an archival description"
echo "- Click 'Add 3D Model' (editors/admins only)"
echo ""
echo "For AR support, ensure your site uses HTTPS."
echo ""
