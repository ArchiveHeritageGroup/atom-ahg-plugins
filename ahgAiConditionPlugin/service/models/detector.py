"""
Damage Detector - YOLOv8 wrapper for archival material damage detection.

15 damage classes:
  tear, stain, foxing, fading, water_damage, mold, pest_damage,
  abrasion, brittleness, loss, discoloration, warping, cracking,
  delamination, corrosion

If no custom-trained model is available, falls back to YOLOv8n pretrained
on COCO with a mock mapping from COCO classes to damage types for demo.
"""
import logging
import os
import random
from typing import Optional

import numpy as np
from PIL import Image

import config

logger = logging.getLogger("ai-condition.detector")

# Mock mapping from COCO class IDs to damage types (for demo when no custom model)
# We use a deterministic but varied mapping so demo results look realistic.
COCO_TO_DAMAGE_MOCK = {
    0: "stain",         # person -> stain (shape-like region)
    1: "tear",          # bicycle -> tear
    2: "water_damage",  # car -> water damage
    3: "foxing",        # motorbike -> foxing
    5: "mold",          # bus -> mold
    7: "pest_damage",   # truck -> pest damage
    15: "fading",       # cat -> fading
    16: "abrasion",     # dog -> abrasion
    24: "loss",         # backpack -> loss
    25: "discoloration", # umbrella -> discoloration
    26: "warping",      # handbag -> warping
    39: "cracking",     # bottle -> cracking
    41: "delamination", # cup -> delamination
    56: "corrosion",    # chair -> corrosion
    62: "brittleness",  # tv -> brittleness
}


