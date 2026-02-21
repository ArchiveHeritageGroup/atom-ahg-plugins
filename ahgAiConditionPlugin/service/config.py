"""
AI Condition Service - Configuration
Centralizes all settings for the condition assessment service.
"""
import os

# =============================================================================
# Service
# =============================================================================
SERVICE_NAME = "ai-condition-service"
SERVICE_VERSION = "1.0.0"
SERVICE_PORT = int(os.getenv("CONDITION_SERVICE_PORT", "8100"))
SERVICE_HOST = os.getenv("CONDITION_SERVICE_HOST", "0.0.0.0")
DEBUG = os.getenv("CONDITION_SERVICE_DEBUG", "0") == "1"

# =============================================================================
# Database (MySQL - same as AtoM)
# =============================================================================
DB_HOST = os.getenv("CONDITION_DB_HOST", "localhost")
DB_PORT = int(os.getenv("CONDITION_DB_PORT", "3306"))
DB_USER = os.getenv("CONDITION_DB_USER", "root")
DB_PASSWORD = os.getenv("CONDITION_DB_PASSWORD", "")
DB_NAME = os.getenv("CONDITION_DB_NAME", "archive")

# =============================================================================
# Model Paths
# =============================================================================
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
WEIGHTS_DIR = os.path.join(BASE_DIR, "weights")
YOLO_MODEL_PATH = os.path.join(WEIGHTS_DIR, "damage_detector.pt")
CLASSIFIER_MODEL_PATH = os.path.join(WEIGHTS_DIR, "damage_classifier.pt")
YOLO_PRETRAINED = "yolov8n.pt"

# =============================================================================
# Upload / Temporary Storage
# =============================================================================
UPLOAD_DIR = os.path.join(BASE_DIR, "uploads")
MAX_IMAGE_SIZE = 20 * 1024 * 1024  # 20 MB
ALLOWED_EXTENSIONS = {".jpg", ".jpeg", ".png", ".tiff", ".tif", ".bmp", ".webp"}

# =============================================================================
# Detection / Classification
# =============================================================================
DETECTION_CONFIDENCE = float(os.getenv("CONDITION_DETECTION_CONF", "0.25"))
CLASSIFICATION_CONFIDENCE = float(os.getenv("CONDITION_CLASSIFICATION_CONF", "0.30"))
IMAGE_DETECT_SIZE = 640   # YOLOv8 input size
IMAGE_CLASSIFY_SIZE = 224  # EfficientNet input size

# Damage classes (15 archival damage types)
DAMAGE_CLASSES = [
    "tear",
    "stain",
    "foxing",
    "fading",
    "water_damage",
    "mold",
    "pest_damage",
    "abrasion",
    "brittleness",
    "loss",
    "discoloration",
    "warping",
    "cracking",
    "delamination",
    "corrosion",
]

# Damage type weights for scoring (structural damages weigh more)
DAMAGE_TYPE_WEIGHTS = {
    "tear": 1.2,
    "stain": 0.8,
    "foxing": 0.8,
    "fading": 0.7,
    "water_damage": 1.3,
    "mold": 1.4,
    "pest_damage": 1.3,
    "abrasion": 0.9,
    "brittleness": 1.1,
    "loss": 1.5,
    "discoloration": 0.6,
    "warping": 1.0,
    "cracking": 1.5,
    "delamination": 1.5,
    "corrosion": 1.4,
}

# Condition grade thresholds
CONDITION_GRADES = {
    "excellent": (90, 100),
    "good": (70, 89),
    "fair": (50, 69),
    "poor": (25, 49),
    "critical": (0, 24),
}

# =============================================================================
# API Auth
# =============================================================================
INTERNAL_API_KEY = os.getenv(
    "CONDITION_INTERNAL_API_KEY", "ahg_ai_condition_internal_2026"
)

# =============================================================================
# Rate Limiting (requests per month by tier)
# =============================================================================
RATE_LIMITS = {
    "free": 50,
    "standard": 500,
    "pro": 5000,
    "enterprise": 0,  # 0 = unlimited
    "internal": 0,    # 0 = unlimited
}

# =============================================================================
# CORS
# =============================================================================
CORS_ORIGINS = ["*"]
