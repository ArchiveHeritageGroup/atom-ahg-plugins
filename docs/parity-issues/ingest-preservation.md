PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** ingest-preservation — current parity ≈ **65%**.

PSIS/AtoM achieves ~65% feature parity with Heratio reference implementation across the ingest-preservation domain. AtoM strengths: comprehensive preservation format management (conversion, identification, migration planning via MigrationPathwayService), replication to external preservation targets (rsync/S3/Azure), virus scanning (ClamAV), PRONOM sync. Critical AtoM gaps: NO OCFL support (blocks modern archival storage standardization), NO watched-folder streaming ingest (forces manual batch-only workflow), format conversion not operationalized (schema exists but no execution commands). Heratio strengths: OCFL v1.1 with embedded metadata extension and PII gating, streaming ingest pipeline (watched folders), unified data migration. Heratio gaps: format conversion commands missing (tables only), no virus scanning, no format migration planning. Both adequately support fixity scheduling, PREMIS logging, and backup replication. The absence of OCFL in PSIS/AtoM is the single largest parity blocker for adoption of modern archival preservation standards.

### High-severity gaps (PSIS missing)
- **OCFL (Oxford Common File Layout) storage implementation** — `ahg-ocfl` → `MISSING - no ahgOcflPlugin`. Heratio has complete OCFL v1.1 implementation with OcflInitCommand, OcflVerifyCommand, OcflIngestCommand, OcflExportCommand, plus StorageRoot, OcflObject, Version, Inventory, and EmbeddedMetadataExtension classes. AtoM/PSIS has zero OCFL support.
- **Watched-folder streaming ingest pipeline** — `ahg-scan` → `MISSING - no ahgScanPlugin`. Heratio has ScanWatchCommand, ScanProcessCommand, WatchedFolderService for continuous folder monitoring with auto-commit. AtoM lacks this; only manual batch ingest via wizard.

### Medium-severity gaps
- Format conversion/normalization execution commands — `ahg-preservation` → `ahgPreservationPlugin`.
- Format identification commands (Siegfried/DROID) — `ahg-preservation` → `ahgPreservationPlugin`.
- Digital object file replication to preservation targets — `ahg-preservation` → `ahgPreservationPlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.