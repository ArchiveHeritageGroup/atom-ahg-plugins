#!/usr/bin/env python3
"""
Data Augmentation Helpers for damage detection training.

Generates augmented copies of training images to increase dataset size
and improve model robustness for archival material conditions.

Usage:
  python augment.py --input /path/to/images --output /path/to/augmented --factor 5
"""
import argparse
import os
import random
import sys

import numpy as np
from PIL import Image, ImageEnhance, ImageFilter


def random_rotation(image: Image.Image, max_angle: int = 15) -> Image.Image:
    """Rotate image by a random angle within [-max_angle, max_angle]."""
    angle = random.uniform(-max_angle, max_angle)
    return image.rotate(angle, expand=False, fillcolor=(128, 128, 128))


def random_brightness(image: Image.Image, factor_range: tuple = (0.6, 1.4)) -> Image.Image:
    """Adjust brightness by a random factor."""
    factor = random.uniform(*factor_range)
    enhancer = ImageEnhance.Brightness(image)
    return enhancer.enhance(factor)


def random_contrast(image: Image.Image, factor_range: tuple = (0.7, 1.3)) -> Image.Image:
    """Adjust contrast by a random factor."""
    factor = random.uniform(*factor_range)
    enhancer = ImageEnhance.Contrast(image)
    return enhancer.enhance(factor)


def random_saturation(image: Image.Image, factor_range: tuple = (0.7, 1.3)) -> Image.Image:
    """Adjust color saturation by a random factor."""
    factor = random.uniform(*factor_range)
    enhancer = ImageEnhance.Color(image)
    return enhancer.enhance(factor)


def random_crop(image: Image.Image, crop_fraction: float = 0.85) -> Image.Image:
    """
    Randomly crop a portion of the image and resize back to original size.
    """
    w, h = image.size
    new_w = int(w * crop_fraction)
    new_h = int(h * crop_fraction)

    left = random.randint(0, w - new_w)
    top = random.randint(0, h - new_h)

    cropped = image.crop((left, top, left + new_w, top + new_h))
    return cropped.resize((w, h), Image.LANCZOS)


def horizontal_flip(image: Image.Image) -> Image.Image:
    """Flip image horizontally."""
    return image.transpose(Image.FLIP_LEFT_RIGHT)


def vertical_flip(image: Image.Image) -> Image.Image:
    """Flip image vertically."""
    return image.transpose(Image.FLIP_TOP_BOTTOM)


def add_gaussian_noise(image: Image.Image, mean: float = 0, std: float = 10) -> Image.Image:
    """Add Gaussian noise to simulate scanning artifacts."""
    img_array = np.array(image, dtype=np.float32)
    noise = np.random.normal(mean, std, img_array.shape)
    noisy = np.clip(img_array + noise, 0, 255).astype(np.uint8)
    return Image.fromarray(noisy)


def simulate_aging(image: Image.Image) -> Image.Image:
    """
    Simulate paper aging by adding a warm tint and reducing contrast.
    Useful for augmenting modern photos to look like aged archival materials.
    """
    img_array = np.array(image, dtype=np.float32)

    # Add warm yellow-brown tint
    tint = np.array([20, 15, -10], dtype=np.float32)
    img_array = np.clip(img_array + tint, 0, 255)

    # Reduce contrast
    mean = np.mean(img_array)
    img_array = mean + (img_array - mean) * 0.8
    img_array = np.clip(img_array, 0, 255).astype(np.uint8)

    image = Image.fromarray(img_array)

    # Slight blur to simulate old photography
    image = image.filter(ImageFilter.GaussianBlur(radius=0.5))

    return image


def random_blur(image: Image.Image, max_radius: float = 1.5) -> Image.Image:
    """Apply random Gaussian blur."""
    radius = random.uniform(0, max_radius)
    return image.filter(ImageFilter.GaussianBlur(radius=radius))


def augment_image(image: Image.Image) -> Image.Image:
    """
    Apply a random combination of augmentations to an image.
    Each augmentation has a probability of being applied.
    """
    augmentations = [
        (0.5, random_rotation),
        (0.5, random_brightness),
        (0.4, random_contrast),
        (0.3, random_saturation),
        (0.3, random_crop),
        (0.5, horizontal_flip),
        (0.2, vertical_flip),
        (0.2, add_gaussian_noise),
        (0.15, simulate_aging),
        (0.2, random_blur),
    ]

    result = image.copy()
    for prob, aug_func in augmentations:
        if random.random() < prob:
            try:
                result = aug_func(result)
            except Exception:
                pass  # Skip failed augmentation

    return result


def augment_dataset(
    input_dir: str,
    output_dir: str,
    augmentation_factor: int = 5,
):
    """
    Generate augmented copies of all images in a directory.

    Args:
        input_dir: Directory containing source images.
        output_dir: Directory for augmented images.
        augmentation_factor: Number of augmented copies per original.
    """
    os.makedirs(output_dir, exist_ok=True)

    image_extensions = {".jpg", ".jpeg", ".png", ".tiff", ".tif", ".bmp"}
    image_files = [
        f for f in os.listdir(input_dir)
        if os.path.splitext(f)[1].lower() in image_extensions
    ]

    if not image_files:
        print(f"No images found in {input_dir}")
        return

    print(f"Augmenting {len(image_files)} images x{augmentation_factor}")
    total = 0

    for fname in image_files:
        src_path = os.path.join(input_dir, fname)
        basename, ext = os.path.splitext(fname)

        try:
            original = Image.open(src_path).convert("RGB")
        except Exception as e:
            print(f"  Warning: Cannot read {fname}: {e}")
            continue

        # Copy original
        original.save(os.path.join(output_dir, fname))
        total += 1

        # Generate augmented copies
        for i in range(augmentation_factor):
            augmented = augment_image(original)
            aug_fname = f"{basename}_aug{i+1:03d}{ext}"
            augmented.save(os.path.join(output_dir, aug_fname))
            total += 1

    print(f"Generated {total} images in {output_dir}")


def main():
    parser = argparse.ArgumentParser(
        description="Generate augmented training images for damage detection"
    )
    parser.add_argument(
        "--input", required=True,
        help="Input directory of images"
    )
    parser.add_argument(
        "--output", required=True,
        help="Output directory for augmented images"
    )
    parser.add_argument(
        "--factor", type=int, default=5,
        help="Augmentation factor (copies per image, default: 5)"
    )
    args = parser.parse_args()

    augment_dataset(args.input, args.output, args.factor)


if __name__ == "__main__":
    main()
