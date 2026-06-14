# Wave 7 — MARC21 binary (ISO 2709) export (2026-06-14)

**Plugin:** ahgLibraryPlugin · **Status:** built + verified live, unreleased.

## Gap
Wave-7 verify found MARC: import + MARCXML export present, but **no full-record → ISO-2709 binary serializer** (no .mrc export). Built it, reusing the existing XML field map.

## Built
- `MarcService::exportMarc21(array $itemIds)` — concatenated binary MARC records.
- `MarcService::buildRecordFields(object $item)` — structured record (leader + control + data fields), mirroring `writeRecordXml()`'s field map (020/022/010/050/082/099/100/700/245/250/264/300/490/500/504/520 + subjects). XML path untouched.
- `MarcService::recordToIso2709(array $rec)` — byte-accurate ISO 2709 serializer (directory + leader record-length@0-4 + base-address@12-16; FT 0x1E / RT 0x1D / SF 0x1F; UTF-8 byte lengths).
- New `library/marcExport` action + route `/library/marc-export?format=marc21|marcxml[&ids=]` — auth-gated, streams `.mrc` (`application/marc`) or `.marcxml`.

## Verified
- All `php -l` clean.
- ISO-2709 serializer self-test (reflection, standalone): record byte-length field == actual bytes (147), base address == 24 + directory (61), ends with RT, directory entry `008`+`0041`+`00000` correct → valid MARC structure.
- `/library/marc-export?format=marc21|marcxml` → 200 (auth gate, no 500). No DDL (reads existing library tables). fpm restarted.

## Unit 2 — ONIX ingestion (Heratio clone) — built + parser-verified, live
PSIS had no ONIX at all; Heratio has a complete `OnixIngestService` (+ tables + a `sample-onix.xml` fixture). Cloned the **parse + validate + review (staging)** phase (commit-to-catalogue deferred).
- `database/onix_ingest.sql`: `library_onix_ingest` + `library_onix_ingest_line` (mirrors Heratio).
- `lib/Service/OnixIngestService.php` (global class; Laravel→PSIS idiom: DB facade→Capsule, `now()`→`date()`, `DB::connection()->transaction()`): `parse` (DOMXPath, namespace-agnostic `local-name()`), `extractProduct`, `validateRecord` (title + ISBN-13/10/ISSN checksums + catalogue dup-check), `ingest`, `listIngests`/`getIngest`/`getLines`/`updateLineStatus`/`deleteIngest`.
- `library/onix` action + route `/library/onix[?id=]` (auth; file upload or paste → parse → review queue with status badges) + template.
- **Verified:** all `php -l` clean; **parser run against Heratio's `sample-onix.xml`** → version 3.0, 2 records extracted correctly (ISBN 9780262033848 / "Introduction to Algorithms" / Cormen;Leiserson / MIT Press / 2009; bad-checksum record flagged), ISBN-13 validator correct. Both tables created; `/library/onix` → 200 (no 500). fpm restarted.
- **Deferred (clean follow-up):** `commit()` (valid lines → library_item + acquisitions order line) — needs the catalog/acq write path.

## Remaining Wave-7 buildable (not built)
SRU client query builder; SUSHI vendor harvester (server exists); deeper COUNTER analytics (PSIS lacks library_counter_log); ONIX commit phase. (MARC editor validation rules also partial.)
