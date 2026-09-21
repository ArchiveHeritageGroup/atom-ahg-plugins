# ahgSubtreeExportPlugin

Export a record and the next N descriptions **in tree order**, with their digital
objects, to external storage. CLI only. Targets **AtoM 2.8.1**.

## Why this is not the portable export plugin

`ahgPortableExportPlugin` targets AtoM 2.10 and builds an offline catalogue viewer.
Its one hard dependency on 2.10 is the Illuminate query builder, used at 192 call
sites. This plugin is the export half only, rewritten on `QubitPdo`, with the
importer, archive extractor, search index and JS viewer left out - about 120 of
those call sites serve features that have nothing to do with getting files out.

It also does **not** write to `ahg_settings`. That table is an AHG/2.10 construct;
a vanilla 2.8 instance has none, and the 2.10 plugin's schema would fail there.

## The selection model

A **forward walk in tree order** from a start record, bounded by an ancestor.

Not a descendant walk. The record this was built for, `smt-les-hab1-1` on RARI, is a
leaf - `lft 276585, rgt 276586` - so "this record and its descendants" returns
exactly one row. What is wanted is this record and the next N onwards.

⚠️ **The bound is load-bearing.** An unbounded forward walk runs past the end of its
collection and starts exporting the next one: a silent wrong answer, not an error.
The bound defaults to the start record's parent; `--bound` overrides it. On RARI the
start sits inside `smits-lucas-3` (253322-284497) and a thousand records stay inside
only because the bound holds them there.

## Use

```bash
# Always look first. Writes nothing.
./symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 --output=/mnt/ext/rari --estimate-only

# Single pass
./symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 --output=/mnt/ext/rari

# Sequential batches, resumable
./symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 --batch-size=100 --output=/mnt/ext/rari
./symfony subtree:export --resume=7
```

Masters and references are included by default; thumbnails are not. On RARI a
thousand records is about **20.67 GB** of masters, while references round to 0.00 GB
- which is why including them costs nothing and makes the package usable without
processing.

## Destination

The export refuses to start unless the destination has the estimate plus 50%
headroom free, overridable with `--force`.

⚠️ **Do not export to the AtoM root.** On RARI it sits on a 62G filesystem with 32G
free, so a 20.67 GB export would leave a production root filesystem with 11G.
`uploads` is a symlink to `/data`, a 30T NFS mount already 90% full. Export to
attached external storage.

## Resuming

`batch_size` above 0 drains one batch per invocation and pauses. The cursor is the
last `lft` exported, and `subtree_export_file` carries a unique key on
`(export_id, digital_object_id)`, so a resumed batch that overlaps an interrupted
one records each file once and does not double-count bytes.

## Install

```bash
mysql -u root <dbname> < database/install.sql
```

Creates `subtree_export` and `subtree_export_file`. Touches nothing else.
