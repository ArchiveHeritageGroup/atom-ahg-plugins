#!/usr/bin/env python3
"""
Qdrant Image Indexing Script for AtoM Discovery Plugin

Reads digital object images from a MySQL AtoM database, generates embeddings
using CLIP (ViT-B/32, 512 dims), and indexes them into a Qdrant collection.

Uses reference-size derivatives (usage_id=142) when available for faster
processing, falls back to originals if no derivative exists.

Usage:
    python3 qdrant_image_index.py --db-name=archive --db-user=root
    python3 qdrant_image_index.py --db-name=archive --db-user=root --reset
    python3 qdrant_image_index.py --db-name=archive --db-user=root --limit=500

Environment:
    QDRANT_URL       Qdrant REST endpoint (default: http://localhost:6333)
    MYSQL_HOST       MySQL host (default: localhost)
    MYSQL_PASSWORD   MySQL password (default: empty)
    ATOM_ROOT        AtoM installation root (default: /usr/share/nginx/archive)
"""

import argparse
import os
import sys
import time
from typing import Optional

import pymysql
import pymysql.cursors
from PIL import Image
from qdrant_client import QdrantClient
from qdrant_client.models import (
    Distance,
    PointStruct,
    VectorParams,
    PayloadSchemaType,
)
from sentence_transformers import SentenceTransformer

# ── Configuration ──────────────────────────────────────────────────

MODEL_NAME = "clip-ViT-B-32"
VECTOR_SIZE = 512
BATCH_SIZE = 100          # Records per DB fetch
EMBED_BATCH_SIZE = 32     # Images per embedding batch (GPU memory)
UPSERT_BATCH_SIZE = 100   # Points per Qdrant upsert

# AtoM derivative usage IDs
USAGE_REFERENCE = 142     # Reference-size derivative (480px)
USAGE_THUMBNAIL = 141     # Thumbnail derivative

# Supported image MIME types
IMAGE_MIMES = {
    "image/jpeg", "image/png", "image/tiff", "image/webp",
    "image/gif", "image/bmp", "image/jp2",
}


def _read_mycnf_password():
    """Read password from ~/.my.cnf if it exists (matches mysql CLI behaviour)."""
    cnf = os.path.expanduser("~/.my.cnf")
    if not os.path.isfile(cnf):
        return ""
    try:
        import configparser
        cfg = configparser.ConfigParser()
        cfg.read(cnf)
        return cfg.get("client", "password", fallback="")
    except Exception:
        return ""


def get_db_connection(args):
    """Create MySQL connection via PyMySQL."""
    password = os.environ.get("MYSQL_PASSWORD", "") or args.db_password or _read_mycnf_password()
    return pymysql.connect(
        host=os.environ.get("MYSQL_HOST", "localhost"),
        user=args.db_user,
        password=password,
        database=args.db_name,
        charset="utf8mb4",
        unix_socket="/var/run/mysqld/mysqld.sock",
        cursorclass=pymysql.cursors.DictCursor,
    )


def count_images(conn) -> int:
    """Count digital objects with supported image MIME types."""
    mime_list = ",".join(f"'{m}'" for m in IMAGE_MIMES)
    with conn.cursor(pymysql.cursors.Cursor) as cursor:
        cursor.execute(f"""
            SELECT COUNT(*)
            FROM digital_object do_master
            WHERE do_master.parent_id IS NULL
              AND do_master.object_id IS NOT NULL
              AND do_master.mime_type IN ({mime_list})
        """)
        return cursor.fetchone()[0]


