PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** dam-media — current parity ≈ **45%**.

Heratio has significantly richer dam-media functionality (45% parity from AtoM perspective). Four Heratio packages (ahg-media-processing, ahg-media-streaming, ahg-c2pa, ahg-image-ar) lack AtoM equivalents, covering streaming, derivatives, watermarking, and AI provenance. Metadata export stronger in Heratio (16 vs 12 formats, RDA/DACS support). AtoM's rights_derivative_rule system (per-role redaction/resize) absent in Heratio. Core modern capabilities missing from PSIS: HTTP streaming with seeking, video transcoding, watermarking, PDF extraction, C2PA manifests, AI video animation—all standard in Heratio.

### High-severity gaps (PSIS missing)
- **Media derivatives (thumbnails, reference images, posters)** — `ahg-media-processing` → `ahgDAMPlugin`. Heratio DerivativeService.generateThumbnail/Reference; AtoM has media_derivatives table but no service
- **Watermarking system (visible + invisible)** — `ahg-media-processing` → `ahgDAMPlugin`. Heratio WatermarkService; AtoM has watermark tables but no service implementation
- **HTTP streaming with Range request support for seeking** — `ahg-media-streaming` → `none`. Heratio StreamingService handles byte-range seeking; no AtoM equivalent
- **Video/audio transcoding to browser formats** — `ahg-media-streaming` → `none`. Heratio TranscodingService transcodes AVI/MOV to MP4; no AtoM equivalent
- **C2PA manifest generation, signing, embedding** — `ahg-c2pa` → `none`. Heratio C2paService with Ed25519 signing; no AtoM C2PA plugin

### Medium-severity gaps
- Caption/subtitle track management — `ahg-media-streaming` → `none`.
- PDF text extraction — `ahg-pdf-tools` → `ahgPreservationPlugin`.
- AI video animation from static images — `ahg-image-ar` → `none`.
- RDA export/import with carrier mapping — `ahg-metadata-export` → `ahgMetadataExportPlugin`.
- DACS export/import — `ahg-metadata-export` → `ahgMetadataExportPlugin`.
- EAD4, EAD2002, MODS, METS, EAC-F exporters — `ahg-metadata-export` → `ahgMetadataExportPlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.