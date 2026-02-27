#!/usr/bin/env python3
"""
Qdrant Indexing Script for AtoM Discovery Plugin

Reads archival records from a MySQL AtoM database, generates embeddings
using sentence-transformers (all-MiniLM-L6-v2), and indexes them into
a Qdrant collection.

Usage:
    python3 qdrant_index.py --db-name=atom --db-user=root
    python3 qdrant_index.py --db-name=archive --db-user=root --collection=archive_records
    python3 qdrant_index.py --db-name=atom --db-user=root --reset  # Drop and recreate collection

Environment:
    QDRANT_URL       Qdrant REST endpoint (default: http://localhost:6333)
    MYSQL_HOST       MySQL host (default: localhost)
    MYSQL_PASSWORD   MySQL password (default: empty)
"""

import argparse
import html
import math
import os
import re
import sys
import time
from typing import Optional

import pymysql
import pymysql.cursors
from qdrant_client import QdrantClient
from qdrant_client.models import (
    Distance,
    PointStruct,
    VectorParams,
    Filter,
    FieldCondition,
    MatchValue,
    PayloadSchemaType,
)
from sentence_transformers import SentenceTransformer

# ── Configuration ──────────────────────────────────────────────────

MODEL_NAME = "all-MiniLM-L6-v2"  # 384 dimensions, fast, good quality
VECTOR_SIZE = 384
BATCH_SIZE = 512          # Records per DB fetch
EMBED_BATCH_SIZE = 256    # Records per embedding batch
UPSERT_BATCH_SIZE = 256   # Points per Qdrant upsert
CULTURE = "en"


