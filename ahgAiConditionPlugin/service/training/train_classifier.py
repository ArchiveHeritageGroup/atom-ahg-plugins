#!/usr/bin/env python3
"""
Train Classifier - Fine-tune EfficientNet-B0 for damage type classification.

Takes cropped damage regions organized by class and trains a classifier.

Expected input structure:
  dataset/
    train/
      tear/
        crop001.jpg
        crop002.jpg
      stain/
        ...
    val/
      tear/
        ...
      stain/
        ...

Usage:
  python train_classifier.py --data /path/to/dataset --epochs 50

The trained model will be saved to:
  ai-condition-service/weights/damage_classifier.pt
"""
import argparse
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import config


def train_classifier(
    data_dir: str,
    epochs: int = 50,
    batch_size: int = 32,
    learning_rate: float = 1e-4,
    device: str = "",
):
    """
    Fine-tune EfficientNet-B0 for damage type classification.

    Args:
        data_dir: Root dataset directory with train/ and val/ subdirs.
        epochs: Number of training epochs.
        batch_size: Training batch size.
        learning_rate: Initial learning rate.
        device: Device string.
    """
    try:
        import torch
        import torch.nn as nn
        import torch.optim as optim
        from torch.utils.data import DataLoader
        from torchvision import datasets, transforms
        from torchvision.models import efficientnet_b0, EfficientNet_B0_Weights
    except ImportError:
        print("Error: torch/torchvision not installed")
        sys.exit(1)

    train_dir = os.path.join(data_dir, "train")
    val_dir = os.path.join(data_dir, "val")

    if not os.path.isdir(train_dir):
        print(f"Error: train directory not found: {train_dir}")
        sys.exit(1)
    if not os.path.isdir(val_dir):
        print(f"Error: val directory not found: {val_dir}")
        sys.exit(1)

    # Determine device
    if not device:
        device = "cuda" if torch.cuda.is_available() else "cpu"
    device = torch.device(device)

    print("=" * 60)
    print("AHG AI Condition Service - Damage Classifier Training")
    print("=" * 60)
    print(f"  Data: {data_dir}")
    print(f"  Epochs: {epochs}")
    print(f"  Batch size: {batch_size}")
    print(f"  Learning rate: {learning_rate}")
    print(f"  Device: {device}")
    print(f"  Classes: {len(config.DAMAGE_CLASSES)}")
    print("=" * 60)

    # Data transforms
    train_transform = transforms.Compose([
        transforms.Resize((config.IMAGE_CLASSIFY_SIZE, config.IMAGE_CLASSIFY_SIZE)),
        transforms.RandomHorizontalFlip(),
        transforms.RandomVerticalFlip(p=0.3),
        transforms.RandomRotation(15),
        transforms.ColorJitter(brightness=0.3, contrast=0.3, saturation=0.2, hue=0.1),
        transforms.ToTensor(),
        transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225]),
    ])

    val_transform = transforms.Compose([
        transforms.Resize((config.IMAGE_CLASSIFY_SIZE, config.IMAGE_CLASSIFY_SIZE)),
        transforms.ToTensor(),
        transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225]),
    ])

    # Datasets
    train_dataset = datasets.ImageFolder(train_dir, transform=train_transform)
    val_dataset = datasets.ImageFolder(val_dir, transform=val_transform)

    print(f"  Train samples: {len(train_dataset)}")
    print(f"  Val samples: {len(val_dataset)}")
    print(f"  Classes found: {train_dataset.classes}")

    train_loader = DataLoader(
        train_dataset, batch_size=batch_size, shuffle=True,
        num_workers=4, pin_memory=True,
    )
    val_loader = DataLoader(
        val_dataset, batch_size=batch_size, shuffle=False,
        num_workers=4, pin_memory=True,
    )

    # Model
    num_classes = len(config.DAMAGE_CLASSES)
    model = efficientnet_b0(weights=EfficientNet_B0_Weights.DEFAULT)
    in_features = model.classifier[1].in_features
    model.classifier[1] = nn.Linear(in_features, num_classes)
    model = model.to(device)

    # Loss and optimizer
    criterion = nn.CrossEntropyLoss()
    optimizer = optim.AdamW(model.parameters(), lr=learning_rate, weight_decay=1e-4)
    scheduler = optim.lr_scheduler.CosineAnnealingLR(optimizer, T_max=epochs)

    # Training loop
    best_val_acc = 0.0
    best_model_state = None

    for epoch in range(epochs):
        # Train
        model.train()
        train_loss = 0.0
        train_correct = 0
        train_total = 0

        for images, labels in train_loader:
            images, labels = images.to(device), labels.to(device)

            optimizer.zero_grad()
            outputs = model(images)
            loss = criterion(outputs, labels)
            loss.backward()
            optimizer.step()

            train_loss += loss.item() * images.size(0)
            _, predicted = outputs.max(1)
            train_total += labels.size(0)
            train_correct += predicted.eq(labels).sum().item()

        scheduler.step()

        train_loss /= train_total
        train_acc = train_correct / train_total

        # Validate
        model.eval()
        val_loss = 0.0
        val_correct = 0
        val_total = 0

        with torch.no_grad():
            for images, labels in val_loader:
                images, labels = images.to(device), labels.to(device)
                outputs = model(images)
                loss = criterion(outputs, labels)

                val_loss += loss.item() * images.size(0)
                _, predicted = outputs.max(1)
                val_total += labels.size(0)
                val_correct += predicted.eq(labels).sum().item()

        val_loss /= val_total
        val_acc = val_correct / val_total

        print(
            f"Epoch {epoch+1}/{epochs}: "
            f"Train Loss={train_loss:.4f} Acc={train_acc:.4f} | "
            f"Val Loss={val_loss:.4f} Acc={val_acc:.4f}"
        )

        # Save best model
        if val_acc > best_val_acc:
            best_val_acc = val_acc
            best_model_state = model.state_dict().copy()
            print(f"  -> New best val accuracy: {val_acc:.4f}")

    # Save best model
    if best_model_state is not None:
        os.makedirs(config.WEIGHTS_DIR, exist_ok=True)
        save_path = config.CLASSIFIER_MODEL_PATH
        torch.save(best_model_state, save_path)
        print(f"\nBest model saved to: {save_path}")
        print(f"Best validation accuracy: {best_val_acc:.4f}")
    else:
        print("\nWarning: No model checkpoint was saved.")

    print("\nTraining complete!")


def main():
    parser = argparse.ArgumentParser(
        description="Fine-tune EfficientNet-B0 for damage classification"
    )
    parser.add_argument(
        "--data", required=True,
        help="Dataset directory with train/ and val/ subdirs"
    )
    parser.add_argument(
        "--epochs", type=int, default=50,
        help="Number of training epochs (default: 50)"
    )
    parser.add_argument(
        "--batch", type=int, default=32,
        help="Batch size (default: 32)"
    )
    parser.add_argument(
        "--lr", type=float, default=1e-4,
        help="Learning rate (default: 1e-4)"
    )
    parser.add_argument(
        "--device", default="",
        help="Device: '' for auto, 'cuda:0', 'cpu'"
    )
    args = parser.parse_args()

    train_classifier(
        data_dir=args.data,
        epochs=args.epochs,
        batch_size=args.batch,
        learning_rate=args.lr,
        device=args.device,
    )


if __name__ == "__main__":
    main()
