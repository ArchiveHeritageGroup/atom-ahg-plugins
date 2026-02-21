"""
Metering Service - Usage tracking and quota enforcement.

Tracks scan usage per API client against the ahg_ai_service_usage table
and enforces monthly quotas based on client tier.
"""
import logging
from datetime import datetime, timezone
from typing import Optional

import mysql.connector

import config

logger = logging.getLogger("ai-condition.metering")


class MeteringService:
    """
    Usage metering and quota management for API clients.
    """

    def _get_connection(self):
        """Create a MySQL connection."""
        return mysql.connector.connect(
            host=config.DB_HOST,
            port=config.DB_PORT,
            user=config.DB_USER,
            password=config.DB_PASSWORD,
            database=config.DB_NAME,
            connection_timeout=10,
        )

    def _ensure_table(self, conn) -> None:
        """
        Create the usage tracking table if it does not exist.
        This is separate from the existing ahg_ai_usage table (which is
        for the NER/translation service). This tracks condition scans.
        """
        cursor = conn.cursor()
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS ahg_ai_condition_usage (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                year_month VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
                scans_used INT DEFAULT 0,
                last_scan_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_client_month (client_id, year_month),
                INDEX idx_year_month (year_month)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """)
        conn.commit()
        cursor.close()

    def _current_month(self) -> str:
        """Return current year-month string (YYYY-MM)."""
        return datetime.now(timezone.utc).strftime("%Y-%m")

    def increment_usage(self, client_id: int) -> bool:
        """
        Increment the scan counter for a client in the current month.

        Args:
            client_id: The API client ID.

        Returns:
            True if usage was incremented successfully.
        """
        if not client_id:
            return False

        conn = None
        try:
            conn = self._get_connection()
            self._ensure_table(conn)
            cursor = conn.cursor()

            year_month = self._current_month()
            now = datetime.now(timezone.utc)

            # Use INSERT ... ON DUPLICATE KEY UPDATE for atomic upsert
            cursor.execute(
                """
                INSERT INTO ahg_ai_condition_usage
                    (client_id, year_month, scans_used, last_scan_at)
                VALUES (%s, %s, 1, %s)
                ON DUPLICATE KEY UPDATE
                    scans_used = scans_used + 1,
                    last_scan_at = %s
                """,
                (client_id, year_month, now, now),
            )
            conn.commit()
            cursor.close()
            return True

        except Exception as e:
            logger.error("Failed to increment usage for client %s: %s", client_id, e)
            return False
        finally:
            if conn:
                conn.close()

    def check_quota(self, client_id: int, limit: int = None) -> bool:
        """
        Check if a client is within their monthly quota.

        Args:
            client_id: The API client ID.
            limit: Override monthly limit (default: look up from tier).

        Returns:
            True if the client is within their quota.
        """
        if not client_id:
            return True

        if limit is not None and limit == 0:
            # Unlimited
            return True

        conn = None
        try:
            conn = self._get_connection()
            self._ensure_table(conn)
            cursor = conn.cursor(dictionary=True)

            year_month = self._current_month()

            cursor.execute(
                """
                SELECT scans_used
                FROM ahg_ai_condition_usage
                WHERE client_id = %s AND year_month = %s
                """,
                (client_id, year_month),
            )
            row = cursor.fetchone()
            cursor.close()

            if row is None:
                # No usage this month yet
                return True

            scans_used = row.get("scans_used", 0)

            # If limit not specified, use default free tier
            if limit is None:
                limit = config.RATE_LIMITS.get("free", 50)

            if limit == 0:
                return True  # Unlimited

            return scans_used < limit

        except Exception as e:
            logger.error("Failed to check quota for client %s: %s", client_id, e)
            # On error, allow the request
            return True
        finally:
            if conn:
                conn.close()

    def get_usage(self, client_id: int) -> Optional[dict]:
        """
        Get current month usage statistics for a client.

        Args:
            client_id: The API client ID.

        Returns:
            Dict with scans_used, year_month, last_scan_at, or None.
        """
        if not client_id:
            return None

        conn = None
        try:
            conn = self._get_connection()
            self._ensure_table(conn)
            cursor = conn.cursor(dictionary=True)

            year_month = self._current_month()

            cursor.execute(
                """
                SELECT scans_used, year_month, last_scan_at, created_at
                FROM ahg_ai_condition_usage
                WHERE client_id = %s AND year_month = %s
                """,
                (client_id, year_month),
            )
            row = cursor.fetchone()
            cursor.close()

            if row is None:
                return {
                    "scans_used": 0,
                    "year_month": year_month,
                    "last_scan_at": None,
                }

            # Convert datetimes to strings
            for key in ("last_scan_at", "created_at"):
                if row.get(key) and hasattr(row[key], "isoformat"):
                    row[key] = row[key].isoformat()

            return row

        except Exception as e:
            logger.error("Failed to get usage for client %s: %s", client_id, e)
            return None
        finally:
            if conn:
                conn.close()

    def reset_monthly(self) -> int:
        """
        Reset monthly counters. Called by a cron job at the start of each month.
        Does NOT delete history; the unique key (client_id, year_month)
        naturally separates months. This method is a no-op since each month
        starts fresh. Provided for explicit cleanup of very old records.

        Returns:
            Number of old records cleaned up (>12 months old).
        """
        conn = None
        try:
            conn = self._get_connection()
            cursor = conn.cursor()

            # Clean up records older than 12 months
            cursor.execute(
                """
                DELETE FROM ahg_ai_condition_usage
                WHERE year_month < DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%%Y-%%m')
                """
            )
            deleted = cursor.rowcount
            conn.commit()
            cursor.close()

            if deleted > 0:
                logger.info("Cleaned up %d old usage records", deleted)
            return deleted

        except Exception as e:
            logger.error("Failed to reset monthly usage: %s", e)
            return 0
        finally:
            if conn:
                conn.close()
