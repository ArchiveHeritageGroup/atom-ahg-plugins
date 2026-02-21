"""
Image Processing Service.
Handles preprocessing, cropping, EXIF orientation, and overlay generation.
"""
import base64
import io
import logging

import numpy as np
from PIL import Image, ImageDraw, ImageFont, ExifTags

import config

logger = logging.getLogger("ai-condition.image_processor")

# Color palette for drawing bounding boxes (R, G, B)
DAMAGE_COLORS = {
    "tear": (220, 20, 60),        # crimson
    "stain": (139, 69, 19),       # saddle brown
    "foxing": (205, 133, 63),     # peru
    "fading": (255, 215, 0),      # gold
    "water_damage": (30, 144, 255),  # dodger blue
    "mold": (0, 128, 0),          # green
    "pest_damage": (255, 69, 0),  # orange red
    "abrasion": (148, 103, 189),  # purple
    "brittleness": (188, 143, 143),  # rosy brown
    "loss": (0, 0, 0),            # black
    "discoloration": (218, 165, 32),  # goldenrod
    "warping": (70, 130, 180),    # steel blue
    "cracking": (178, 34, 34),    # firebrick
    "delamination": (255, 140, 0),  # dark orange
    "corrosion": (85, 107, 47),   # dark olive green
}

DEFAULT_COLOR = (128, 128, 128)


def fix_exif_orientation(image: Image.Image) -> Image.Image:
    """
    Fix image orientation based on EXIF data.
    Many phone cameras store orientation in EXIF rather than rotating pixels.
    """
    try:
        exif = image.getexif()
        if not exif:
            return image

        orientation_key = None
        for k, v in ExifTags.TAGS.items():
            if v == "Orientation":
                orientation_key = k
                break

        if orientation_key is None or orientation_key not in exif:
            return image

        orientation = exif[orientation_key]

        if orientation == 2:
            image = image.transpose(Image.FLIP_LEFT_RIGHT)
        elif orientation == 3:
            image = image.rotate(180, expand=True)
        elif orientation == 4:
            image = image.transpose(Image.FLIP_TOP_BOTTOM)
        elif orientation == 5:
            image = image.transpose(Image.FLIP_LEFT_RIGHT).rotate(270, expand=True)
        elif orientation == 6:
            image = image.rotate(270, expand=True)
        elif orientation == 7:
            image = image.transpose(Image.FLIP_LEFT_RIGHT).rotate(90, expand=True)
        elif orientation == 8:
            image = image.rotate(90, expand=True)

    except Exception as e:
        logger.debug("EXIF orientation fix failed: %s", e)

    return image


def preprocess_for_detection(image_bytes: bytes) -> Image.Image:
    """
    Preprocess an image for damage detection.
    - Decode from bytes
    - Fix EXIF orientation
    - Convert to RGB
    - Resize to detection input size (640x640) preserving aspect ratio with padding

    Args:
        image_bytes: Raw image bytes.

    Returns:
        PIL Image ready for detection.
    """
    image = Image.open(io.BytesIO(image_bytes))
    image = fix_exif_orientation(image)
    image = image.convert("RGB")

    # Resize maintaining aspect ratio, pad to square
    target_size = config.IMAGE_DETECT_SIZE
    w, h = image.size
    scale = min(target_size / w, target_size / h)
    new_w = int(w * scale)
    new_h = int(h * scale)

    image = image.resize((new_w, new_h), Image.LANCZOS)

    # Pad to target size with gray background
    padded = Image.new("RGB", (target_size, target_size), (114, 114, 114))
    pad_x = (target_size - new_w) // 2
    pad_y = (target_size - new_h) // 2
    padded.paste(image, (pad_x, pad_y))

    return padded


def preprocess_for_classification(
    image_bytes: bytes,
    bbox: dict,
) -> Image.Image:
    """
    Preprocess an image region for classification.
    - Decode, fix orientation, convert to RGB
    - Crop to bounding box
    - Resize to classification input size (224x224)

    Args:
        image_bytes: Raw image bytes.
        bbox: Dict with x1, y1, x2, y2 coordinates.

    Returns:
        PIL Image of the cropped, resized region.
    """
    image = Image.open(io.BytesIO(image_bytes))
    image = fix_exif_orientation(image)
    image = image.convert("RGB")

    # Crop to bounding box
    x1 = max(0, int(bbox.get("x1", 0)))
    y1 = max(0, int(bbox.get("y1", 0)))
    x2 = min(image.width, int(bbox.get("x2", image.width)))
    y2 = min(image.height, int(bbox.get("y2", image.height)))

    # Ensure valid crop
    if x2 <= x1 or y2 <= y1:
        logger.warning("Invalid bbox for crop: %s, using full image", bbox)
        cropped = image
    else:
        cropped = image.crop((x1, y1, x2, y2))

    # Resize to classifier input
    target_size = config.IMAGE_CLASSIFY_SIZE
    cropped = cropped.resize((target_size, target_size), Image.LANCZOS)

    return cropped


def get_original_image(image_bytes: bytes) -> Image.Image:
    """
    Load original image with EXIF orientation fix and RGB conversion.
    """
    image = Image.open(io.BytesIO(image_bytes))
    image = fix_exif_orientation(image)
    image = image.convert("RGB")
    return image


def create_overlay(
    image_bytes: bytes,
    damages: list[dict],
) -> str:
    """
    Draw bounding boxes with labels on the original image.

    Args:
        image_bytes: Raw image bytes.
        damages: List of damage dicts with bbox, damage_type, confidence, severity.

    Returns:
        Base64-encoded JPEG string of the annotated image.
    """
    image = get_original_image(image_bytes)
    draw = ImageDraw.Draw(image)

    # Try to load a font, fall back to default
    font = None
    font_small = None
    try:
        font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 16)
        font_small = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 12)
    except Exception:
        try:
            font = ImageFont.truetype("/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf", 16)
            font_small = ImageFont.truetype("/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf", 12)
        except Exception:
            font = ImageFont.load_default()
            font_small = font

    for damage in damages:
        bbox = damage.get("bbox", {})
        x1 = bbox.get("x1", 0)
        y1 = bbox.get("y1", 0)
        x2 = bbox.get("x2", 0)
        y2 = bbox.get("y2", 0)

        damage_type = damage.get("damage_type", "unknown")
        confidence = damage.get("confidence", 0)
        severity = damage.get("severity", "unknown")

        color = DAMAGE_COLORS.get(damage_type, DEFAULT_COLOR)

        # Draw bounding box (3px width for visibility)
        for offset in range(3):
            draw.rectangle(
                [x1 - offset, y1 - offset, x2 + offset, y2 + offset],
                outline=color,
            )

        # Draw label background
        label = f"{damage_type} ({confidence:.0%}) [{severity}]"
        label_bbox = draw.textbbox((x1, y1), label, font=font)
        label_w = label_bbox[2] - label_bbox[0]
        label_h = label_bbox[3] - label_bbox[1]

        # Position label above the box, or inside if at top edge
        label_y = y1 - label_h - 6
        if label_y < 0:
            label_y = y1 + 4

        # Semi-transparent background for label
        draw.rectangle(
            [x1, label_y, x1 + label_w + 8, label_y + label_h + 4],
            fill=color,
        )
        draw.text(
            (x1 + 4, label_y + 2),
            label,
            fill=(255, 255, 255),
            font=font,
        )

    # Encode to base64 JPEG
    buffer = io.BytesIO()
    image.save(buffer, format="JPEG", quality=85)
    buffer.seek(0)
    encoded = base64.b64encode(buffer.getvalue()).decode("utf-8")

    return encoded
