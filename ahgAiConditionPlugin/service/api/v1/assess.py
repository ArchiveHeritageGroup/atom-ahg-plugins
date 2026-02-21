"""
Assessment endpoint.
POST /api/v1/assess - AI-powered condition assessment of archival materials.

Accepts either:
  - JSON body with base64-encoded image
  - Multipart form with file upload
"""
import base64
import logging
import time
from datetime import datetime, timezone
from typing import Optional

from fastapi import APIRouter, Depends, File, Form, HTTPException, Request, UploadFile
from pydantic import BaseModel, Field

import config
from api.middleware.auth import require_api_key, get_client_info
from api.middleware.rate_limit import check_rate_limit
from services.assessment_service import AssessmentService
from services.image_processor import preprocess_for_detection

logger = logging.getLogger("ai-condition.assess")

router = APIRouter()


# =============================================================================
# Request / Response Models
# =============================================================================
class AssessRequestJSON(BaseModel):
    """JSON request body for assessment."""
    image_base64: str = Field(..., description="Base64-encoded image data")
    object_id: Optional[int] = Field(None, description="AtoM information_object ID")
    object_type: Optional[str] = Field(
        "document", description="Material type: book, document, photograph, artifact"
    )
    store: bool = Field(True, description="Store result in database")
    include_overlay: bool = Field(
        True, description="Include annotated image in response"
    )


class DamageItem(BaseModel):
    damage_type: str
    confidence: float
    severity: str
    bbox: dict
    area_percentage: float
    description: str


class AssessmentResponse(BaseModel):
    success: bool
    assessment_id: Optional[int] = None
    object_id: Optional[int] = None
    damages: list[DamageItem]
    damage_count: int
    overall_score: float
    condition_grade: str
    recommendations: list[str]
    overlay_base64: Optional[str] = None
    processing_time_ms: int
    model_info: dict


# =============================================================================
# JSON Endpoint
# =============================================================================
@router.post("/assess", response_model=AssessmentResponse)
async def assess_json(
    request: Request,
    body: AssessRequestJSON,
    client: dict = Depends(require_api_key),
    _rate: None = Depends(check_rate_limit),
):
    """
    Assess condition from a base64-encoded image (JSON body).
    Requires X-API-Key header.
    """
    start_time = time.time()

    # Decode base64 image
    try:
        # Strip optional data URI prefix
        image_data = body.image_base64
        if "," in image_data:
            image_data = image_data.split(",", 1)[1]
        image_bytes = base64.b64decode(image_data)
    except Exception as e:
        raise HTTPException(
            status_code=400,
            detail=f"Invalid base64 image data: {e}",
        )

    if len(image_bytes) > config.MAX_IMAGE_SIZE:
        raise HTTPException(
            status_code=400,
            detail=f"Image too large ({len(image_bytes)} bytes). Maximum: {config.MAX_IMAGE_SIZE} bytes.",
        )

    # Run assessment
    result = await _run_assessment(
        image_bytes=image_bytes,
        object_id=body.object_id,
        object_type=body.object_type or "document",
        store=body.store,
        include_overlay=body.include_overlay,
        client=client,
        start_time=start_time,
    )
    return result


# =============================================================================
# Multipart Upload Endpoint
# =============================================================================
@router.post("/assess/upload", response_model=AssessmentResponse)
async def assess_upload(
    request: Request,
    file: UploadFile = File(..., description="Image file to assess"),
    object_id: Optional[int] = Form(None, description="AtoM information_object ID"),
    object_type: Optional[str] = Form("document", description="Material type"),
    store: bool = Form(True, description="Store result in database"),
    include_overlay: bool = Form(True, description="Include annotated overlay"),
    client: dict = Depends(require_api_key),
    _rate: None = Depends(check_rate_limit),
):
    """
    Assess condition from an uploaded image file.
    Requires X-API-Key header.
    """
    start_time = time.time()

    # Validate file extension
    import os
    ext = os.path.splitext(file.filename or "")[1].lower()
    if ext not in config.ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=400,
            detail=f"Unsupported file type: {ext}. Allowed: {', '.join(config.ALLOWED_EXTENSIONS)}",
        )

    # Read file bytes
    image_bytes = await file.read()
    if len(image_bytes) > config.MAX_IMAGE_SIZE:
        raise HTTPException(
            status_code=400,
            detail=f"Image too large ({len(image_bytes)} bytes). Maximum: {config.MAX_IMAGE_SIZE} bytes.",
        )

    if not image_bytes:
        raise HTTPException(status_code=400, detail="Empty file uploaded.")

    # Run assessment
    result = await _run_assessment(
        image_bytes=image_bytes,
        object_id=object_id,
        object_type=object_type or "document",
        store=store,
        include_overlay=include_overlay,
        client=client,
        start_time=start_time,
    )
    return result


# =============================================================================
# Shared Assessment Logic
# =============================================================================
async def _run_assessment(
    image_bytes: bytes,
    object_id: Optional[int],
    object_type: str,
    store: bool,
    include_overlay: bool,
    client: dict,
    start_time: float,
) -> dict:
    """Run the full assessment pipeline and return the response dict."""
    from main import get_detector, get_classifier, get_scorer

    det = get_detector()
    clf = get_classifier()
    scr = get_scorer()

    if det is None or clf is None or scr is None:
        raise HTTPException(
            status_code=503,
            detail="Models not loaded. Service is starting up or encountered an error.",
        )

    try:
        service = AssessmentService(
            detector=det,
            classifier=clf,
            scorer=scr,
        )
        result = service.assess(
            image_bytes=image_bytes,
            object_id=object_id,
            object_type=object_type,
            store=store,
            include_overlay=include_overlay,
            client_id=client.get("id"),
        )
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        logger.error("Assessment failed: %s", e, exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Assessment processing error: {e}",
        )

    processing_time = int((time.time() - start_time) * 1000)

    # Track usage
    try:
        from services.metering_service import MeteringService
        metering = MeteringService()
        metering.increment_usage(client.get("id"))
    except Exception as e:
        logger.warning("Usage tracking failed: %s", e)

    return {
        "success": True,
        "assessment_id": result.get("assessment_id"),
        "object_id": object_id,
        "damages": result.get("damages", []),
        "damage_count": len(result.get("damages", [])),
        "overall_score": result.get("overall_score", 100.0),
        "condition_grade": result.get("condition_grade", "excellent"),
        "recommendations": result.get("recommendations", []),
        "overlay_base64": result.get("overlay_base64") if include_overlay else None,
        "processing_time_ms": processing_time,
        "model_info": {
            "detector": "yolov8" if det.model_loaded else "mock_demo",
            "classifier": "efficientnet" if clf.model_loaded else "heuristic",
            "custom_trained": det.using_custom_model,
        },
    }
