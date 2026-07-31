# ahgPreservation - package download UX + Add-Digital-Object lookup (v3.88.12)

Date: 2026-07-31
Instance: PSIS / archive
Plugin: ahgPreservationPlugin

## Reported
1. `/admin/preservation/package/1/download/` -> "Page Not Found".
2. "I do not see Download on other packages created."

## Diagnosis
- Package 1 ("Test SIP Package", sip) was `exported` on 2026-01-19 with
  `export_path = uploads/exports/8298c33d-...zip`. That zip has since been
  cleaned off disk (only `uploads/exports/preservica/` remains), so
  `executePackageDownload` correctly `forward404`'d on the missing file. The
  route (`/admin/preservation/package/:id/download`) and action were fine; the
  trailing slash was NOT the cause (route matched, returned 302->login).
- "No download on other packages" is by design: the Download button renders only
  when `export_path` is set, which happens only at the Export step. Packages 2 & 3
  are `draft`; package 4 ("test3") is `complete` and needs its **Export Package**
  button clicked to produce an archive.

## Changes (v3.88.12)
- `executePackageDownload`: when `export_path` is missing OR the file no longer
  exists on disk, set an `error` flash ("The export archive ... is no longer
  available on disk. Please re-export the package to download it again.") and
  redirect to `packageView`, instead of a bare 404. (Missing-package still 404s.)
- Bundled the earlier OAIS Add-Digital-Object lookup: `apiSearchObjects` API +
  route + `packageEditSuccess.php` autocomplete (title/filename search box
  replacing the numeric-ID input).

## Lifecycle reference
draft -> complete -> validated -> exported. Download appears only at `exported`
(export_path populated by `apiPackageExport`).

---

## Follow-up same day: package view 500, hierarchy selection, one-click build+export

Reported: package 5 view "only text" (actually a 500), no Download on non-exported
packages, "add download package immediately / run the cron on save", "make sure
parent and child (collection) can be selected".

### Fixes
- **packageView 500**: `array_slice($objects, ...)` got AtoM's escaper-wrapped
  `sfOutputEscaperArrayDecorator` (iterates but is not a raw array). Unwrapped via
  `sfOutputEscaper::unescape()` before slicing. This was the "only text" symptom.
- **Dead-end complete package**: packageView showed neither Edit (draft-only) nor
  Download (export_path-only) for a `complete`/`validated` package. Added an
  **Export Package** button (routes to the editor's export control) and broadened
  **Edit** to any non-exported status.
- **One-click Build & Export** (`buildAndExportPackage` service + `apiPackageBuildExport`
  action/route + sidebar button): builds (if needed) then exports in one pass so the
  download is immediately ready. Re-runnable - resets an exported/errored package (or
  one whose BagIt tree was cleaned off disk) back to draft, rebuilds, re-exports. This
  also re-heals the stale-export case (package 1).
- **Build & export on save**: `build_on_save` checkbox on the editor; on update save
  with object_count>0, runs buildAndExportPackage and flashes "download is ready".
- **Parent + collection selection**: editor now has a **Parent Package** dropdown
  (`parent_package_id`, excludes self) and a **Linked Collection / Description**
  autocomplete (`information_object_id`) backed by new `apiSearchDescriptions`
  (searches `information_object_i18n.title` / `io.identifier`). Both persisted in
  create/update; linked-collection title resolved for display on load.

### Verified (temp superuser, curl)
packageView 5 -> 200; editor renders Parent Package / Linked Collection / Build &
Export / buildOnSave / collectionSearch; descriptions search returns real rows
(id/title/reference/level); all 4 new routes resolve.

### Download filename = package title
`executePackageDownload` now names the download after the package title/description
(sanitised, e.g. "Test SIP Package" -> `Test_SIP_Package.zip`) instead of the internal
UUID, keeping the archive's real extension and adding an RFC 5987 `filename*` UTF-8
variant for accented/non-Latin titles. Verified: package 5 downloads as `feedback.zip`.
