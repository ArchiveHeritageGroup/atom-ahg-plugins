#!/usr/bin/env python3
"""
Lightweight image embedding service for Discovery Plugin.

Called by PHP (ImageSearchStrategy) to embed an image using CLIP.
Outputs JSON: {"vector": [0.1, 0.2, ...], "dimensions": 512}

Usage:
    python3 embed_image.py /path/to/image.jpg
    python3 embed_image.py --stdin < image.jpg
"""

import json
import sys
import os

from PIL import Image
from sentence_transformers import SentenceTransformer

MODEL_NAME = "clip-ViT-B-32"


def main():
    # Get image path
    if "--stdin" in sys.argv:
        # Read image from stdin (binary)
        import io
        data = sys.stdin.buffer.read()
        try:
            img = Image.open(io.BytesIO(data)).convert("RGB")
        except Exception as e:
            print(json.dumps({"error": f"Cannot open image from stdin: {str(e)}"}))
            sys.exit(1)
    elif len(sys.argv) > 1 and not sys.argv[1].startswith("--"):
        path = sys.argv[1]
        if not os.path.isfile(path):
            print(json.dumps({"error": f"File not found: {path}"}))
            sys.exit(1)
        try:
            img = Image.open(path).convert("RGB")
        except Exception as e:
            print(json.dumps({"error": f"Cannot open image: {str(e)}"}))
            sys.exit(1)
    else:
        print(json.dumps({"error": "No image provided. Usage: embed_image.py <path>"}))
        sys.exit(1)

    # Resize for efficiency
    if max(img.size) > 512:
        img.thumbnail((512, 512), Image.LANCZOS)

    model = SentenceTransformer(MODEL_NAME)
    embedding = model.encode([img], normalize_embeddings=True)[0]

    result = {
        "vector": embedding.tolist(),
        "dimensions": len(embedding),
        "model": MODEL_NAME,
    }
    print(json.dumps(result))


if __name__ == "__main__":
    main()
