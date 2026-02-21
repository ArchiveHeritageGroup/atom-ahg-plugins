#!/usr/bin/env python3
"""
Evaluation Script - Compute detection and classification metrics.

Metrics computed:
  - mAP (mean Average Precision) at IoU 0.50 and 0.50:0.95
  - Per-class precision, recall, F1-score
  - Confusion matrix

Usage:
  python evaluate.py --model /path/to/model.pt --data /path/to/data.yaml
  python evaluate.py --classifier /path/to/classifier.pt --data /path/to/val_dir
"""
import argparse
import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import config


def evaluate_detector(model_path: str, data_yaml: str, device: str = ""):
    """
    Evaluate the YOLOv8 damage detector.

    Args:
        model_path: Path to the trained .pt model.
        data_yaml: Path to data.yaml.
        device: Device string.
    """
    try:
        from ultralytics import YOLO
    except ImportError:
        print("Error: ultralytics not installed")
        sys.exit(1)

    if not os.path.exists(model_path):
        print(f"Error: Model not found: {model_path}")
        sys.exit(1)

    if not os.path.exists(data_yaml):
        print(f"Error: data.yaml not found: {data_yaml}")
        sys.exit(1)

    print("=" * 60)
    print("AHG AI Condition Service - Detector Evaluation")
    print("=" * 60)
    print(f"  Model: {model_path}")
    print(f"  Data: {data_yaml}")

    model = YOLO(model_path)
    results = model.val(
        data=data_yaml,
        device=device or None,
        verbose=True,
        plots=True,
    )

    # Print summary metrics
    print("\n" + "=" * 60)
    print("DETECTION METRICS SUMMARY")
    print("=" * 60)

    if hasattr(results, "box"):
        box = results.box
        print(f"  mAP@0.50:      {box.map50:.4f}")
        print(f"  mAP@0.50:0.95: {box.map:.4f}")

        if hasattr(box, "maps") and box.maps is not None:
            print("\n  Per-class mAP@0.50:")
            for i, class_name in enumerate(config.DAMAGE_CLASSES):
                if i < len(box.maps):
                    print(f"    {class_name:20s}: {box.maps[i]:.4f}")

    if hasattr(results, "results_dict"):
        rd = results.results_dict
        print(f"\n  Precision: {rd.get('metrics/precision(B)', 'N/A')}")
        print(f"  Recall:    {rd.get('metrics/recall(B)', 'N/A')}")

    print("=" * 60)
    return results