def fetch_images(conn, offset: int, limit: int, atom_root: str):
    """
    Fetch a batch of digital objects with their file paths.
    Prefers reference derivatives (usage_id=142), falls back to originals.
    Returns rows with resolved filesystem paths.
    """
    mime_list = ",".join(f"'{m}'" for m in IMAGE_MIMES)
    with conn.cursor() as cursor:
        cursor.execute(f"""
            SELECT
                do_master.id AS do_id,
                do_master.object_id,
                do_master.path AS master_path,
                do_master.name AS master_name,
                do_master.mime_type,
                i18n.title,
                s.slug,
                ref.path AS ref_path,
                ref.name AS ref_name
            FROM digital_object do_master
            LEFT JOIN information_object_i18n i18n
                ON do_master.object_id = i18n.id AND i18n.culture = 'en'
            LEFT JOIN slug s ON s.object_id = do_master.object_id
            LEFT JOIN digital_object ref
                ON ref.parent_id = do_master.id AND ref.usage_id = {USAGE_REFERENCE}
            WHERE do_master.parent_id IS NULL
              AND do_master.object_id IS NOT NULL
              AND do_master.mime_type IN ({mime_list})
            ORDER BY do_master.id
            LIMIT %s OFFSET %s
        """, (limit, offset))
        rows = cursor.fetchall()

    # Resolve filesystem paths
    results = []
    for row in rows:
        # Prefer reference derivative
        if row["ref_path"] and row["ref_name"]:
            filepath = os.path.join(atom_root, row["ref_path"].lstrip("/"), row["ref_name"])
        else:
            filepath = os.path.join(atom_root, row["master_path"].lstrip("/"), row["master_name"])

        row["filepath"] = filepath
        results.append(row)

    return results


def load_image(filepath: str, max_size: int = 512) -> Optional[Image.Image]:
    """Load and resize an image, returning None on failure."""
    try:
        img = Image.open(filepath).convert("RGB")
        # Resize large images for faster embedding
        if max(img.size) > max_size:
            img.thumbnail((max_size, max_size), Image.LANCZOS)
        return img
    except Exception:
        return None


def setup_collection(client: QdrantClient, collection_name: str, reset: bool = False):
    """Create or reset the Qdrant image collection."""
    exists = client.collection_exists(collection_name)

    if exists and reset:
        print(f"  Dropping existing collection '{collection_name}'...")
        client.delete_collection(collection_name)
        exists = False

    if not exists:
        print(f"  Creating collection '{collection_name}' (dim={VECTOR_SIZE}, cosine)...")
        client.create_collection(
            collection_name=collection_name,
            vectors_config=VectorParams(
                size=VECTOR_SIZE,
                distance=Distance.COSINE,
            ),
        )
        # Payload indexes for filtering
        client.create_payload_index(
            collection_name=collection_name,
            field_name="database",
            field_schema=PayloadSchemaType.KEYWORD,
        )
        client.create_payload_index(
            collection_name=collection_name,
            field_name="object_id",
            field_schema=PayloadSchemaType.INTEGER,
        )
        client.create_payload_index(
            collection_name=collection_name,
            field_name="mime_type",
            field_schema=PayloadSchemaType.KEYWORD,
        )
        print(f"  Collection '{collection_name}' created.")
    else:
        info = client.get_collection(collection_name)
        print(f"  Collection '{collection_name}' exists ({info.points_count} points).")


