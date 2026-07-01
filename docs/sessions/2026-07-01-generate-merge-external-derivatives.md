# 2026-07-01 — Identifier Generate, merge-to-PDF, external-link derivatives

Instances: archive/PSIS + archaeology (synced). Releases: atom-ahg-plugins
v3.79.38–v3.79.40, atom-framework v2.13.11.

## Identifier Generate (ioManage add/edit form)
- v3.79.38: no longer FORCES a repository (client gate removed; server legacy
  fallback uses a REPO placeholder; NumberingService already handled null repo).
- v3.79.39: "Generate creates then clears; re-click increments the number" —
  getNextReference RESERVES/advances the counter each call. Fixed client-side:
  cache the reserved identifier and re-apply on re-click (no re-burn); re-assert
  the field value after 400ms to defeat a late async-init clear; invalidate cache
  on repository change.

## Merge images to PDF (archaeology)
Three stacked bugs (see [[archaeology_no_job_worker]]):
1. No archaeology tiff-pdf-worker → merge jobs queued forever. Created
   archeology-tiff-pdf-worker.service.
2. TiffPdfMergeJob called undefined TiffPdfMergeService::mergeFilesToPdf()
   (broke merges on BOTH instances). Added the public wrapper (atom-framework
   v2.13.11) dispatching to convertToPdfA/convertToPdf by pdf_standard.
3. Job used sfConfig/QubitSearch (absent in the framework-only worker). Guarded
   the uploads-dir lookup (dirname(__DIR__,4).'/uploads') and widened the reindex
   catch to \Throwable. Merges now complete (verified: 2–4 page PDFs attached).

## Async derivatives (v3.79.40)
- Merge: worker backgrounds `digitalobject:regen-derivatives --slug=X --force
  --index` after attaching the PDF → PDF thumbnail + ES reindex (worker can't
  reindex directly).
- External link (addDigitalObjectAction): reference stored instantly
  (createDerivatives=false, no hang), then background `regen-derivatives
  --slug=X --only-externals --force --index` downloads the remote + builds
  thumbnail + reindex. `--force` is REQUIRED (skips the y/N prompt that aborts a
  background exec).

## Open
- Merge UPLOAD step (`/tiff-pdf-merge/upload/`) may drop files (empty jobs →
  "No files to process"); pipeline itself is proven working.
