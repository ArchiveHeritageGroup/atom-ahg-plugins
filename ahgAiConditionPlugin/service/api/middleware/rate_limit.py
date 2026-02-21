"""
Rate Limiting Middleware.
Per-client rate limiting based on tier:
  free      = 50 scans/month
  standard  = 500 scans/month
  pro       = 5000 scans/month
  enterprise = unlimited
  internal   = unlimited
"""
import logging

from fastapi import Depends, HTTPException, Request

import config
from api.middleware.auth import require_api_key
from services.metering_service import MeteringService

logger = logging.getLogger("ai-condition.ratelimit")


async def check_rate_limit(
    request: Request,
    client: dict = Depends(require_api_key),
) -> None:
    """
    FastAPI dependency that checks per-client rate limits.
    Raises 429 if the client has exceeded their monthly quota.
    """
    tier = client.get("tier", "free")
    limit = config.RATE_LIMITS.get(tier, 50)

    # Unlimited tiers
    if limit == 0:
        return

    client_id = client.get("id")
    if not client_id:
        return

    try:
        metering = MeteringService()
        within_quota = metering.check_quota(client_id, limit)
    except Exception as e:
        # If metering fails, allow the request but log the error
        logger.warning("Rate limit check failed for client %s: %s", client_id, e)
        return

    if not within_quota:
        usage = metering.get_usage(client_id)
        scans_used = usage.get("scans_used", 0) if usage else 0
        raise HTTPException(
            status_code=429,
            detail={
                "error": "Monthly scan quota exceeded",
                "tier": tier,
                "limit": limit,
                "used": scans_used,
                "upgrade_info": "Contact support@theahg.co.za to upgrade your plan.",
            },
        )
