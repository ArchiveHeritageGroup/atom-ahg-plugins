"""
Training management endpoints.

POST   /api/v1/training/upload                       - Upload training dataset (ZIP)
POST   /api/v1/training/start                        - Start model training
GET    /api/v1/training/status                       - Get training status
GET    /api/v1/training/datasets                     - List available datasets
DELETE /api/v1/training/dataset/{id}                 - Delete a dataset
GET    /api/v1/training/model-info                   - Current model information
POST   /api/v1/training/contribute                   - Submit a training contribution
GET    /api/v1/training/contributions                - List training contributions
POST   /api/v1/training/contributions/{id}/approve   - Approve a contribution
POST   /api/v1/training/contributions/{id}/reject    - Reject a contribution
POST   /api/v1/training/build-dataset                - Build dataset from approved contributions
"""
import base64
import io
import json
import logging
import math
import os
import shutil
import subprocess
import uuid
import zipfile
from datetime import datetime, timezone
from enum import Enum
from typing import List, Optional

import mysql.connector
from fastapi import APIRouter, HTTPException, Query, UploadFile, File
from pydantic import BaseModel, Field

import config

logger = logging.getLogger("ai-condition.training")

router = APIRouter()

TRAINING_DATA_DIR = os.path.join(config.BASE_DIR, "training_data")
TRAINING_STATUS_FILE = os.path.join(config.BASE_DIR, "training_status.json")

IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png", ".tiff", ".tif", ".bmp", ".webp"}
ANNOTATION_EXTENSIONS = {".json", ".xml"}


# =============================================================================
# Pydantic Models
# =============================================================================
class TrainingStartRequest(BaseModel):
    """Request body for starting a training run."""
    dataset_id: str = Field(..., description="Dataset upload_id to train on")
    epochs: int = Field(100, ge=1, le=1000, description="Number of training epochs")
    batch_size: int = Field(16, ge=1, le=128, description="Training batch size")


class DatasetInfo(BaseModel):
    """Information about a training dataset."""
    dataset_id: str
    image_count: int
    annotation_count: int
    created_at: str
    size_bytes: int


class TrainingStatus(BaseModel):
    """Current training status."""
    status: str  # idle, preparing, training, completed, failed
    progress: Optional[float] = None
    epoch: Optional[int] = None
    total_epochs: Optional[int] = None
    dataset_id: Optional[str] = None
    metrics: Optional[dict] = None
    started_at: Optional[str] = None
    completed_at: Optional[str] = None
    error: Optional[str] = None


class ContributionSource(str, Enum):
    """Allowed source values for training contributions."""
    condition_photos = "condition_photos"
    annotation_studio = "annotation_studio"
    saas_client = "saas_client"
    manual = "manual"


class BBox(BaseModel):
    """Bounding box coordinates."""
    x1: float = Field(..., description="Left x coordinate")
    y1: float = Field(..., description="Top y coordinate")
    x2: float = Field(..., description="Right x coordinate")
    y2: float = Field(..., description="Bottom y coordinate")


class AnnotationItem(BaseModel):
    """A single damage annotation with type and bounding box."""
    damage_type: str = Field(..., description="Damage type (must be a valid DAMAGE_CLASS)")
    bbox: BBox = Field(..., description="Bounding box for the damage region")


class ContributeRequest(BaseModel):
    """Request body for submitting a training contribution."""
    image_base64: str = Field(..., description="Base64-encoded image data")
    annotations: List[AnnotationItem] = Field(
        ..., min_length=1, description="List of damage annotations"
    )
    source: ContributionSource = Field(
        ..., description="Source of the contribution"
    )
    object_id: Optional[int] = Field(
        None, description="AtoM information_object.id"
    )
    contributor: Optional[str] = Field(
        None, max_length=255, description="Name or identifier of the contributor"
    )
    client_id: Optional[int] = Field(
        None, description="SaaS client ID"
    )


CONTRIBUTIONS_DIR = os.path.join(TRAINING_DATA_DIR, "_contributions")
VALID_CONTRIBUTION_STATUSES = {"pending", "approved", "rejected"}


# =============================================================================
# Helper Functions
# =============================================================================
def _get_training_status() -> dict:
    """Read training status from JSON file."""
    if not os.path.exists(TRAINING_STATUS_FILE):
        return {"status": "idle"}
    try:
        with open(TRAINING_STATUS_FILE, "r") as f:
            return json.load(f)
    except (json.JSONDecodeError, IOError):
        return {"status": "idle"}


def _set_training_status(status: dict):
    """Write training status to JSON file."""
    with open(TRAINING_STATUS_FILE, "w") as f:
        json.dump(status, f, indent=2)


def _count_files_by_extension(directory: str, extensions: set) -> int:
    """Count files matching given extensions in a directory."""
    if not os.path.isdir(directory):
        return 0
    count = 0
    for fname in os.listdir(directory):
        ext = os.path.splitext(fname)[1].lower()
        if ext in extensions:
            count += 1
    return count


