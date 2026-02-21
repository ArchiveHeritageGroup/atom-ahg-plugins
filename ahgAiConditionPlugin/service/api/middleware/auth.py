"""
API Key Authentication Middleware.
Validates X-API-Key header against the ahg_ai_service_client MySQL table.
Also supports an internal key for the AtoM condition plugin.
"""
import logging
from typing import Optional

from fastapi import Depends, HTTPException, Request

import config

logger = logging.getLogger("ai-condition.auth")


def _get_db_connection():
    """Create a MySQL connection."""
    import mysql.connector
    return mysql.connector.connect(
        host=config.DB_HOST,
        port=config.DB_PORT,
        user=config.DB_USER,
        password=config.DB_PASSWORD,
        database=config.DB_NAME,
        connection_timeout=5,
    )


def _validate_api_key(api_key: str) -> Optional[dict]:
    """
    Validate an API key against the database.
    Returns client info dict if valid, None if invalid.
    """
    # Check internal key first (no DB lookup needed)
    if api_key == config.INTERNAL_API_KEY:
        return {
            "id": 0,
            "client_name": "internal",
            "api_key": api_key,
            "tier": "internal",
            "is_active": True,
        }

    # Look up in ahg_ai_service_client table
    try:
        conn = _get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            """
            SELECT id, name, api_key, tier, is_active,
                   monthly_limit, created_at
            FROM ahg_ai_service_client
            WHERE api_key = %s
            LIMIT 1
            """,
            (api_key,),
        )
        row = cursor.fetchone()
        cursor.close()
        conn.close()

        if row is None:
            return None

        if not row.get("is_active", False):
            return None

        return {
            "id": row["id"],
            "client_name": row.get("name", "unknown"),
            "api_key": row["api_key"],
            "tier": row.get("tier", "free"),
            "is_active": row.get("is_active", True),
            "scans_limit": row.get("monthly_limit", 50),
        }
    except Exception as e:
        logger.error("Database error during API key validation: %s", e)
        # If DB is unavailable but the key matches internal key, allow
        if api_key == config.INTERNAL_API_KEY:
            return {
                "id": 0,
                "client_name": "internal",
                "api_key": api_key,
                "tier": "internal",
                "is_active": True,
            }
        raise HTTPException(
            status_code=503,
            detail="Authentication service temporarily unavailable.",
        )


async def require_api_key(request: Request) -> dict:
    """
    FastAPI dependency that validates the X-API-Key header.
    Returns client info dict on success, raises 401 on failure.
    """
    api_key = request.headers.get("X-API-Key") or request.headers.get("x-api-key")

    if not api_key:
        raise HTTPException(
            status_code=401,
            detail="Missing X-API-Key header.",
        )

    client = _validate_api_key(api_key)
    if client is None:
        raise HTTPException(
            status_code=401,
            detail="Invalid or inactive API key.",
        )

    # Store client info on request state for downstream use
    request.state.client = client
    return client


async def get_client_info(request: Request) -> Optional[dict]:
    """
    Optional dependency to get client info without requiring auth.
    Returns None if no valid API key is provided.
    """
    api_key = request.headers.get("X-API-Key") or request.headers.get("x-api-key")
    if not api_key:
        return None
    return _validate_api_key(api_key)
