"""
Damage Classifier - EfficientNet-B0 based classifier for damage type refinement.

Takes a cropped image region (from the detector) and classifies the type
of damage. If no fine-tuned model is available, uses a heuristic based on
color and texture analysis.
"""
import logging
import os
from typing import Optional

import numpy as np
from PIL import Image

import config

logger = logging.getLogger("ai-condition.classifier")


class DamageClassifier:
    """
    EfficientNet-B0 based damage type classifier.
    Classifies cropped damage regions into 15 damage types.
    """

    def __init__(self):
        self.model = None
        self.model_loaded = False
        self.using_custom_model = False
        self._transform = None
        self._device = None

    def load(self, model_path: str) -> bool:
        """
        Load the classifier model.
        If a custom fine-tuned model exists at model_path, load it.
        Otherwise, load pretrained EfficientNet-B0 (heuristic mode).

        Args:
            model_path: Path to fine-tuned .pt file.

        Returns:
            True if model was loaded successfully.
        """
        try:
            import torch
            import torchvision.transforms as transforms
            from torchvision.models import efficientnet_b0, EfficientNet_B0_Weights

            self._device = torch.device(
                "cuda" if torch.cuda.is_available() else "cpu"
            )

            # Standard EfficientNet-B0 preprocessing
            self._transform = transforms.Compose([
                transforms.Resize((config.IMAGE_CLASSIFY_SIZE, config.IMAGE_CLASSIFY_SIZE)),
                transforms.ToTensor(),
                transforms.Normalize(
                    mean=[0.485, 0.456, 0.406],
                    std=[0.229, 0.224, 0.225],
                ),
            ])

            if os.path.exists(model_path):
                # Load custom fine-tuned model
                self.model = efficientnet_b0(weights=None)
                # Replace classifier head for 15 damage classes
                in_features = self.model.classifier[1].in_features
                self.model.classifier[1] = torch.nn.Linear(
                    in_features, len(config.DAMAGE_CLASSES)
                )
                state_dict = torch.load(model_path, map_location=self._device)
                self.model.load_state_dict(state_dict)
                self.model.to(self._device)
                self.model.eval()
                self.model_loaded = True
                self.using_custom_model = True
                logger.info("Loaded custom classifier: %s", model_path)
                return True

            # Load pretrained EfficientNet-B0 (for feature extraction / heuristic)
            self.model = efficientnet_b0(weights=EfficientNet_B0_Weights.DEFAULT)
            self.model.to(self._device)
            self.model.eval()
            self.model_loaded = True
            self.using_custom_model = False
            logger.info("Using pretrained EfficientNet-B0 (heuristic mode)")
            return True

        except ImportError:
            logger.error("torchvision not installed, classifier unavailable")
            self.model_loaded = False
            return False
        except Exception as e:
            logger.error("Failed to load classifier: %s", e)
            self.model_loaded = False
            return False

    def classify(
        self,
        image_crop: Image.Image,
        detector_hint: Optional[str] = None,
    ) -> dict:
        """
        Classify a cropped damage region.

        Args:
            image_crop: PIL Image of the cropped damage region.
            detector_hint: Optional damage type from the detector for refinement.

        Returns:
            Dict with:
              - damage_type: str
              - confidence: float
              - all_scores: dict of damage_type -> confidence
        """
        if self.model_loaded and self.using_custom_model:
            return self._classify_with_model(image_crop)

        # Heuristic fallback based on color/texture analysis
        return self._classify_heuristic(image_crop, detector_hint)

    def _classify_with_model(self, image_crop: Image.Image) -> dict:
        """Run classification using the fine-tuned model."""
        try:
            import torch

            # Preprocess
            img_rgb = image_crop.convert("RGB")
            tensor = self._transform(img_rgb).unsqueeze(0).to(self._device)

            # Inference
            with torch.no_grad():
                outputs = self.model(tensor)
                probs = torch.softmax(outputs, dim=1)[0]

            # Build scores dict
            all_scores = {}
            for i, class_name in enumerate(config.DAMAGE_CLASSES):
                all_scores[class_name] = round(float(probs[i]), 4)

            # Top prediction
            top_idx = int(torch.argmax(probs))
            damage_type = config.DAMAGE_CLASSES[top_idx]
            confidence = float(probs[top_idx])

            return {
                "damage_type": damage_type,
                "confidence": round(confidence, 4),
                "all_scores": all_scores,
            }

        except Exception as e:
            logger.error("Model classification failed: %s", e)
            return self._classify_heuristic(image_crop, None)

    def _classify_heuristic(
        self,
        image_crop: Image.Image,
        detector_hint: Optional[str],
    ) -> dict:
        """
        Heuristic damage classification based on color and texture.
        Analyzes the cropped region for color dominance, brightness,
        and variance to estimate the most likely damage type.
        """
        img_rgb = image_crop.convert("RGB")
        img_array = np.array(img_rgb, dtype=np.float32)

        # Basic color statistics
        mean_r = np.mean(img_array[:, :, 0])
        mean_g = np.mean(img_array[:, :, 1])
        mean_b = np.mean(img_array[:, :, 2])
        brightness = np.mean(img_array)
        variance = np.var(img_array)
        std_dev = np.std(img_array)

        # Color ratios
        total = mean_r + mean_g + mean_b + 1e-6
        r_ratio = mean_r / total
        g_ratio = mean_g / total
        b_ratio = mean_b / total

        # Initialize scores for each damage type
        scores = {dt: 0.1 for dt in config.DAMAGE_CLASSES}

        # Heuristic rules based on visual characteristics

        # Brown/yellow spots -> foxing
        if r_ratio > 0.38 and g_ratio > 0.30 and b_ratio < 0.28:
            scores["foxing"] += 0.4
            scores["stain"] += 0.25

        # Dark spots -> mold or stain
        if brightness < 80:
            scores["mold"] += 0.35
            scores["stain"] += 0.3

        # Very bright / washed out -> fading
        if brightness > 200:
            scores["fading"] += 0.45
            scores["discoloration"] += 0.2

        # Blue/purple tint -> water damage
        if b_ratio > 0.37:
            scores["water_damage"] += 0.4

        # High variance -> texture damage (cracking, abrasion)
        if variance > 3000:
            scores["cracking"] += 0.3
            scores["abrasion"] += 0.25
            scores["tear"] += 0.2

        # Low variance -> uniform damage (fading, discoloration)
        if variance < 500:
            scores["fading"] += 0.2
            scores["discoloration"] += 0.25
            scores["brittleness"] += 0.15

        # Green tint -> mold
        if g_ratio > 0.38 and r_ratio < 0.34:
            scores["mold"] += 0.45

        # Reddish -> corrosion
        if r_ratio > 0.42 and g_ratio < 0.30:
            scores["corrosion"] += 0.4

        # Very dark edges with lighter center -> loss
        h, w = img_array.shape[:2]
        if h > 10 and w > 10:
            center_brightness = np.mean(img_array[h//4:3*h//4, w//4:3*w//4])
            edge_brightness = np.mean(img_array[:h//4, :])
            if center_brightness > edge_brightness * 1.5:
                scores["loss"] += 0.3

        # High standard deviation -> pest damage (irregular patterns)
        if std_dev > 60:
            scores["pest_damage"] += 0.2

        # If detector provided a hint, boost that type
        if detector_hint and detector_hint in scores:
            scores[detector_hint] += 0.3

        # Normalize scores to sum to 1.0
        total_score = sum(scores.values())
        if total_score > 0:
            scores = {k: v / total_score for k, v in scores.items()}

        # Find top prediction
        top_type = max(scores, key=scores.get)
        top_confidence = scores[top_type]

        return {
            "damage_type": top_type,
            "confidence": round(top_confidence, 4),
            "all_scores": {k: round(v, 4) for k, v in scores.items()},
        }
