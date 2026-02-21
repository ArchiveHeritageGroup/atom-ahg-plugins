#!/bin/bash
# =============================================================================
# AHG AI Condition Service - Stop Script
# =============================================================================
# Stops the running FastAPI service.
#
# Usage:
#   ./scripts/stop.sh
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_DIR="$(dirname "$SCRIPT_DIR")"
PID_FILE="${SERVICE_DIR}/logs/ai-condition.pid"

if [ ! -f "${PID_FILE}" ]; then
    echo "PID file not found: ${PID_FILE}"
    echo "Service may not be running (or was started via systemd)."

    # Try to find the process anyway
    PID=$(pgrep -f "uvicorn main:app.*8100" 2>/dev/null || true)
    if [ -n "${PID}" ]; then
        echo "Found running process: PID ${PID}"
        echo -n "Stop it? [y/N]: "
        read -r CONFIRM
        if [ "$CONFIRM" = "y" ] || [ "$CONFIRM" = "Y" ]; then
            kill "${PID}"
            echo "Sent SIGTERM to PID ${PID}"
        fi
    else
        echo "No running process found on port 8100."
    fi
    exit 0
fi

PID=$(cat "${PID_FILE}")

if kill -0 "${PID}" 2>/dev/null; then
    echo "Stopping AI Condition Service (PID: ${PID})..."
    kill "${PID}"

    # Wait for graceful shutdown (up to 10 seconds)
    for i in $(seq 1 10); do
        if ! kill -0 "${PID}" 2>/dev/null; then
            break
        fi
        sleep 1
    done

    # Force kill if still running
    if kill -0 "${PID}" 2>/dev/null; then
        echo "Forcing shutdown..."
        kill -9 "${PID}" 2>/dev/null
    fi

    rm -f "${PID_FILE}"
    echo "Service stopped."
else
    echo "Process ${PID} not found (already stopped)."
    rm -f "${PID_FILE}"
fi