def strip_html(text: str) -> str:
    """Remove HTML tags and decode entities."""
    if not text:
        return ""
    text = re.sub(r"<[^>]+>", " ", text)
    text = html.unescape(text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


def build_text(title: str, scope: Optional[str], transcript: Optional[str] = None) -> str:
    """Combine title, scope_and_content, and OCR transcript into embeddable text."""
    parts = []
    if title:
        parts.append(strip_html(title))
    if scope:
        cleaned = strip_html(scope)
        if len(cleaned) > 500:
            cleaned = cleaned[:500]
        parts.append(cleaned)
    if transcript:
        cleaned = strip_html(transcript)
        # Use first 1000 chars of OCR text for embedding
        if len(cleaned) > 1000:
            cleaned = cleaned[:1000]
        parts.append(cleaned)
    return " ".join(parts)


def get_db_connection(args):
    """Create MySQL connection via PyMySQL."""
    import pymysql
    password = os.environ.get("MYSQL_PASSWORD", args.db_password)
    return pymysql.connect(
        host=os.environ.get("MYSQL_HOST", "localhost"),
        user=args.db_user,
        password=password,
        database=args.db_name,
        charset="utf8mb4",
        unix_socket="/var/run/mysqld/mysqld.sock",
        cursorclass=pymysql.cursors.DictCursor,
    )


def count_records(conn) -> int:
    """Count indexable records (excludes junk: barcodes, placeholders, very short titles)."""
    with conn.cursor(pymysql.cursors.Cursor) as cursor:
        cursor.execute("""
            SELECT COUNT(*)
            FROM information_object io
            JOIN information_object_i18n i18n ON io.id = i18n.id AND i18n.culture = %s
            WHERE io.id != 1
              AND i18n.title IS NOT NULL
              AND i18n.title != ''
              AND i18n.title NOT LIKE 'Barcode%%'
              AND COALESCE(i18n.scope_and_content, '') != 'barcode'
              AND LENGTH(TRIM(i18n.title)) >= 3
        """, (CULTURE,))
        return cursor.fetchone()[0]


def fetch_records(conn, offset: int, limit: int):
    """Fetch a batch of records with metadata including OCR transcript."""
    with conn.cursor() as cursor:
        cursor.execute("""
            SELECT
                io.id AS object_id,
                i18n.title,
                i18n.scope_and_content,
                io.parent_id,
                io.lft,
                io.rgt,
                s.slug,
                LEFT(pi.value, 2000) AS transcript
            FROM information_object io
            JOIN information_object_i18n i18n ON io.id = i18n.id AND i18n.culture = %s
            LEFT JOIN slug s ON s.object_id = io.id
            LEFT JOIN digital_object do2 ON do2.object_id = io.id
            LEFT JOIN property p ON p.object_id = do2.id AND p.name = 'transcript'
            LEFT JOIN property_i18n pi ON p.id = pi.id AND pi.value IS NOT NULL AND pi.value != ''
            WHERE io.id != 1
              AND i18n.title IS NOT NULL
              AND i18n.title != ''
              AND i18n.title NOT LIKE 'Barcode%%'
              AND COALESCE(i18n.scope_and_content, '') != 'barcode'
              AND LENGTH(TRIM(i18n.title)) >= 3
            GROUP BY io.id
            ORDER BY io.id
            LIMIT %s OFFSET %s
        """, (CULTURE, limit, offset))
        return cursor.fetchall()


def setup_collection(client: QdrantClient, collection_name: str, reset: bool = False):
    """Create or reset the Qdrant collection."""
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
        # Create payload indexes for filtering
        client.create_payload_index(
            collection_name=collection_name,
            field_name="database",
            field_schema=PayloadSchemaType.KEYWORD,
        )
        client.create_payload_index(
            collection_name=collection_name,
            field_name="parent_id",
            field_schema=PayloadSchemaType.INTEGER,
        )
        print(f"  Collection '{collection_name}' created.")
    else:
        info = client.get_collection(collection_name)
        print(f"  Collection '{collection_name}' exists ({info.points_count} points).")


def main():
    parser = argparse.ArgumentParser(description="Index AtoM records into Qdrant")
    parser.add_argument("--db-name", default="atom", help="MySQL database name")
    parser.add_argument("--db-user", default="root", help="MySQL user")
    parser.add_argument("--db-password", default="", help="MySQL password")
    parser.add_argument("--collection", default="atom_records", help="Qdrant collection name")
    parser.add_argument("--qdrant-url", default=os.environ.get("QDRANT_URL", "http://localhost:6333"))
    parser.add_argument("--reset", action="store_true", help="Drop and recreate collection")
    parser.add_argument("--offset", type=int, default=0, help="Start from this offset")
    parser.add_argument("--limit", type=int, default=0, help="Max records to index (0=all)")
    args = parser.parse_args()

    print(f"═══ Qdrant Indexer for AtoM Discovery ═══")
    print(f"  Database:   {args.db_name}")
    print(f"  Collection: {args.collection}")
    print(f"  Qdrant:     {args.qdrant_url}")
    print(f"  Model:      {MODEL_NAME}")
    print()

    # ── Connect to services ──
    print("Connecting to MySQL...")
    conn = get_db_connection(args)

    print("Connecting to Qdrant...")
    qdrant = QdrantClient(url=args.qdrant_url)

    print("Loading embedding model...")
    model = SentenceTransformer(MODEL_NAME)
    print(f"  Model loaded ({VECTOR_SIZE}-dimensional embeddings)")
    print()

    # ── Setup collection ──
    setup_collection(qdrant, args.collection, args.reset)

    # ── Count records ──
    total = count_records(conn)
    if args.limit > 0:
        total = min(total, args.offset + args.limit)
    print(f"\n  Records to index: {total - args.offset:,}")

    # ── Index in batches ──
    offset = args.offset
    indexed = 0
    skipped = 0
    start_time = time.time()

    while offset < total:
        batch_limit = min(BATCH_SIZE, total - offset) if args.limit > 0 else BATCH_SIZE
        rows = fetch_records(conn, offset, batch_limit)

        if not rows:
            break

        # Build texts for embedding
        texts = []
        valid_rows = []
        for row in rows:
            text = build_text(row["title"], row.get("scope_and_content"), row.get("transcript"))
            if text:
                texts.append(text)
                valid_rows.append(row)
            else:
                skipped += 1

        if not texts:
            offset += len(rows)
            continue

        # Generate embeddings in sub-batches
        all_embeddings = []
        for i in range(0, len(texts), EMBED_BATCH_SIZE):
            sub_texts = texts[i:i + EMBED_BATCH_SIZE]
            embeddings = model.encode(sub_texts, show_progress_bar=False, normalize_embeddings=True)
            all_embeddings.extend(embeddings)

        # Build Qdrant points
        points = []
        for row, embedding in zip(valid_rows, all_embeddings):
            point = PointStruct(
                id=int(row["object_id"]),
                vector=embedding.tolist(),
                payload={
                    "database": args.db_name,
                    "title": strip_html(row["title"])[:200],
                    "parent_id": row["parent_id"] or 0,
                    "slug": row["slug"] or "",
                    "has_scope": bool(row.get("scope_and_content")),
                    "has_transcript": bool(row.get("transcript")),
                },
            )
            points.append(point)

        # Upsert to Qdrant in sub-batches
        for i in range(0, len(points), UPSERT_BATCH_SIZE):
            sub_points = points[i:i + UPSERT_BATCH_SIZE]
            qdrant.upsert(
                collection_name=args.collection,
                points=sub_points,
            )

        indexed += len(points)
        offset += len(rows)
        elapsed = time.time() - start_time
        rate = indexed / elapsed if elapsed > 0 else 0
        pct = (offset / total) * 100 if total > 0 else 100

        print(f"  [{pct:5.1f}%] {indexed:>8,} indexed | {offset:>8,}/{total:,} | {rate:.0f} rec/s", end="\r")
        sys.stdout.flush()

    elapsed = time.time() - start_time
    print(f"\n\n═══ Indexing Complete ═══")
    print(f"  Indexed: {indexed:,}")
    print(f"  Skipped: {skipped:,}")
    print(f"  Time:    {elapsed:.1f}s ({indexed / elapsed:.0f} rec/s)")

    # Verify
    info = qdrant.get_collection(args.collection)
    print(f"  Collection points: {info.points_count:,}")

    conn.close()
    print("\nDone.")


if __name__ == "__main__":
    main()
