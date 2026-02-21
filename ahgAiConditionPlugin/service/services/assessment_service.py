"""
Assessment Service - Orchestrates the full condition assessment pipeline.

Pipeline:
  1. Preprocess image
  2. Run YOLOv8 detection
  3. Classify each detected region
  4. Score overall condition
  5. Create annotated overlay
  6. Optionally store result in database
"""
import logging
from typing import Optional

from PIL import Image

from models.detector import DamageDetector
from models.classifier import DamageClassifier
from models.scorer import ConditionScorer
from services.image_processor import (
    get_original_image,
    preprocess_for_classification,
    create_overlay,
)

logger = logging.getLogger("ai-condition.assessment")


class AssessmentService:
    """
    Orchestrates the full damage detection, classification, and scoring pipeline.
    """

    def __init__(
        self,
        detector: DamageDetector,
        classifier: DamageClassifier,
        scorer: ConditionScorer,
    ):
        self.detector = detector
        self.classifier = classifier
        self.scorer = scorer

    def assess(
        self,
        image_bytes: bytes,
        object_id: Optional[int] = None,
        object_type: str = "document",
        store: bool = True,
        include_overlay: bool = True,
        client_id: Optional[int] = None,
    ) -> dict:
        """
        Run the full assessment pipeline.

        Args:
            image_bytes: Raw image bytes.
            object_id: AtoM information_object ID (optional).
            object_type: Material type (book, document, photograph, artifact).
            store: Whether to store results in the database.
            include_overlay: Whether to create an annotated overlay image.
            client_id: API client ID for tracking.

        Returns:
            Dict with assessment results:
              - assessment_id: int or None
              - damages: list of damage dicts
              - overall_score: float
              - condition_grade: str
              - recommendations: list[str]
              - overlay_base64: str or None
        """
        # 1. Load original image for detection
        try:
            original_image = get_original_image(image_bytes)
        except Exception as e:
            raise ValueError(f"Failed to load image: {e}")

        logger.info(
            "Starting assessment: image %dx%d, type=%s, object_id=%s",
            original_image.width,
            original_image.height,
            object_type,
            object_id,
        )

        # 2. Run detection
        detections = self.detector.detect(original_image)
        logger.info("Detection found %d regions", len(detections))

        # 3. Classify each detected region
        classified_damages = []
        for detection in detections:
            bbox = detection.get("bbox", {})

            # Classify the cropped region
            try:
                crop = preprocess_for_classification(image_bytes, bbox)
                classification = self.classifier.classify(
                    crop,
                    detector_hint=detection.get("damage_type"),
                )

                # Use classifier result if it has higher confidence,
                # otherwise keep detector result
                if (
                    classification["confidence"] > detection.get("confidence", 0) * 0.8
                    and self.classifier.using_custom_model
                ):
                    final_type = classification["damage_type"]
                    final_confidence = classification["confidence"]
                else:
                    final_type = detection["damage_type"]
                    final_confidence = detection["confidence"]

            except Exception as e:
                logger.warning("Classification failed for region: %s", e)
                final_type = detection["damage_type"]
                final_confidence = detection["confidence"]

            classified_damages.append({
                "damage_type": final_type,
                "confidence": final_confidence,
                "bbox": bbox,
                "area_percentage": detection.get("area_percentage", 0),
            })

        # 4. Score overall condition
        score_result = self.scorer.score(classified_damages)

        # Merge scored damage info back
        damages_output = []
        for scored in score_result.get("damages_scored", []):
            damages_output.append({
                "damage_type": scored["damage_type"],
                "confidence": scored["confidence"],
                "severity": scored.get("severity", "unknown"),
                "bbox": scored.get("bbox", {}),
                "area_percentage": scored.get("area_percentage", 0),
                "description": scored.get("description", ""),
                "deduction": scored.get("deduction", 0),
            })

        # 5. Create overlay if requested
        overlay_base64 = None
        if include_overlay and damages_output:
            try:
                overlay_base64 = create_overlay(image_bytes, damages_output)
            except Exception as e:
                logger.warning("Overlay creation failed: %s", e)

        # 6. Store in database if requested
        assessment_id = None
        if store and object_id:
            try:
                from services.storage_service import StorageService
                storage = StorageService()
                assessment_id = storage.save_assessment({
                    "object_id": object_id,
                    "object_type": object_type,
                    "overall_score": score_result["overall_score"],
                    "condition_grade": score_result["condition_grade"],
                    "damage_count": len(damages_output),
                    "recommendations": score_result.get("recommendations", []),
                    "client_id": client_id,
                })
                if assessment_id:
                    storage.save_damages(assessment_id, damages_output)
                    logger.info("Assessment stored with ID %d", assessment_id)
            except Exception as e:
                logger.warning("Failed to store assessment: %s", e)

        return {
            "assessment_id": assessment_id,
            "damages": damages_output,
            "overall_score": score_result["overall_score"],
            "condition_grade": score_result["condition_grade"],
            "recommendations": score_result.get("recommendations", []),
            "overlay_base64": overlay_base64,
        }
