#!/bin/bash
# =============================================================================
# AHG AI Condition Service - Start Script
# =============================================================================
# Starts the FastAPI service using uvicorn.
#
# Usage:
#   ./scripts/start.sh          # Foreground
#   ./scripts/start.sh daemon   # Background (daemon mode)
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_DIR="$(dirname "$SCRIPT_DIR")"
VENV_DIR="${SERVICE_DIR}/venv"
LOG_DIR="${SERVICE_DIR}/logs"
PID_FILE="${SERVICE_DIR}/logs/ai-condition.pid"
LOG_FILE="${LOG_DIR}/ai-condition.log"

# Ensure log directory exists
mkdir -p "${LOG_DIR}"

# Activate virtual environment
if [ ! -d "${VENV_DIR}" ]; then
    echo "Error: Virtual environment not found at ${VENV_DIR}"
    echo "Run ./scripts/install.sh first."
    exit 1
fi
source "${VENV_DIR}/bin/activate"

# Change to service directory
cd "${SERVICE_DIR}"

# Check if already running
if [ -f "${PID_FILE}" ]; then
    OLD_PID=$(cat "${PID_FILE}")
    if kill -0 "${OLD_PID}" 2>/dev/null; then
        echo "Service already running (PID: ${OLD_PID})"
        echo "Use ./scripts/stop.sh to stop it first."
        exit 1
    else
        rm -f "${PID_FILE}"
    fi
fi

if [ "$1" = "daemon" ] || [ "$1" = "-d" ] || [ "$1" = "bg" ]; then
    echo "Starting AI Condition Service in background..."
    nohup python -m uvicorn main:app \
        --host 0.0.0.0 \
        --port 8100 \
        --workers 1 \
        --log-level info \
        >> "${LOG_FILE}" 2>&1 &

    echo $! > "${PID_FILE}"
    echo "Started (PID: $(cat ${PID_FILE}))"
    echo "Log: ${LOG_FILE}"
    echo "PID file: ${PID_FILE}"

    # Wait a moment and check health
    sleep 3
    if curl -s http://localhost:8100/api/v1/health > /dev/null 2>&1; then
        echo "Health check: OK"
    else
        echo "Warning: Health check failed (service may still be starting)"
    fi
else
    echo "Starting AI Condition Service (foreground)..."
    echo "Press Ctrl+C to stop."
    echo ""
    python -m uvicorn main:app \
        --host 0.0.0.0 \
        --port 8100 \
        --workers 1 \
        --log-level info
fi
