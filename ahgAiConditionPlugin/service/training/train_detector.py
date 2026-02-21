#!/usr/bin/env python3
"""
Train Detector - Fine-tune YOLOv8n on 15 archival damage classes.

Prerequisites:
  1. Run prepare_dataset.py to create YOLO-format dataset
  2. Ensure ultralytics is installed

Usage:
  python train_detector.py --data /path/to/data.yaml --epochs 100

The trained model will be saved to:
  ai-condition-service/weights/damage_detector.pt
"""
import argparse
import os
import shutil
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import config


def train_detector(
    data_yaml: str,
    epochs: int = 100,
    batch_size: int = 16,
    img_size: int = 640,
    pretrained: str = "yolov8n.pt",
    device: str = "",
    patience: int = 20,
    project: str = None,
    name: str = "damage_detector",
):
    """
    Fine-tune YOLOv8n for archival damage detection.

    Args:
        data_yaml: Path to data.yaml file.
        epochs: Number of training epochs.
        batch_size: Training batch size.
        img_size: Input image size.
        pretrained: Base model to fine-tune from.
        device: Device string ('' for auto, '0' for GPU 0, 'cpu' for CPU).
        patience: Early stopping patience.
        project: Output project directory.
        name: Run name.
    """
    try:
        from ultralytics import YOLO
    except ImportError:
        print("Error: ultralytics not installed. Run: pip install ultralytics")
        sys.exit(1)

    if not os.path.exists(data_yaml):
        print(f"Error: data.yaml not found: {data_yaml}")
        sys.exit(1)

    if project is None:
        project = os.path.join(config.BASE_DIR, "runs", "detect")

    print("=" * 60)
    print("AHG AI Condition Service - Damage Detector Training")
    print("=" * 60)
    print(f"  Data: {data_yaml}")
    print(f"  Epochs: {epochs}")
    print(f"  Batch size: {batch_size}")
    print(f"  Image size: {img_size}")
    print(f"  Base model: {pretrained}")
    print(f"  Device: {device or 'auto'}")
    print(f"  Patience: {patience}")
    print(f"  Classes: {len(config.DAMAGE_CLASSES)}")
    print("=" * 60)

    # Load base model
    model = YOLO(pretrained)

    # Train
    results = model.train(
        data=data_yaml,
        epochs=epochs,
        batch=batch_size,
        imgsz=img_size,
        device=device or None,
        patience=patience,
        project=project,
        name=name,
        save=True,
        save_period=10,
        plots=True,
        verbose=True,
        # Augmentation parameters
        hsv_h=0.015,
        hsv_s=0.7,
        hsv_v=0.4,
        degrees=5.0,
        translate=0.1,
        scale=0.5,
        flipud=0.3,
        fliplr=0.5,
        mosaic=1.0,
    )

    # Copy best model to weights directory
    best_path = os.path.join(project, name, "weights", "best.pt")
    if os.path.exists(best_path):
        os.makedirs(config.WEIGHTS_DIR, exist_ok=True)
        dest_path = config.YOLO_MODEL_PATH
        shutil.copy2(best_path, dest_path)
        print(f"\nBest model copied to: {dest_path}")
    else:
        print(f"\nWarning: best.pt not found at {best_path}")

    print("\nTraining complete!")
    print(f"Results saved to: {os.path.join(project, name)}")

    return results


def main():
    parser = argparse.ArgumentParser(
        description="Fine-tune YOLOv8n for archival damage detection"
    )
    parser.add_argument(
        "--data", required=True,
        help="Path to data.yaml file"
    )
    parser.add_argument(
        "--epochs", type=int, default=100,
        help="Number of training epochs (default: 100)"
    )
    parser.add_argument(
        "--batch", type=int, default=16,
        help="Batch size (default: 16)"
    )
    parser.add_argument(
        "--img-size", type=int, default=640,
        help="Input image size (default: 640)"
    )
    parser.add_argument(
        "--pretrained", default="yolov8n.pt",
        help="Base model (default: yolov8n.pt)"
    )
    parser.add_argument(
        "--device", default="",
        help="Device: '' for auto, '0' for GPU 0, 'cpu' for CPU"
    )
    parser.add_argument(
        "--patience", type=int, default=20,
        help="Early stopping patience (default: 20)"
    )
    args = parser.parse_args()

    train_detector(
        data_yaml=args.data,
        epochs=args.epochs,
        batch_size=args.batch,
        img_size=args.img_size,
        pretrained=args.pretrained,
        device=args.device,
        patience=args.patience,
    )


if __name__ == "__main__":
    main()
