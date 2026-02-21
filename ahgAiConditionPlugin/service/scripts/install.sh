#!/bin/bash
# =============================================================================
# AHG AI Condition Service - Installation Script
# =============================================================================
# Creates virtual environment, installs dependencies, downloads base models,
# and sets up required directories.
#
# Usage:
#   chmod +x scripts/install.sh
#   ./scripts/install.sh
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_DIR="$(dirname "$SCRIPT_DIR")"
VENV_DIR="${SERVICE_DIR}/venv"
WEIGHTS_DIR="${SERVICE_DIR}/weights"
UPLOADS_DIR="${SERVICE_DIR}/uploads"

echo "============================================================"
echo "AHG AI Condition Service - Installer"
echo "============================================================"
echo "Service directory: ${SERVICE_DIR}"
echo ""

# 1. Create directories
echo "[1/6] Creating directories..."
mkdir -p "${WEIGHTS_DIR}"
mkdir -p "${UPLOADS_DIR}"
mkdir -p "${SERVICE_DIR}/logs"
mkdir -p "${SERVICE_DIR}/runs"
echo "  Done."

# 2. Create virtual environment
echo "[2/6] Creating Python virtual environment..."
if [ -d "${VENV_DIR}" ]; then
    echo "  Virtual environment already exists at ${VENV_DIR}"
else
    python3 -m venv "${VENV_DIR}"
    echo "  Created: ${VENV_DIR}"
fi

# 3. Activate and install dependencies
echo "[3/6] Installing Python dependencies..."
source "${VENV_DIR}/bin/activate"
pip install --upgrade pip setuptools wheel
pip install -r "${SERVICE_DIR}/requirements.txt"
echo "  Done."

# 4. Download YOLOv8n pretrained weights
echo "[4/6] Downloading YOLOv8n pretrained weights..."
YOLO_WEIGHTS="${WEIGHTS_DIR}/yolov8n.pt"
if [ -f "${YOLO_WEIGHTS}" ]; then
    echo "  YOLOv8n weights already exist at ${YOLO_WEIGHTS}"
else
    python3 -c "
from ultralytics import YOLO
import shutil
model = YOLO('yolov8n.pt')
# Model is downloaded to current dir or cache, copy to weights dir
import os
for search_path in ['yolov8n.pt', os.path.expanduser('~/.cache/ultralytics/yolov8n.pt')]:
    if os.path.exists(search_path):
        shutil.copy2(search_path, '${YOLO_WEIGHTS}')
        print(f'  Copied from {search_path} to ${YOLO_WEIGHTS}')
        break
else:
    print('  Warning: Could not locate downloaded weights')
"
    echo "  Done."
fi

# 5. Create MySQL tables (if DB is accessible)
echo "[5/6] Creating database tables..."
python3 -c "
import sys
sys.path.insert(0, '${SERVICE_DIR}')
try:
    from services.storage_service import StorageService
    storage = StorageService()
    import mysql.connector
    conn = mysql.connector.connect(
        host='localhost', user='root', password='', database='archive',
        connection_timeout=5,
    )
    storage._ensure_tables(conn)
    conn.close()
    print('  Database tables created successfully.')
except Exception as e:
    print(f'  Warning: Could not create tables: {e}')
    print('  Tables will be created automatically on first request.')
" 2>/dev/null || echo "  Skipped (database not available)"

# 6. Create API client entry for internal use
echo "[6/6] Creating internal API client..."
python3 -c "
import sys
sys.path.insert(0, '${SERVICE_DIR}')
import config
try:
    import mysql.connector
    conn = mysql.connector.connect(
        host='localhost', user='root', password='', database='archive',
        connection_timeout=5,
    )
    cursor = conn.cursor()

    # Create the client table if not exists
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS ahg_ai_service_client (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_name VARCHAR(100) NOT NULL,
            api_key VARCHAR(255) NOT NULL UNIQUE,
            tier VARCHAR(20) DEFAULT \"free\",
            is_active TINYINT(1) DEFAULT 1,
            scans_limit INT DEFAULT 50,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_api_key (api_key),
            INDEX idx_tier (tier)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ''')

    # Check if internal client exists
    cursor.execute(
        'SELECT id FROM ahg_ai_service_client WHERE client_name = %s',
        ('ahg-condition-internal',)
    )
    if cursor.fetchone() is None:
        cursor.execute(
            '''INSERT INTO ahg_ai_service_client
                (client_name, api_key, tier, is_active, scans_limit, description)
            VALUES (%s, %s, %s, 1, 0, %s)''',
            (
                'ahg-condition-internal',
                config.INTERNAL_API_KEY,
                'internal',
                'Internal API client for AtoM condition plugin',
            )
        )
        print('  Created internal API client.')
    else:
        print('  Internal API client already exists.')

    conn.commit()
    cursor.close()
    conn.close()
except Exception as e:
    print(f'  Warning: Could not create API client: {e}')
" 2>/dev/null || echo "  Skipped (database not available)"

echo ""
echo "============================================================"
echo "Installation complete!"
echo "============================================================"
echo ""
echo "To start the service:"
echo "  ./scripts/start.sh"
echo ""
echo "Or manually:"
echo "  source ${VENV_DIR}/bin/activate"
echo "  python main.py"
echo ""
echo "Health check:"
echo "  curl http://localhost:8100/api/v1/health"
echo ""
echo "To install as systemd service:"
echo "  sudo cp systemd/ai-condition.service /etc/systemd/system/"
echo "  sudo systemctl daemon-reload"
echo "  sudo systemctl enable ai-condition"
echo "  sudo systemctl start ai-condition"
echo "============================================================"