def evaluate_classifier(model_path: str, val_dir: str, device: str = ""):
    """
    Evaluate the EfficientNet damage classifier.

    Args:
        model_path: Path to the trained .pt model.
        val_dir: Validation directory with class subdirs.
        device: Device string.
    """
    try:
        import torch
        import torch.nn as nn
        from torch.utils.data import DataLoader
        from torchvision import datasets, transforms
        from torchvision.models import efficientnet_b0
    except ImportError:
        print("Error: torch/torchvision not installed")
        sys.exit(1)

    if not os.path.exists(model_path):
        print(f"Error: Model not found: {model_path}")
        sys.exit(1)

    if not os.path.isdir(val_dir):
        print(f"Error: Validation directory not found: {val_dir}")
        sys.exit(1)

    # Device
    if not device:
        device = "cuda" if torch.cuda.is_available() else "cpu"
    device = torch.device(device)

    print("=" * 60)
    print("AHG AI Condition Service - Classifier Evaluation")
    print("=" * 60)
    print(f"  Model: {model_path}")
    print(f"  Val dir: {val_dir}")
    print(f"  Device: {device}")

    # Transform
    val_transform = transforms.Compose([
        transforms.Resize((config.IMAGE_CLASSIFY_SIZE, config.IMAGE_CLASSIFY_SIZE)),
        transforms.ToTensor(),
        transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225]),
    ])

    # Dataset
    val_dataset = datasets.ImageFolder(val_dir, transform=val_transform)
    val_loader = DataLoader(val_dataset, batch_size=32, shuffle=False, num_workers=4)

    # Model
    num_classes = len(config.DAMAGE_CLASSES)
    model = efficientnet_b0(weights=None)
    in_features = model.classifier[1].in_features
    model.classifier[1] = nn.Linear(in_features, num_classes)
    model.load_state_dict(torch.load(model_path, map_location=device))
    model.to(device)
    model.eval()

    # Evaluate
    all_preds = []
    all_labels = []

    with torch.no_grad():
        for images, labels in val_loader:
            images = images.to(device)
            outputs = model(images)
            _, predicted = outputs.max(1)
            all_preds.extend(predicted.cpu().numpy().tolist())
            all_labels.extend(labels.numpy().tolist())

    # Compute metrics
    num_correct = sum(p == l for p, l in zip(all_preds, all_labels))
    accuracy = num_correct / len(all_labels) if all_labels else 0

    print(f"\n  Overall Accuracy: {accuracy:.4f} ({num_correct}/{len(all_labels)})")

    # Per-class metrics
    print(f"\n  {'Class':20s} {'Precision':>10s} {'Recall':>10s} {'F1':>10s} {'Support':>10s}")
    print("  " + "-" * 60)

    class_names = config.DAMAGE_CLASSES
    for cls_idx, cls_name in enumerate(class_names):
        tp = sum(1 for p, l in zip(all_preds, all_labels) if p == cls_idx and l == cls_idx)
        fp = sum(1 for p, l in zip(all_preds, all_labels) if p == cls_idx and l != cls_idx)
        fn = sum(1 for p, l in zip(all_preds, all_labels) if p != cls_idx and l == cls_idx)
        support = sum(1 for l in all_labels if l == cls_idx)

        precision = tp / (tp + fp) if (tp + fp) > 0 else 0
        recall = tp / (tp + fn) if (tp + fn) > 0 else 0
        f1 = 2 * precision * recall / (precision + recall) if (precision + recall) > 0 else 0

        print(f"  {cls_name:20s} {precision:10.4f} {recall:10.4f} {f1:10.4f} {support:10d}")

    # Confusion matrix
    print("\n  Confusion Matrix:")
    confusion = [[0] * num_classes for _ in range(num_classes)]
    for p, l in zip(all_preds, all_labels):
        if l < num_classes and p < num_classes:
            confusion[l][p] += 1

    # Print header
    header = "  " + " " * 16 + "".join(f"{c[:4]:>6s}" for c in class_names)
    print(header)
    for i, cls_name in enumerate(class_names):
        row = f"  {cls_name[:14]:14s} |"
        for j in range(num_classes):
            row += f"{confusion[i][j]:6d}"
        print(row)

    print("=" * 60)

    # Save results
    results = {
        "accuracy": accuracy,
        "total_samples": len(all_labels),
        "correct": num_correct,
        "model": model_path,
        "val_dir": val_dir,
    }
    results_path = os.path.join(
        os.path.dirname(model_path), "eval_results.json"
    )
    try:
        with open(results_path, "w") as f:
            json.dump(results, f, indent=2)
        print(f"\nResults saved to: {results_path}")
    except Exception:
        pass

    return results


def main():
    parser = argparse.ArgumentParser(
        description="Evaluate damage detection and classification models"
    )
    parser.add_argument("--model", help="Path to YOLO detector model (.pt)")
    parser.add_argument("--classifier", help="Path to classifier model (.pt)")
    parser.add_argument("--data", required=True, help="data.yaml or val directory path")
    parser.add_argument("--device", default="", help="Device string")

    args = parser.parse_args()

    if args.model:
        evaluate_detector(args.model, args.data, args.device)
    elif args.classifier:
        evaluate_classifier(args.classifier, args.data, args.device)
    else:
        print("Error: Specify --model (detector) or --classifier to evaluate")
        sys.exit(1)


if __name__ == "__main__":
    main()
