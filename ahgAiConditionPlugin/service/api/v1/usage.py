"""
Usage endpoint.
GET /api/v1/usage - Usage statistics for the authenticated API client.
"""
import logging

from fastapi import APIRouter, Depends, HTTPException

from api.middleware.auth import require_api_key
from services.metering_service import MeteringService

logger = logging.getLogger("ai-condition.usage")

router = APIRouter()


@router.get("/usage")
async def get_usage(
    client: dict = Depends(require_api_key),
):
    """
    Returns usage statistics for the authenticated API client.
    Includes current month scan count, quota, and tier info.
    """
    client_id = client.get("id")
    if not client_id:
        raise HTTPException(status_code=400, detail="Client ID not available.")

    try:
        metering = MeteringService()
        usage = metering.get_usage(client_id)
    except Exception as e:
        logger.error("Failed to retrieve usage for client %s: %s", client_id, e)
        raise HTTPException(
            status_code=500,
            detail=f"Failed to retrieve usage data: {e}",
        )

    return {
        "success": True,
        "client_id": client_id,
        "client_name": client.get("client_name", "unknown"),
        "tier": client.get("tier", "free"),
        "usage": usage,
    }
