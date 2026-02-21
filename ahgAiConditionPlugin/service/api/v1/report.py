"""
Report endpoint.
GET /api/v1/report/{id} - Retrieve a stored assessment by ID.
GET /api/v1/report/history/{object_id} - Assessment history for an object.
"""
import logging

from fastapi import APIRouter, Depends, HTTPException

from api.middleware.auth import require_api_key
from services.storage_service import StorageService

logger = logging.getLogger("ai-condition.report")

router = APIRouter()


@router.get("/report/{assessment_id}")
async def get_report(
    assessment_id: int,
    client: dict = Depends(require_api_key),
):
    """
    Retrieve a stored condition assessment by ID.
    Returns the full assessment with all detected damages.
    """
    try:
        storage = StorageService()
        assessment = storage.get_assessment(assessment_id)
    except Exception as e:
        logger.error("Failed to retrieve assessment %d: %s", assessment_id, e)
        raise HTTPException(
            status_code=500,
            detail=f"Database error: {e}",
        )

    if assessment is None:
        raise HTTPException(
            status_code=404,
            detail=f"Assessment {assessment_id} not found.",
        )

    return {
        "success": True,
        "assessment": assessment,
    }


@router.get("/report/history/{object_id}")
async def get_history(
    object_id: int,
    limit: int = 20,
    offset: int = 0,
    client: dict = Depends(require_api_key),
):
    """
    Retrieve assessment history for a specific information object.
    Returns assessments in reverse chronological order.
    """
    if limit < 1 or limit > 100:
        raise HTTPException(
            status_code=400,
            detail="Limit must be between 1 and 100.",
        )

    try:
        storage = StorageService()
        assessments = storage.get_history(object_id, limit=limit, offset=offset)
    except Exception as e:
        logger.error("Failed to retrieve history for object %d: %s", object_id, e)
        raise HTTPException(
            status_code=500,
            detail=f"Database error: {e}",
        )

    return {
        "success": True,
        "object_id": object_id,
        "assessments": assessments,
        "count": len(assessments),
        "limit": limit,
        "offset": offset,
    }
