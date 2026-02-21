"""
API v1 Router - aggregates all v1 endpoint routers.
"""
from fastapi import APIRouter

from api.v1.health import router as health_router
from api.v1.assess import router as assess_router
from api.v1.report import router as report_router
from api.v1.usage import router as usage_router
from api.v1.training import router as training_router

v1_router = APIRouter()

v1_router.include_router(health_router, tags=["Health"])
v1_router.include_router(assess_router, tags=["Assessment"])
v1_router.include_router(report_router, tags=["Reports"])
v1_router.include_router(usage_router, tags=["Usage"])
v1_router.include_router(training_router, tags=["Training"])
