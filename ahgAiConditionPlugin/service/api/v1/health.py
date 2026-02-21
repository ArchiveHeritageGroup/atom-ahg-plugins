"""
Health check endpoint.
GET /api/v1/health
"""
import time
from datetime import datetime, timezone

from fastapi import APIRouter

import config

router = APIRouter()


@router.get("/health")
async def health():
    """
    Returns service status, model loaded status, GPU detection, and version.
    No authentication required.
    """
    # Check GPU
    gpu_available = False
    gpu_name = None
    try:
        import torch
        gpu_available = torch.cuda.is_available()
        if gpu_available:
            gpu_name = torch.cuda.get_device_name(0)
    except Exception:
        pass

    # Check model status
    from main import get_detector, get_classifier, get_scorer
    det = get_detector()
    clf = get_classifier()
    scr = get_scorer()

    detector_status = "not_loaded"
    detector_mode = None
    if det is not None:
        if det.model_loaded:
            detector_status = "loaded"
            detector_mode = "custom" if det.using_custom_model else "pretrained_fallback"
        else:
            detector_status = "mock"
            detector_mode = "mock_demo"

    classifier_status = "not_loaded"
    classifier_mode = None
    if clf is not None:
        if clf.model_loaded:
            classifier_status = "loaded"
            classifier_mode = "custom" if clf.using_custom_model else "pretrained_fallback"
        else:
            classifier_status = "fallback"
            classifier_mode = "heuristic"

    # Check DB connectivity
    db_connected = False
    try:
        import mysql.connector
        conn = mysql.connector.connect(
            host=config.DB_HOST,
            port=config.DB_PORT,
            user=config.DB_USER,
            password=config.DB_PASSWORD,
            database=config.DB_NAME,
            connection_timeout=3,
        )
        conn.close()
        db_connected = True
    except Exception:
        pass

    return {
        "success": True,
        "status": "ok",
        "version": config.SERVICE_VERSION,
        "service": config.SERVICE_NAME,
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "models": {
            "detector": {
                "status": detector_status,
                "mode": detector_mode,
            },
            "classifier": {
                "status": classifier_status,
                "mode": classifier_mode,
            },
            "scorer": {
                "status": "ready" if scr is not None else "not_loaded",
            },
        },
        "gpu": {
            "available": gpu_available,
            "device": gpu_name,
        },
        "database": {
            "connected": db_connected,
        },
        "damage_classes": config.DAMAGE_CLASSES,
        "damage_class_count": len(config.DAMAGE_CLASSES),
    }
