#!/bin/bash
#
# Gearman Setup Script for AtoM Data Migration Plugin
#
# This script installs and configures Gearman for background job processing.
# Run as root or with sudo.
#
# Usage: sudo ./setup-gearman.sh
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
ATOM_ROOT="${ATOM_ROOT:-/usr/share/nginx/archive}"
PHP_VERSION="${PHP_VERSION:-8.3}"
WORKER_USER="${WORKER_USER:-www-data}"
WORKER_GROUP="${WORKER_GROUP:-www-data}"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Gearman Setup for Data Migration${NC}"
echo -e "${GREEN}========================================${NC}"
echo

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Error: Please run as root or with sudo${NC}"
    exit 1
fi

# Detect PHP version if not specified
if ! command -v php${PHP_VERSION} &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
    echo -e "${YELLOW}Detected PHP version: ${PHP_VERSION}${NC}"
fi

echo -e "${YELLOW}Step 1: Installing Gearman packages...${NC}"
apt-get update -qq
apt-get install -y gearman-job-server php${PHP_VERSION}-gearman

echo -e "${YELLOW}Step 2: Starting Gearman server...${NC}"
systemctl enable gearman-job-server
systemctl start gearman-job-server

echo -e "${YELLOW}Step 3: Restarting PHP-FPM...${NC}"
systemctl restart php${PHP_VERSION}-fpm

echo -e "${YELLOW}Step 4: Verifying installation...${NC}"

# Check Gearman server
if systemctl is-active --quiet gearman-job-server; then
    echo -e "  ${GREEN}[OK]${NC} Gearman server is running"
else
    echo -e "  ${RED}[FAIL]${NC} Gearman server is not running"
    exit 1
fi

# Check PHP extension
if php -m | grep -q gearman; then
    echo -e "  ${GREEN}[OK]${NC} PHP Gearman extension is loaded"
else
    echo -e "  ${RED}[FAIL]${NC} PHP Gearman extension not loaded"
    exit 1
fi

# Check Gearman port
if netstat -tlnp 2>/dev/null | grep -q ":4730" || ss -tlnp | grep -q ":4730"; then
    echo -e "  ${GREEN}[OK]${NC} Gearman listening on port 4730"
else
    echo -e "  ${YELLOW}[WARN]${NC} Could not verify Gearman port"
fi

echo -e "${YELLOW}Step 5: Creating systemd service for AtoM worker...${NC}"

cat > /etc/systemd/system/atom-worker.service << EOF
[Unit]
Description=AtoM Gearman Worker
After=network.target gearman-job-server.service
Requires=gearman-job-server.service

[Service]
Type=simple
User=${WORKER_USER}
Group=${WORKER_GROUP}
WorkingDirectory=${ATOM_ROOT}
ExecStart=/usr/bin/php symfony jobs:worker
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable atom-worker

echo -e "${YELLOW}Step 6: Starting AtoM worker service...${NC}"
systemctl start atom-worker

# Verify worker is running
sleep 2
if systemctl is-active --quiet atom-worker; then
    echo -e "  ${GREEN}[OK]${NC} AtoM worker service is running"
else
    echo -e "  ${RED}[FAIL]${NC} AtoM worker service failed to start"
    echo "  Check logs with: journalctl -u atom-worker -n 50"
    exit 1
fi

echo
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Gearman Setup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo
echo "Useful commands:"
echo "  - Check Gearman status:  gearadmin --status"
echo "  - Check worker status:   systemctl status atom-worker"
echo "  - View worker logs:      journalctl -u atom-worker -f"
echo "  - Restart worker:        systemctl restart atom-worker"
echo
echo "The Data Migration Plugin will now queue large imports/exports"
echo "as background jobs that the worker will process automatically."
echo
