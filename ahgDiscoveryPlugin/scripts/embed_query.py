#!/usr/bin/env python3
"""
Lightweight query embedding service for Discovery Plugin.

Called by PHP (VectorSearchStrategy) to embed a search query.
Outputs JSON: {"vector": [0.1, 0.2, ...], "dimensions": 384}

Usage:
    python3 embed_query.py "photographs of Cape Town"
    echo "ANC education policy" | python3 embed_query.py --stdin
"""

import json
import sys

from sentence_transformers import SentenceTransformer

MODEL_NAME = "all-MiniLM-L6-v2"

def main():
    # Get query text
    if "--stdin" in sys.argv:
        query = sys.stdin.read().strip()
    elif len(sys.argv) > 1 and not sys.argv[1].startswith("--"):
        query = " ".join(a for a in sys.argv[1:] if not a.startswith("--"))
    else:
        print(json.dumps({"error": "No query provided"}))
        sys.exit(1)

    if not query:
        print(json.dumps({"error": "Empty query"}))
        sys.exit(1)

    model = SentenceTransformer(MODEL_NAME)
    embedding = model.encode([query], normalize_embeddings=True)[0]

    result = {
        "vector": embedding.tolist(),
        "dimensions": len(embedding),
        "model": MODEL_NAME,
    }
    print(json.dumps(result))


if __name__ == "__main__":
    main()