class DamageDetector:
    """
    YOLOv8-based damage detector for archival materials.
    Wraps ultralytics YOLO model for inference.
    """

    def __init__(self):
        self.model = None
        self.model_loaded = False
        self.using_custom_model = False
        self._model_path = None

    def load(self, model_path: str) -> bool:
        """
        Load the YOLO model from the given path.
        Falls back to pretrained yolov8n.pt if custom model not found.

        Args:
            model_path: Path to custom-trained .pt file.

        Returns:
            True if a model was loaded (custom or pretrained fallback).
        """
        try:
            from ultralytics import YOLO

            if os.path.exists(model_path):
                self.model = YOLO(model_path)
                self.model_loaded = True
                self.using_custom_model = True
                self._model_path = model_path
                logger.info("Loaded custom damage detector: %s", model_path)
                return True

            # Fall back to pretrained YOLOv8n (COCO)
            logger.warning(
                "Custom model not found at %s, using pretrained %s",
                model_path,
                config.YOLO_PRETRAINED,
            )
            pretrained_path = os.path.join(config.WEIGHTS_DIR, config.YOLO_PRETRAINED)
            if os.path.exists(pretrained_path):
                self.model = YOLO(pretrained_path)
            else:
                # ultralytics will auto-download
                self.model = YOLO(config.YOLO_PRETRAINED)
            self.model_loaded = True
            self.using_custom_model = False
            self._model_path = config.YOLO_PRETRAINED
            logger.info("Using pretrained fallback model: %s", config.YOLO_PRETRAINED)
            return True

        except ImportError:
            logger.error("ultralytics package not installed")
            self.model_loaded = False
            return False
        except Exception as e:
            logger.error("Failed to load YOLO model: %s", e)
            self.model_loaded = False
            return False

    def detect(
        self,
        image: Image.Image,
        confidence: float = None,
    ) -> list[dict]:
        """
        Run damage detection on an image.

        Args:
            image: PIL Image to analyze.
            confidence: Minimum confidence threshold (default from config).

        Returns:
            List of detection dicts with keys:
              - damage_type: str
              - confidence: float
              - bbox: dict with x1, y1, x2, y2 (pixel coordinates)
              - area_percentage: float (percentage of total image area)
              - class_id: int (original model class ID)
        """
        if confidence is None:
            confidence = config.DETECTION_CONFIDENCE

        if not self.model_loaded or self.model is None:
            return self._mock_detect(image, confidence)

        try:
            # Run YOLOv8 inference
            results = self.model.predict(
                source=image,
                conf=confidence,
                imgsz=config.IMAGE_DETECT_SIZE,
                verbose=False,
            )

            detections = []
            img_w, img_h = image.size
            img_area = img_w * img_h

            for result in results:
                if result.boxes is None:
                    continue

                boxes = result.boxes
                for i in range(len(boxes)):
                    box = boxes[i]
                    x1, y1, x2, y2 = box.xyxy[0].tolist()
                    conf = float(box.conf[0])
                    cls_id = int(box.cls[0])

                    # Map class to damage type
                    if self.using_custom_model:
                        # Custom model: class IDs directly map to DAMAGE_CLASSES
                        if cls_id < len(config.DAMAGE_CLASSES):
                            damage_type = config.DAMAGE_CLASSES[cls_id]
                        else:
                            damage_type = "unknown"
                    else:
                        # Pretrained COCO model: mock mapping
                        damage_type = COCO_TO_DAMAGE_MOCK.get(cls_id)
                        if damage_type is None:
                            # Skip non-mapped COCO classes
                            continue

                    # Calculate area percentage
                    box_area = abs(x2 - x1) * abs(y2 - y1)
                    area_pct = (box_area / img_area) * 100.0 if img_area > 0 else 0.0

                    detections.append({
                        "damage_type": damage_type,
                        "confidence": round(conf, 4),
                        "bbox": {
                            "x1": round(x1, 1),
                            "y1": round(y1, 1),
                            "x2": round(x2, 1),
                            "y2": round(y2, 1),
                        },
                        "area_percentage": round(area_pct, 2),
                        "class_id": cls_id,
                    })

            # Sort by confidence descending
            detections.sort(key=lambda d: d["confidence"], reverse=True)
            logger.info("Detected %d damage regions", len(detections))
            return detections

        except Exception as e:
            logger.error("Detection inference error: %s", e)
            return self._mock_detect(image, confidence)

    def _mock_detect(self, image: Image.Image, confidence: float) -> list[dict]:
        """
        Generate mock detections for demo purposes when no model is available.
        Produces 1-4 plausible-looking detections based on image characteristics.
        """
        img_w, img_h = image.size
        img_area = img_w * img_h

        # Use image hash for deterministic but varied results
        img_array = np.array(image.resize((32, 32)))
        seed = int(np.sum(img_array)) % 10000
        rng = random.Random(seed)

        num_detections = rng.randint(1, 4)
        damage_types = rng.sample(config.DAMAGE_CLASSES, min(num_detections, len(config.DAMAGE_CLASSES)))

        detections = []
        for damage_type in damage_types:
            # Generate a plausible bounding box
            box_w = rng.uniform(0.05, 0.35) * img_w
            box_h = rng.uniform(0.05, 0.35) * img_h
            x1 = rng.uniform(0, img_w - box_w)
            y1 = rng.uniform(0, img_h - box_h)
            x2 = x1 + box_w
            y2 = y1 + box_h

            conf = rng.uniform(max(confidence, 0.30), 0.92)
            box_area = box_w * box_h
            area_pct = (box_area / img_area) * 100.0 if img_area > 0 else 0.0

            detections.append({
                "damage_type": damage_type,
                "confidence": round(conf, 4),
                "bbox": {
                    "x1": round(x1, 1),
                    "y1": round(y1, 1),
                    "x2": round(x2, 1),
                    "y2": round(y2, 1),
                },
                "area_percentage": round(area_pct, 2),
                "class_id": config.DAMAGE_CLASSES.index(damage_type),
            })

        detections.sort(key=lambda d: d["confidence"], reverse=True)
        logger.info("Generated %d mock detections (demo mode)", len(detections))
        return detections