def main():
    parser = argparse.ArgumentParser(description="Index AtoM digital object images into Qdrant using CLIP")
    parser.add_argument("--db-name", default="archive", help="MySQL database name")
    parser.add_argument("--db-user", default="root", help="MySQL user")
    parser.add_argument("--db-password", default="", help="MySQL password")
    parser.add_argument("--collection", default=None, help="Qdrant collection name (default: {db-name}_images)")
    parser.add_argument("--qdrant-url", default=os.environ.get("QDRANT_URL", "http://localhost:6333"))
    parser.add_argument("--atom-root", default=os.environ.get("ATOM_ROOT", "/usr/share/nginx/archive"))
    parser.add_argument("--reset", action="store_true", help="Drop and recreate collection")
    parser.add_argument("--offset", type=int, default=0, help="Start from this offset")
    parser.add_argument("--limit", type=int, default=0, help="Max images to index (0=all)")
    args = parser.parse_args()

    collection_name = args.collection or (args.db_name + "_images")

    print(f"═══ Qdrant Image Indexer for AtoM Discovery ═══")
    print(f"  Database:   {args.db_name}")
    print(f"  Collection: {collection_name}")
    print(f"  Qdrant:     {args.qdrant_url}")
    print(f"  Model:      {MODEL_NAME} ({VECTOR_SIZE}-dim)")
    print(f"  AtoM root:  {args.atom_root}")
    print()

    # ── Connect to services ──
    print("Connecting to MySQL...")
    conn = get_db_connection(args)

    print("Connecting to Qdrant...")
    qdrant = QdrantClient(url=args.qdrant_url)

    print("Loading CLIP model...")
    model = SentenceTransformer(MODEL_NAME)
    print(f"  Model loaded ({VECTOR_SIZE}-dimensional image embeddings)")
    print()

    # ── Setup collection ──
    setup_collection(qdrant, collection_name, args.reset)

    # ── Count images ──
    total = count_images(conn)
    if args.limit > 0:
        total = min(total, args.offset + args.limit)
    print(f"\n  Images to index: {total - args.offset:,}")

    # ── Index in batches ──
    offset = args.offset
    indexed = 0
    skipped = 0
    start_time = time.time()

    while offset < total:
        batch_limit = min(BATCH_SIZE, total - offset) if args.limit > 0 else BATCH_SIZE
        rows = fetch_images(conn, offset, batch_limit, args.atom_root)

        if not rows:
            break

        # Load images
        images = []
        valid_rows = []
        for row in rows:
            img = load_image(row["filepath"])
            if img is not None:
                images.append(img)
                valid_rows.append(row)
            else:
                skipped += 1

        if not images:
            offset += len(rows)
            continue

        # Generate embeddings in sub-batches
        all_embeddings = []
        for i in range(0, len(images), EMBED_BATCH_SIZE):
            sub_images = images[i:i + EMBED_BATCH_SIZE]
            embeddings = model.encode(sub_images, show_progress_bar=False, normalize_embeddings=True)
            all_embeddings.extend(embeddings)

        # Build Qdrant points (use do_id as point ID for uniqueness)
        points = []
        for row, embedding in zip(valid_rows, all_embeddings):
            point = PointStruct(
                id=int(row["do_id"]),
                vector=embedding.tolist(),
                payload={
                    "database": args.db_name,
                    "object_id": row["object_id"],
                    "title": (row["title"] or "")[:200],
                    "slug": row["slug"] or "",
                    "mime_type": row["mime_type"] or "",
                },
            )
            points.append(point)

        # Upsert to Qdrant
        for i in range(0, len(points), UPSERT_BATCH_SIZE):
            sub_points = points[i:i + UPSERT_BATCH_SIZE]
            qdrant.upsert(
                collection_name=collection_name,
                points=sub_points,
            )

        indexed += len(points)
        offset += len(rows)
        elapsed = time.time() - start_time
        rate = indexed / elapsed if elapsed > 0 else 0
        pct = (offset / total) * 100 if total > 0 else 100

        print(f"  [{pct:5.1f}%] {indexed:>8,} indexed | {offset:>8,}/{total:,} | {rate:.1f} img/s", end="\r")
        sys.stdout.flush()

    elapsed = time.time() - start_time
    print(f"\n\n═══ Image Indexing Complete ═══")
    print(f"  Indexed: {indexed:,}")
    print(f"  Skipped: {skipped:,} (missing/unreadable)")
    print(f"  Time:    {elapsed:.1f}s ({indexed / elapsed:.1f} img/s)" if elapsed > 0 else "  Time:    0s")

    # Verify
    info = qdrant.get_collection(collection_name)
    print(f"  Collection points: {info.points_count:,}")

    conn.close()
    print("\nDone.")


if __name__ == "__main__":
    main()
