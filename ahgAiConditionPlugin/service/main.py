#!/usr/bin/env python3
"""
AHG AI Condition Assessment Service
FastAPI application for AI-powered condition assessment of archival materials.
Uses YOLOv8 for damage detection and EfficientNet for classification.

Port: 8100
Endpoint prefix: /api/v1/
"""
import logging
import traceback
from contextlib import asynccontextmanager

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse

import config
from models.detector import DamageDetector
from models.classifier import DamageClassifier
from models.scorer import ConditionScorer
from api.v1.routes import v1_router

# =============================================================================
# Logging
# =============================================================================
logging.basicConfig(
    level=logging.DEBUG if config.DEBUG else logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger("ai-condition")

# =============================================================================
# Global model instances (loaded at startup)
# =============================================================================
detector: DamageDetector | None = None
classifier: DamageClassifier | None = None
scorer: ConditionScorer | None = None


def get_detector() -> DamageDetector | None:
    return detector


def get_classifier() -> DamageClassifier | None:
    return classifier


def get_scorer() -> ConditionScorer | None:
    return scorer


# =============================================================================
# Lifespan (startup / shutdown)
# =============================================================================
@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load models on startup, clean up on shutdown."""
    global detector, classifier, scorer
    import os

    logger.info("=" * 60)
    logger.info("AI Condition Service v%s starting...", config.SERVICE_VERSION)
    logger.info("=" * 60)

    # Ensure directories exist
    os.makedirs(config.WEIGHTS_DIR, exist_ok=True)
    os.makedirs(config.UPLOAD_DIR, exist_ok=True)

    # Load damage detector (YOLOv8)
    try:
        detector = DamageDetector()
        detector.load(config.YOLO_MODEL_PATH)
        logger.info("Damage detector loaded successfully")
    except Exception as e:
        logger.warning("Failed to load damage detector: %s", e)
        detector = DamageDetector()  # Will use mock detection

    # Load damage classifier (EfficientNet)
    try:
        classifier = DamageClassifier()
        classifier.load(config.CLASSIFIER_MODEL_PATH)
        logger.info("Damage classifier loaded successfully")
    except Exception as e:
        logger.warning("Failed to load damage classifier: %s", e)
        classifier = DamageClassifier()  # Will use fallback

    # Initialize scorer
    scorer = ConditionScorer()
    logger.info("Condition scorer initialized")

    # Check GPU availability
    try:
        import torch
        if torch.cuda.is_available():
            gpu_name = torch.cuda.get_device_name(0)
            logger.info("GPU available: %s", gpu_name)
        else:
            logger.info("No GPU detected, using CPU")
    except Exception:
        logger.info("PyTorch GPU check failed, using CPU")

    logger.info("Service ready on port %d", config.SERVICE_PORT)
    logger.info("=" * 60)

    yield

    # Cleanup
    logger.info("AI Condition Service shutting down...")
    detector = None
    classifier = None
    scorer = None


# =============================================================================
# FastAPI App
# =============================================================================
app = FastAPI(
    title="AHG AI Condition Assessment Service",
    description=(
        "AI-powered condition assessment for archival materials. "
        "Uses YOLOv8 for damage detection and EfficientNet for classification."
    ),
    version=config.SERVICE_VERSION,
    lifespan=lifespan,
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=config.CORS_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Mount v1 API routes
app.include_router(v1_router, prefix="/api/v1")


# =============================================================================
# Exception Handlers
# =============================================================================
@app.exception_handler(Exception)
async def global_exception_handler(request: Request, exc: Exception):
    """Catch-all exception handler for unhandled errors."""
    logger.error(
        "Unhandled exception on %s %s: %s\n%s",
        request.method,
        request.url.path,
        str(exc),
        traceback.format_exc(),
    )
    return JSONResponse(
        status_code=500,
        content={
            "success": False,
            "error": "Internal server error",
            "detail": str(exc) if config.DEBUG else None,
        },
    )


@app.exception_handler(404)
async def not_found_handler(request: Request, exc):
    return JSONResponse(
        status_code=404,
        content={
            "success": False,
            "error": f"Endpoint not found: {request.method} {request.url.path}",
        },
    )


# =============================================================================
# Root redirect
# =============================================================================
@app.get("/", include_in_schema=False)
async def root():
    return {
        "service": config.SERVICE_NAME,
        "version": config.SERVICE_VERSION,
        "docs": "/docs",
        "health": "/api/v1/health",
    }


# =============================================================================
# Entry Point
# =============================================================================
if __name__ == "__main__":
    import uvicorn

    uvicorn.run(
        "main:app",
        host=config.SERVICE_HOST,
        port=config.SERVICE_PORT,
        reload=config.DEBUG,
        log_level="debug" if config.DEBUG else "info",
    )