def _get_dir_size(directory: str) -> int:
    """Calculate total size of a directory in bytes."""
    total = 0
    for dirpath, _dirnames, filenames in os.walk(directory):
        for fname in filenames:
            fpath = os.path.join(dirpath, fname)
            try:
                total += os.path.getsize(fpath)
            except OSError:
                pass
    return total


def _get_db_connection():
    """Create a MySQL connection using config settings."""
    return mysql.connector.connect(
        host=config.DB_HOST,
        port=config.DB_PORT,
        user=config.DB_USER,
        password=config.DB_PASSWORD,
        database=config.DB_NAME,
        connection_timeout=10,
    )


def _ensure_contribution_table(conn) -> None:
    """Create the training contribution table if it does not exist."""
    cursor = conn.cursor()
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS ahg_ai_training_contribution (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source VARCHAR(50) NOT NULL COMMENT 'condition_photos, annotation_studio, saas_client, manual',
            object_id INT NULL COMMENT 'AtoM information_object.id',
            contributor VARCHAR(255) NULL,
            client_id INT NULL COMMENT 'SaaS client ID',
            image_filename VARCHAR(255) NOT NULL,
            annotation_filename VARCHAR(255) NOT NULL,
            damage_types JSON NULL COMMENT 'Array of damage type strings in this contribution',
            status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_source (source),
            INDEX idx_status (status),
            INDEX idx_object_id (object_id),
            INDEX idx_client_id (client_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    """)
    conn.commit()
    cursor.close()


def _decode_and_validate_image(image_base64: str) -> bytes:
    """
    Decode a base64 string and validate that it is a valid image.

    Returns the raw image bytes.
    Raises HTTPException on failure.
    """
    # Strip optional data URI prefix (e.g. "data:image/jpeg;base64,...")
    if "," in image_base64 and image_base64.index(",") < 100:
        image_base64 = image_base64.split(",", 1)[1]

    try:
        image_bytes = base64.b64decode(image_base64, validate=True)
    except Exception:
        raise HTTPException(
            status_code=400,
            detail="Invalid base64 encoding for image_base64.",
        )

    if len(image_bytes) < 100:
        raise HTTPException(
            status_code=400,
            detail="Decoded image data is too small to be a valid image.",
        )

    # Validate it is a real image by attempting to open it with PIL
    try:
        from PIL import Image
        img = Image.open(io.BytesIO(image_bytes))
        img.verify()  # verify it is a valid image
    except Exception:
        raise HTTPException(
            status_code=400,
            detail="Decoded base64 data is not a valid image.",
        )

    return image_bytes


# =============================================================================
# POST /training/upload
# =============================================================================
@router.post("/training/upload")
async def upload_dataset(
    file: UploadFile = File(..., description="ZIP file containing images/ and annotations/ directories"),
):
    """
    Upload a training dataset as a ZIP file.

    The ZIP must contain:
      - images/ directory with image files (.jpg, .png, .tiff, etc.)
      - annotations/ directory with annotation files (.json or .xml)

    Returns the upload_id and file counts.
    """
    # Validate file type
    if not file.filename or not file.filename.lower().endswith(".zip"):
        raise HTTPException(
            status_code=400,
            detail="Only ZIP files are accepted. Please upload a .zip archive.",
        )

    # Generate unique upload ID
    upload_id = datetime.now(timezone.utc).strftime("%Y%m%d_%H%M%S") + "_" + uuid.uuid4().hex[:8]
    extract_dir = os.path.join(TRAINING_DATA_DIR, upload_id)

    try:
        # Read uploaded file into memory
        contents = await file.read()
        if not contents:
            raise HTTPException(status_code=400, detail="Empty file uploaded.")

        # Ensure training data directory exists
        os.makedirs(TRAINING_DATA_DIR, exist_ok=True)

        # Save ZIP temporarily
        zip_path = os.path.join(TRAINING_DATA_DIR, f"{upload_id}.zip")
        with open(zip_path, "wb") as f:
            f.write(contents)

        # Validate it is a valid ZIP
        if not zipfile.is_zipfile(zip_path):
            os.remove(zip_path)
            raise HTTPException(
                status_code=400,
                detail="Uploaded file is not a valid ZIP archive.",
            )

        # Extract
        os.makedirs(extract_dir, exist_ok=True)
        with zipfile.ZipFile(zip_path, "r") as zf:
            # Security: check for path traversal
            for member in zf.namelist():
                member_path = os.path.realpath(os.path.join(extract_dir, member))
                if not member_path.startswith(os.path.realpath(extract_dir)):
                    shutil.rmtree(extract_dir, ignore_errors=True)
                    os.remove(zip_path)
                    raise HTTPException(
                        status_code=400,
                        detail="ZIP contains path traversal entries. Upload rejected.",
                    )
            zf.extractall(extract_dir)

        # Remove the ZIP file after extraction
        os.remove(zip_path)

        # Handle case where ZIP contains a single root directory
        extracted_items = os.listdir(extract_dir)
        if len(extracted_items) == 1:
            single_dir = os.path.join(extract_dir, extracted_items[0])
            if os.path.isdir(single_dir):
                # Check if the images/ and annotations/ are inside this subdirectory
                if os.path.isdir(os.path.join(single_dir, "images")) or os.path.isdir(
                    os.path.join(single_dir, "annotations")
                ):
                    # Move contents up one level
                    for item in os.listdir(single_dir):
                        shutil.move(
                            os.path.join(single_dir, item),
                            os.path.join(extract_dir, item),
                        )
                    os.rmdir(single_dir)

        # Validate structure
        images_dir = os.path.join(extract_dir, "images")
        annotations_dir = os.path.join(extract_dir, "annotations")

        if not os.path.isdir(images_dir):
            shutil.rmtree(extract_dir, ignore_errors=True)
            raise HTTPException(
                status_code=400,
                detail="ZIP must contain an 'images/' directory.",
            )

        if not os.path.isdir(annotations_dir):
            shutil.rmtree(extract_dir, ignore_errors=True)
            raise HTTPException(
                status_code=400,
                detail="ZIP must contain an 'annotations/' directory.",
            )

        # Count files
        image_count = _count_files_by_extension(images_dir, IMAGE_EXTENSIONS)
        annotation_count = _count_files_by_extension(annotations_dir, ANNOTATION_EXTENSIONS)

        if image_count == 0:
            shutil.rmtree(extract_dir, ignore_errors=True)
            raise HTTPException(
                status_code=400,
                detail="No image files found in images/ directory. "
                       f"Supported formats: {', '.join(sorted(IMAGE_EXTENSIONS))}",
            )

        if annotation_count == 0:
            shutil.rmtree(extract_dir, ignore_errors=True)
            raise HTTPException(
                status_code=400,
                detail="No annotation files found in annotations/ directory. "
                       f"Supported formats: {', '.join(sorted(ANNOTATION_EXTENSIONS))}",
            )

        logger.info(
            "Dataset uploaded: %s (%d images, %d annotations)",
            upload_id,
            image_count,
            annotation_count,
        )

        return {
            "success": True,
            "upload_id": upload_id,
            "image_count": image_count,
            "annotation_count": annotation_count,
            "path": extract_dir,
        }

    except HTTPException:
        raise
    except Exception as e:
        # Cleanup on unexpected error
        if os.path.isdir(extract_dir):
            shutil.rmtree(extract_dir, ignore_errors=True)
        zip_cleanup = os.path.join(TRAINING_DATA_DIR, f"{upload_id}.zip")
        if os.path.exists(zip_cleanup):
            os.remove(zip_cleanup)
        logger.error("Dataset upload failed: %s", e, exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Failed to process uploaded dataset: {e}",
        )


# =============================================================================
# POST /training/start
# =============================================================================
@router.post("/training/start")
async def start_training(body: TrainingStartRequest):
    """
    Start model training in the background.

    Uses the uploaded dataset identified by dataset_id. Runs the
    prepare_dataset.py and train_detector.py scripts as a subprocess.
    Training progress is tracked in training_status.json.
    """
    # Check if training is already running
    current_status = _get_training_status()
    if current_status.get("status") in ("preparing", "training"):
        raise HTTPException(
            status_code=409,
            detail="Training is already in progress. Wait for it to complete or check status.",
        )

    # Validate dataset exists
    dataset_dir = os.path.join(TRAINING_DATA_DIR, body.dataset_id)
    if not os.path.isdir(dataset_dir):
        raise HTTPException(
            status_code=404,
            detail=f"Dataset '{body.dataset_id}' not found.",
        )

    images_dir = os.path.join(dataset_dir, "images")
    annotations_dir = os.path.join(dataset_dir, "annotations")

    if not os.path.isdir(images_dir) or not os.path.isdir(annotations_dir):
        raise HTTPException(
            status_code=400,
            detail=f"Dataset '{body.dataset_id}' is missing images/ or annotations/ directory.",
        )

    # Set initial status
    started_at = datetime.now(timezone.utc).isoformat()
    _set_training_status({
        "status": "preparing",
        "progress": 0.0,
        "epoch": 0,
        "total_epochs": body.epochs,
        "dataset_id": body.dataset_id,
        "metrics": None,
        "started_at": started_at,
        "completed_at": None,
        "error": None,
    })

    # Build the training wrapper script that runs prepare + train sequentially
    prepare_script = os.path.join(config.BASE_DIR, "training", "prepare_dataset.py")
    train_script = os.path.join(config.BASE_DIR, "training", "train_detector.py")
    yolo_output_dir = os.path.join(dataset_dir, "yolo_dataset")

    # Determine Python executable (prefer venv if available)
    venv_python = os.path.join(config.BASE_DIR, "venv", "bin", "python")
    python_exe = venv_python if os.path.exists(venv_python) else "python3"

    # Build a wrapper shell command that:
    # 1. Prepares the dataset
    # 2. Trains the model
    # 3. Updates status on completion or failure
    wrapper_script = f"""
import json
import os
import subprocess
import sys
import traceback
from datetime import datetime, timezone

STATUS_FILE = {json.dumps(TRAINING_STATUS_FILE)}
BASE_DIR = {json.dumps(config.BASE_DIR)}

def update_status(updates):
    try:
        with open(STATUS_FILE, "r") as f:
            status = json.load(f)
    except Exception:
        status = {{}}
    status.update(updates)
    with open(STATUS_FILE, "w") as f:
        json.dump(status, f, indent=2)

try:
    # Step 1: Prepare dataset
    update_status({{"status": "preparing", "progress": 5.0}})

    result = subprocess.run(
        [
            sys.executable,
            {json.dumps(prepare_script)},
            "--input", {json.dumps(dataset_dir)},
            "--output", {json.dumps(yolo_output_dir)},
        ],
        capture_output=True,
        text=True,
        cwd=BASE_DIR,
    )

    if result.returncode != 0:
        update_status({{
            "status": "failed",
            "error": f"Dataset preparation failed: {{result.stderr or result.stdout}}",
            "completed_at": datetime.now(timezone.utc).isoformat(),
        }})
        sys.exit(1)

    # Verify data.yaml was created
    data_yaml = os.path.join({json.dumps(yolo_output_dir)}, "data.yaml")
    if not os.path.exists(data_yaml):
        update_status({{
            "status": "failed",
            "error": "data.yaml not created by prepare_dataset.py",
            "completed_at": datetime.now(timezone.utc).isoformat(),
        }})
        sys.exit(1)

    # Step 2: Train model
    update_status({{"status": "training", "progress": 10.0}})

    result = subprocess.run(
        [
            sys.executable,
            {json.dumps(train_script)},
            "--data", data_yaml,
            "--epochs", str({body.epochs}),
            "--batch", str({body.batch_size}),
        ],
        capture_output=True,
        text=True,
        cwd=BASE_DIR,
    )

    if result.returncode != 0:
        update_status({{
            "status": "failed",
            "error": f"Training failed: {{result.stderr or result.stdout}}",
            "completed_at": datetime.now(timezone.utc).isoformat(),
        }})
        sys.exit(1)

    # Success
    update_status({{
        "status": "completed",
        "progress": 100.0,
        "epoch": {body.epochs},
        "completed_at": datetime.now(timezone.utc).isoformat(),
        "metrics": {{
            "stdout_tail": result.stdout[-2000:] if result.stdout else None,
        }},
    }})

except Exception as e:
    update_status({{
        "status": "failed",
        "error": f"Unexpected error: {{traceback.format_exc()}}",
        "completed_at": datetime.now(timezone.utc).isoformat(),
    }})
    sys.exit(1)
"""

    # Write wrapper script to a temporary file and run it in the background
    wrapper_path = os.path.join(config.BASE_DIR, "training", "_run_training.py")
    with open(wrapper_path, "w") as f:
        f.write(wrapper_script)

    # Launch as a background subprocess (detached)
    try:
        subprocess.Popen(
            [python_exe, wrapper_path],
            cwd=config.BASE_DIR,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            start_new_session=True,
        )
    except Exception as e:
        _set_training_status({
            "status": "failed",
            "error": f"Failed to launch training process: {e}",
            "completed_at": datetime.now(timezone.utc).isoformat(),
        })
        raise HTTPException(
            status_code=500,
            detail=f"Failed to start training subprocess: {e}",
        )

    logger.info(
        "Training started: dataset=%s, epochs=%d, batch_size=%d",
        body.dataset_id,
        body.epochs,
        body.batch_size,
    )

    return {
        "success": True,
        "message": "Training started in background.",
        "dataset_id": body.dataset_id,
        "epochs": body.epochs,
        "batch_size": body.batch_size,
        "started_at": started_at,
    }


# =============================================================================
# GET /training/status
# =============================================================================
@router.get("/training/status")
async def get_training_status():
    """
    Return the current training status.

    Status values: idle, preparing, training, completed, failed.
    """
    status = _get_training_status()
    return {
        "success": True,
        "training": status,
    }


# =============================================================================
# GET /training/datasets
# =============================================================================
@router.get("/training/datasets")
async def list_datasets():
    """
    List all available training datasets.

    Scans the training_data/ directory and returns metadata for each dataset.
    """
    if not os.path.isdir(TRAINING_DATA_DIR):
        return {
            "success": True,
            "datasets": [],
            "count": 0,
        }

    datasets = []
    for entry in sorted(os.listdir(TRAINING_DATA_DIR)):
        dataset_path = os.path.join(TRAINING_DATA_DIR, entry)
        if not os.path.isdir(dataset_path):
            continue

        images_dir = os.path.join(dataset_path, "images")
        annotations_dir = os.path.join(dataset_path, "annotations")

        # Skip directories that do not look like datasets
        if not os.path.isdir(images_dir) and not os.path.isdir(annotations_dir):
            continue

        image_count = _count_files_by_extension(images_dir, IMAGE_EXTENSIONS)
        annotation_count = _count_files_by_extension(annotations_dir, ANNOTATION_EXTENSIONS)

        # Get creation time from directory stat
        try:
            stat = os.stat(dataset_path)
            created_at = datetime.fromtimestamp(stat.st_ctime, tz=timezone.utc).isoformat()
        except OSError:
            created_at = None

        size_bytes = _get_dir_size(dataset_path)

        datasets.append({
            "dataset_id": entry,
            "image_count": image_count,
            "annotation_count": annotation_count,
            "created_at": created_at,
            "size_bytes": size_bytes,
        })

    return {
        "success": True,
        "datasets": datasets,
        "count": len(datasets),
    }


# =============================================================================
# DELETE /training/dataset/{dataset_id}
# =============================================================================
@router.delete("/training/dataset/{dataset_id}")
async def delete_dataset(dataset_id: str):
    """
    Delete a training dataset by ID.

    Removes the entire dataset directory and all its contents.
    Cannot delete a dataset that is currently being used for training.
    """
    # Prevent path traversal
    if "/" in dataset_id or "\\" in dataset_id or ".." in dataset_id:
        raise HTTPException(
            status_code=400,
            detail="Invalid dataset ID.",
        )

    dataset_path = os.path.join(TRAINING_DATA_DIR, dataset_id)

    if not os.path.isdir(dataset_path):
        raise HTTPException(
            status_code=404,
            detail=f"Dataset '{dataset_id}' not found.",
        )

    # Check if this dataset is currently being trained on
    current_status = _get_training_status()
    if (
        current_status.get("status") in ("preparing", "training")
        and current_status.get("dataset_id") == dataset_id
    ):
        raise HTTPException(
            status_code=409,
            detail="Cannot delete a dataset that is currently being used for training.",
        )

    try:
        shutil.rmtree(dataset_path)
    except Exception as e:
        logger.error("Failed to delete dataset %s: %s", dataset_id, e)
        raise HTTPException(
            status_code=500,
            detail=f"Failed to delete dataset: {e}",
        )

    logger.info("Dataset deleted: %s", dataset_id)

    return {
        "success": True,
        "message": f"Dataset '{dataset_id}' deleted.",
    }


# =============================================================================
# GET /training/model-info
# =============================================================================
@router.get("/training/model-info")
async def get_model_info():
    """
    Return information about the currently deployed damage detection model.

    Includes file existence, size, last modified time, and the damage classes
    the model is trained to detect.
    """
    model_path = config.YOLO_MODEL_PATH
    model_exists = os.path.isfile(model_path)

    info = {
        "model_path": model_path,
        "exists": model_exists,
        "file_size_bytes": None,
        "file_size_mb": None,
        "last_modified": None,
        "damage_classes": config.DAMAGE_CLASSES,
        "damage_class_count": len(config.DAMAGE_CLASSES),
    }

    if model_exists:
        try:
            stat = os.stat(model_path)
            info["file_size_bytes"] = stat.st_size
            info["file_size_mb"] = round(stat.st_size / (1024 * 1024), 2)
            info["last_modified"] = datetime.fromtimestamp(
                stat.st_mtime, tz=timezone.utc
            ).isoformat()
        except OSError as e:
            logger.warning("Failed to stat model file: %s", e)

    return {
        "success": True,
        "model": info,
    }


# =============================================================================
# POST /training/contribute
# =============================================================================
@router.post("/training/contribute")
async def contribute_training_data(body: ContributeRequest):
    """
    Receive a training data contribution.

    Accepts a base64-encoded image with damage annotations from SaaS clients,
    the condition module, or the annotation studio. The contribution is saved
    to disk and recorded in the database with status 'pending' for review.
    """
    # Validate annotations have valid damage types
    invalid_types = []
    for ann in body.annotations:
        normalized = ann.damage_type.lower().replace(" ", "_")
        if normalized not in config.DAMAGE_CLASSES:
            invalid_types.append(ann.damage_type)

    if invalid_types:
        raise HTTPException(
            status_code=400,
            detail=(
                f"Invalid damage type(s): {', '.join(invalid_types)}. "
                f"Valid types: {', '.join(config.DAMAGE_CLASSES)}"
            ),
        )

    # Decode and validate the image
    image_bytes = _decode_and_validate_image(body.image_base64)

    # Generate a unique contribution ID
    contribution_id = (
        datetime.now(timezone.utc).strftime("%Y%m%d_%H%M%S")
        + "_"
        + uuid.uuid4().hex[:12]
    )

    # Build file paths
    contrib_dir = os.path.join(CONTRIBUTIONS_DIR, contribution_id)
    images_dir = os.path.join(contrib_dir, "images")
    annotations_dir = os.path.join(contrib_dir, "annotations")

    try:
        os.makedirs(images_dir, exist_ok=True)
        os.makedirs(annotations_dir, exist_ok=True)

        # Save image as JPEG
        from PIL import Image

        img = Image.open(io.BytesIO(image_bytes))
        if img.mode in ("RGBA", "P", "LA"):
            img = img.convert("RGB")
        image_filename = f"{contribution_id}.jpg"
        img.save(os.path.join(images_dir, image_filename), format="JPEG", quality=95)

        # Build annotation data in the format expected by prepare_dataset.py
        damages_list = []
        damage_type_names = []
        for ann in body.annotations:
            normalized_type = ann.damage_type.lower().replace(" ", "_")
            damages_list.append({
                "type": normalized_type,
                "bbox": {
                    "x1": ann.bbox.x1,
                    "y1": ann.bbox.y1,
                    "x2": ann.bbox.x2,
                    "y2": ann.bbox.y2,
                },
            })
            if normalized_type not in damage_type_names:
                damage_type_names.append(normalized_type)

        annotation_data = {"damages": damages_list}
        annotation_filename = f"{contribution_id}.json"
        annotation_path = os.path.join(annotations_dir, annotation_filename)
        with open(annotation_path, "w") as f:
            json.dump(annotation_data, f, indent=2, ensure_ascii=False)

        # Insert record into the database
        conn = None
        db_id = None
        try:
            conn = _get_db_connection()
            _ensure_contribution_table(conn)
            cursor = conn.cursor()

            cursor.execute(
                """
                INSERT INTO ahg_ai_training_contribution
                    (source, object_id, contributor, client_id,
                     image_filename, annotation_filename, damage_types, status)
                VALUES (%s, %s, %s, %s, %s, %s, %s, 'pending')
                """,
                (
                    body.source.value,
                    body.object_id,
                    body.contributor,
                    body.client_id,
                    image_filename,
                    annotation_filename,
                    json.dumps(damage_type_names),
                ),
            )
            conn.commit()
            db_id = cursor.lastrowid
            cursor.close()
        except Exception as e:
            logger.error("Failed to insert contribution record: %s", e)
            if conn:
                conn.rollback()
            raise HTTPException(
                status_code=500,
                detail=f"Failed to save contribution to database: {e}",
            )
        finally:
            if conn:
                conn.close()

        logger.info(
            "Training contribution received: id=%s, source=%s, damages=%d, db_id=%s",
            contribution_id,
            body.source.value,
            len(body.annotations),
            db_id,
        )

        return {
            "success": True,
            "contribution_id": contribution_id,
            "db_id": db_id,
            "source": body.source.value,
            "damage_types": damage_type_names,
            "annotation_count": len(body.annotations),
            "status": "pending",
        }

    except HTTPException:
        raise
    except Exception as e:
        # Cleanup on unexpected error
        if os.path.isdir(contrib_dir):
            shutil.rmtree(contrib_dir, ignore_errors=True)
        logger.error("Contribution failed: %s", e, exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Failed to process contribution: {e}",
        )


# =============================================================================
# GET /training/contributions
# =============================================================================
@router.get("/training/contributions")
async def list_contributions(
    source: Optional[str] = Query(None, description="Filter by source"),
    status: Optional[str] = Query(None, description="Filter by status (pending/approved/rejected)"),
    page: int = Query(1, ge=1, description="Page number"),
    per_page: int = Query(25, ge=1, le=100, description="Results per page"),
):
    """
    List all training contributions with optional filtering and pagination.

    Query from the ahg_ai_training_contribution table.
    """
    # Validate status filter
    if status and status not in VALID_CONTRIBUTION_STATUSES:
        raise HTTPException(
            status_code=400,
            detail=f"Invalid status '{status}'. Must be one of: {', '.join(sorted(VALID_CONTRIBUTION_STATUSES))}",
        )

    # Validate source filter
    valid_sources = {e.value for e in ContributionSource}
    if source and source not in valid_sources:
        raise HTTPException(
            status_code=400,
            detail=f"Invalid source '{source}'. Must be one of: {', '.join(sorted(valid_sources))}",
        )

    conn = None
    try:
        conn = _get_db_connection()
        _ensure_contribution_table(conn)
        cursor = conn.cursor(dictionary=True)

        # Build query with optional filters
        where_clauses = []
        params = []

        if source:
            where_clauses.append("source = %s")
            params.append(source)

        if status:
            where_clauses.append("status = %s")
            params.append(status)

        where_sql = ""
        if where_clauses:
            where_sql = "WHERE " + " AND ".join(where_clauses)

        # Get total count
        count_sql = f"SELECT COUNT(*) AS total FROM ahg_ai_training_contribution {where_sql}"
        cursor.execute(count_sql, params)
        total = cursor.fetchone()["total"]

        # Calculate pagination
        offset = (page - 1) * per_page
        total_pages = max(1, math.ceil(total / per_page))

        # Fetch page
        data_sql = (
            f"SELECT id, source, object_id, contributor, client_id, "
            f"image_filename, annotation_filename, damage_types, status, "
            f"created_at, updated_at "
            f"FROM ahg_ai_training_contribution {where_sql} "
            f"ORDER BY created_at DESC LIMIT %s OFFSET %s"
        )
        cursor.execute(data_sql, params + [per_page, offset])
        rows = cursor.fetchall()
        cursor.close()

        # Format rows
        for row in rows:
            for key in ("created_at", "updated_at"):
                if row.get(key) and hasattr(row[key], "isoformat"):
                    row[key] = row[key].isoformat()
            # Parse damage_types JSON string
            dt = row.get("damage_types")
            if isinstance(dt, str):
                try:
                    row["damage_types"] = json.loads(dt)
                except (json.JSONDecodeError, TypeError):
                    row["damage_types"] = []

        return {
            "success": True,
            "contributions": rows,
            "pagination": {
                "page": page,
                "per_page": per_page,
                "total": total,
                "total_pages": total_pages,
            },
        }

    except HTTPException:
        raise
    except Exception as e:
        logger.error("Failed to list contributions: %s", e, exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Failed to list contributions: {e}",
        )
    finally:
        if conn:
            conn.close()


# =============================================================================
# POST /training/contributions/{id}/approve
# =============================================================================
@router.post("/training/contributions/{contribution_id}/approve")
async def approve_contribution(contribution_id: int):
    """
    Approve a training contribution.

    Updates the status to 'approved' and copies the image and annotation
    files into the shared approved contributions directory for dataset building.
    """
    conn = None
    try:
        conn = _get_db_connection()
        _ensure_contribution_table(conn)
        cursor = conn.cursor(dictionary=True)

        # Fetch the contribution record
        cursor.execute(
            """
            SELECT id, source, image_filename, annotation_filename, status
            FROM ahg_ai_training_contribution
            WHERE id = %s
            """,
            (contribution_id,),
        )
        row = cursor.fetchone()

        if row is None:
            cursor.close()
            raise HTTPException(
                status_code=404,
                detail=f"Contribution {contribution_id} not found.",
            )

        if row["status"] == "approved":
            cursor.close()
            return {
                "success": True,
                "message": f"Contribution {contribution_id} is already approved.",
                "contribution_id": contribution_id,
                "status": "approved",
            }

        if row["status"] == "rejected":
            cursor.close()
            raise HTTPException(
                status_code=400,
                detail=f"Contribution {contribution_id} has been rejected and cannot be approved.",
            )

        image_filename = row["image_filename"]
        annotation_filename = row["annotation_filename"]

        # Derive the contribution folder ID from the image filename (minus extension)
        contrib_folder_id = os.path.splitext(image_filename)[0]
        source_images_dir = os.path.join(CONTRIBUTIONS_DIR, contrib_folder_id, "images")
        source_annotations_dir = os.path.join(CONTRIBUTIONS_DIR, contrib_folder_id, "annotations")

        # Verify source files exist
        source_image_path = os.path.join(source_images_dir, image_filename)
        source_annotation_path = os.path.join(source_annotations_dir, annotation_filename)

        if not os.path.isfile(source_image_path):
            cursor.close()
            raise HTTPException(
                status_code=404,
                detail=f"Source image file not found on disk: {image_filename}",
            )

        if not os.path.isfile(source_annotation_path):
            cursor.close()
            raise HTTPException(
                status_code=404,
                detail=f"Source annotation file not found on disk: {annotation_filename}",
            )

        # Copy to the approved directory
        approved_images_dir = os.path.join(CONTRIBUTIONS_DIR, "approved", "images")
        approved_annotations_dir = os.path.join(CONTRIBUTIONS_DIR, "approved", "annotations")
        os.makedirs(approved_images_dir, exist_ok=True)
        os.makedirs(approved_annotations_dir, exist_ok=True)

        shutil.copy2(source_image_path, os.path.join(approved_images_dir, image_filename))
        shutil.copy2(
            source_annotation_path,
            os.path.join(approved_annotations_dir, annotation_filename),
        )

        # Update DB status
        cursor.execute(
            """
            UPDATE ahg_ai_training_contribution
            SET status = 'approved'
            WHERE id = %s
            """,
            (contribution_id,),
        )
        conn.commit()
        cursor.close()

        logger.info("Contribution %d approved", contribution_id)

        return {
            "success": True,
            "message": f"Contribution {contribution_id} approved.",
            "contribution_id": contribution_id,
            "status": "approved",
        }

    except HTTPException:
        raise
    except Exception as e:
        logger.error("Failed to approve contribution %d: %s", contribution_id, e, exc_info=True)
        if conn:
            conn.rollback()
        raise HTTPException(
            status_code=500,
            detail=f"Failed to approve contribution: {e}",
        )
    finally:
        if conn:
            conn.close()


# =============================================================================
# POST /training/contributions/{id}/reject
# =============================================================================
@router.post("/training/contributions/{contribution_id}/reject")
async def reject_contribution(contribution_id: int):
    """
    Reject a training contribution.

    Updates the status to 'rejected'. The original contribution files remain
    on disk but will not be included in any future dataset builds.
    """
    conn = None
    try:
        conn = _get_db_connection()
        _ensure_contribution_table(conn)
        cursor = conn.cursor(dictionary=True)

        # Fetch the contribution record
        cursor.execute(
            """
            SELECT id, status
            FROM ahg_ai_training_contribution
            WHERE id = %s
            """,
            (contribution_id,),
        )
        row = cursor.fetchone()

        if row is None:
            cursor.close()
            raise HTTPException(
                status_code=404,
                detail=f"Contribution {contribution_id} not found.",
            )

        if row["status"] == "rejected":
            cursor.close()
            return {
                "success": True,
                "message": f"Contribution {contribution_id} is already rejected.",
                "contribution_id": contribution_id,
                "status": "rejected",
            }

        # Update DB status
        cursor.execute(
            """
            UPDATE ahg_ai_training_contribution
            SET status = 'rejected'
            WHERE id = %s
            """,
            (contribution_id,),
        )
        conn.commit()
        cursor.close()

        logger.info("Contribution %d rejected", contribution_id)

        return {
            "success": True,
            "message": f"Contribution {contribution_id} rejected.",
            "contribution_id": contribution_id,
            "status": "rejected",
        }

    except HTTPException:
        raise
    except Exception as e:
        logger.error("Failed to reject contribution %d: %s", contribution_id, e, exc_info=True)
        if conn:
            conn.rollback()
        raise HTTPException(
            status_code=500,
            detail=f"Failed to reject contribution: {e}",
        )
    finally:
        if conn:
            conn.close()


# =============================================================================
# POST /training/build-dataset
# =============================================================================
@router.post("/training/build-dataset")
async def build_dataset_from_contributions():
    """
    Build a training dataset from all approved contributions.

    Collects all approved contribution images and annotations, copies them
    into a new dataset directory under training_data/ with a unique ID, and
    returns the dataset_id. The resulting dataset can then be used with the
    existing POST /training/start endpoint.
    """
    # Verify there are approved contributions
    approved_images_dir = os.path.join(CONTRIBUTIONS_DIR, "approved", "images")
    approved_annotations_dir = os.path.join(CONTRIBUTIONS_DIR, "approved", "annotations")

    if not os.path.isdir(approved_images_dir) or not os.path.isdir(approved_annotations_dir):
        raise HTTPException(
            status_code=400,
            detail="No approved contributions found. Approve some contributions first.",
        )

    # Count approved images
    image_files = [
        f for f in os.listdir(approved_images_dir)
        if os.path.splitext(f)[1].lower() in IMAGE_EXTENSIONS
    ]
    annotation_files = [
        f for f in os.listdir(approved_annotations_dir)
        if os.path.splitext(f)[1].lower() in ANNOTATION_EXTENSIONS
    ]

    if not image_files:
        raise HTTPException(
            status_code=400,
            detail="No approved image files found in approved contributions directory.",
        )

    if not annotation_files:
        raise HTTPException(
            status_code=400,
            detail="No approved annotation files found in approved contributions directory.",
        )

    # Generate a new dataset ID
    dataset_id = (
        "contrib_"
        + datetime.now(timezone.utc).strftime("%Y%m%d_%H%M%S")
        + "_"
        + uuid.uuid4().hex[:8]
    )
    dataset_dir = os.path.join(TRAINING_DATA_DIR, dataset_id)
    dataset_images_dir = os.path.join(dataset_dir, "images")
    dataset_annotations_dir = os.path.join(dataset_dir, "annotations")

    try:
        os.makedirs(dataset_images_dir, exist_ok=True)
        os.makedirs(dataset_annotations_dir, exist_ok=True)

        # Copy all approved images and annotations
        images_copied = 0
        annotations_copied = 0

        for fname in image_files:
            src = os.path.join(approved_images_dir, fname)
            dst = os.path.join(dataset_images_dir, fname)
            shutil.copy2(src, dst)
            images_copied += 1

        for fname in annotation_files:
            src = os.path.join(approved_annotations_dir, fname)
            dst = os.path.join(dataset_annotations_dir, fname)
            shutil.copy2(src, dst)
            annotations_copied += 1

        size_bytes = _get_dir_size(dataset_dir)

        logger.info(
            "Built dataset from contributions: %s (%d images, %d annotations)",
            dataset_id,
            images_copied,
            annotations_copied,
        )

        return {
            "success": True,
            "dataset_id": dataset_id,
            "image_count": images_copied,
            "annotation_count": annotations_copied,
            "size_bytes": size_bytes,
            "message": (
                f"Dataset '{dataset_id}' created from {images_copied} approved contributions. "
                f"Use POST /training/start with this dataset_id to begin training."
            ),
        }

    except Exception as e:
        # Cleanup on failure
        if os.path.isdir(dataset_dir):
            shutil.rmtree(dataset_dir, ignore_errors=True)
        logger.error("Failed to build dataset from contributions: %s", e, exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Failed to build dataset: {e}",
        )
