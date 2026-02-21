"""
Storage Service - MySQL storage for condition assessments.

Tables:
  - ahg_ai_condition_assessment: Main assessment records
  - ahg_ai_condition_damage: Individual damage findings per assessment
"""
import json
import logging
from datetime import datetime, timezone
from typing import Optional

import mysql.connector

import config

logger = logging.getLogger("ai-condition.storage")


class StorageService:
    """
    MySQL storage for condition assessment results.
    Uses mysql-connector-python for direct DB access.
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

    def _ensure_tables(self, conn) -> None:
        """
        Create assessment tables if they do not exist.
        Called lazily on first write operation.
        """
        cursor = conn.cursor()

        cursor.execute("""
            CREATE TABLE IF NOT EXISTS ahg_ai_condition_assessment (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                object_id INT NULL COMMENT 'AtoM information_object.id',
                object_type VARCHAR(50) DEFAULT 'document',
                overall_score DECIMAL(5,2) DEFAULT 100.00,
                condition_grade VARCHAR(20) DEFAULT 'excellent',
                damage_count INT DEFAULT 0,
                recommendations JSON NULL,
                client_id INT NULL COMMENT 'ahg_ai_service_client.id',
                assessed_by VARCHAR(100) NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_object_id (object_id),
                INDEX idx_condition_grade (condition_grade),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """)

        cursor.execute("""
            CREATE TABLE IF NOT EXISTS ahg_ai_condition_damage (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                assessment_id BIGINT UNSIGNED NOT NULL,
                damage_type VARCHAR(50) NOT NULL,
                confidence DECIMAL(5,4) DEFAULT 0.0000,
                severity VARCHAR(20) DEFAULT 'unknown',
                bbox_x1 DECIMAL(10,2) DEFAULT 0,
                bbox_y1 DECIMAL(10,2) DEFAULT 0,
                bbox_x2 DECIMAL(10,2) DEFAULT 0,
                bbox_y2 DECIMAL(10,2) DEFAULT 0,
                area_percentage DECIMAL(6,2) DEFAULT 0.00,
                description TEXT NULL,
                deduction DECIMAL(6,2) DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_assessment_id (assessment_id),
                INDEX idx_damage_type (damage_type),
                CONSTRAINT fk_damage_assessment
                    FOREIGN KEY (assessment_id)
                    REFERENCES ahg_ai_condition_assessment(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """)

        conn.commit()
        cursor.close()

    def save_assessment(self, data: dict) -> Optional[int]:
        """
        Save an assessment record to the database.

        Args:
            data: Dict with:
              - object_id: int
              - object_type: str
              - overall_score: float
              - condition_grade: str
              - damage_count: int
              - recommendations: list[str]
              - client_id: int

        Returns:
            Assessment ID, or None if save failed.
        """
        conn = None
        try:
            conn = self._get_connection()
            self._ensure_tables(conn)
            cursor = conn.cursor()

            recommendations_json = json.dumps(
                data.get("recommendations", []), ensure_ascii=False
            )

            cursor.execute(
                """
                INSERT INTO ahg_ai_condition_assessment
                    (object_id, object_type, overall_score, condition_grade,
                     damage_count, recommendations, client_id)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
                """,
                (
                    data.get("object_id"),
                    data.get("object_type", "document"),
                    data.get("overall_score", 100.0),
                    data.get("condition_grade", "excellent"),
                    data.get("damage_count", 0),
                    recommendations_json,
                    data.get("client_id"),
                ),
            )
            conn.commit()
            assessment_id = cursor.lastrowid
            cursor.close()

            logger.info("Saved assessment %d for object %s", assessment_id, data.get("object_id"))
            return assessment_id

        except Exception as e:
            logger.error("Failed to save assessment: %s", e)
            if conn:
                conn.rollback()
            return None
        finally:
            if conn:
                conn.close()

    def save_damages(self, assessment_id: int, damages: list[dict]) -> bool:
        """
        Save individual damage records for an assessment.

        Args:
            assessment_id: The parent assessment ID.
            damages: List of damage dicts.

        Returns:
            True if all damages were saved successfully.
        """
        if not damages:
            return True

        conn = None
        try:
            conn = self._get_connection()
            cursor = conn.cursor()

            for damage in damages:
                bbox = damage.get("bbox", {})
                cursor.execute(
                    """
                    INSERT INTO ahg_ai_condition_damage
                        (assessment_id, damage_type, confidence, severity,
                         bbox_x1, bbox_y1, bbox_x2, bbox_y2,
                         area_percentage, description, deduction)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        assessment_id,
                        damage.get("damage_type", "unknown"),
                        damage.get("confidence", 0),
                        damage.get("severity", "unknown"),
                        bbox.get("x1", 0),
                        bbox.get("y1", 0),
                        bbox.get("x2", 0),
                        bbox.get("y2", 0),
                        damage.get("area_percentage", 0),
                        damage.get("description", ""),
                        damage.get("deduction", 0),
                    ),
                )

            conn.commit()
            cursor.close()
            logger.info("Saved %d damages for assessment %d", len(damages), assessment_id)
            return True

        except Exception as e:
            logger.error("Failed to save damages: %s", e)
            if conn:
                conn.rollback()
            return False
        finally:
            if conn:
                conn.close()

    def get_assessment(self, assessment_id: int) -> Optional[dict]:
        """
        Retrieve an assessment by ID, including all damages.

        Args:
            assessment_id: The assessment ID.

        Returns:
            Assessment dict with damages, or None if not found.
        """
        conn = None
        try:
            conn = self._get_connection()
            cursor = conn.cursor(dictionary=True)

            # Get assessment
            cursor.execute(
                """
                SELECT id, object_id, object_type, overall_score, condition_grade,
                       damage_count, recommendations, client_id, assessed_by,
                       notes, created_at, updated_at
                FROM ahg_ai_condition_assessment
                WHERE id = %s
                """,
                (assessment_id,),
            )
            row = cursor.fetchone()
            if row is None:
                cursor.close()
                return None

            # Parse recommendations JSON
            recs = row.get("recommendations")
            if isinstance(recs, str):
                try:
                    row["recommendations"] = json.loads(recs)
                except Exception:
                    row["recommendations"] = []

            # Convert datetime objects to strings
            for key in ("created_at", "updated_at"):
                if row.get(key) and hasattr(row[key], "isoformat"):
                    row[key] = row[key].isoformat()

            # Get damages
            cursor.execute(
                """
                SELECT id, damage_type, confidence, severity,
                       bbox_x1, bbox_y1, bbox_x2, bbox_y2,
                       area_percentage, description, deduction, created_at
                FROM ahg_ai_condition_damage
                WHERE assessment_id = %s
                ORDER BY deduction DESC
                """,
                (assessment_id,),
            )
            damages = cursor.fetchall()

            # Format damages
            formatted_damages = []
            for d in damages:
                if d.get("created_at") and hasattr(d["created_at"], "isoformat"):
                    d["created_at"] = d["created_at"].isoformat()
                d["bbox"] = {
                    "x1": float(d.pop("bbox_x1", 0)),
                    "y1": float(d.pop("bbox_y1", 0)),
                    "x2": float(d.pop("bbox_x2", 0)),
                    "y2": float(d.pop("bbox_y2", 0)),
                }
                # Convert Decimal to float
                for fk in ("confidence", "area_percentage", "deduction"):
                    if fk in d:
                        d[fk] = float(d[fk])
                formatted_damages.append(d)

            row["damages"] = formatted_damages

            # Convert Decimal to float
            if "overall_score" in row:
                row["overall_score"] = float(row["overall_score"])

            cursor.close()
            return row

        except Exception as e:
            logger.error("Failed to retrieve assessment %d: %s", assessment_id, e)
            return None
        finally:
            if conn:
                conn.close()

    def get_history(
        self,
        object_id: int,
        limit: int = 20,
        offset: int = 0,
    ) -> list[dict]:
        """
        Retrieve assessment history for an information object.

        Args:
            object_id: AtoM information_object ID.
            limit: Maximum results.
            offset: Pagination offset.

        Returns:
            List of assessment summary dicts (without full damage details).
        """
        conn = None
        try:
            conn = self._get_connection()
            cursor = conn.cursor(dictionary=True)

            cursor.execute(
                """
                SELECT id, object_id, object_type, overall_score, condition_grade,
                       damage_count, created_at
                FROM ahg_ai_condition_assessment
                WHERE object_id = %s
                ORDER BY created_at DESC
                LIMIT %s OFFSET %s
                """,
                (object_id, limit, offset),
            )
            rows = cursor.fetchall()

            for row in rows:
                if row.get("created_at") and hasattr(row["created_at"], "isoformat"):
                    row["created_at"] = row["created_at"].isoformat()
                if "overall_score" in row:
                    row["overall_score"] = float(row["overall_score"])

            cursor.close()
            return rows

        except Exception as e:
            logger.error("Failed to retrieve history for object %d: %s", object_id, e)
            return []
        finally:
            if conn:
                conn.close()
