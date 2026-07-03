# Portable Export — "This computer" local folder/drive destination

**Date:** 2026-07-03
**Repo:** atom-ahg-plugins · **Release:** v3.79.51 (patch)
**Plugin:** ahgPortableExportPlugin
**Instances:** archive/PSIS + archaeology (both synced, md5 MATCH)

## Requirement
"Portable export Folder/Drive should also be local on PC/Laptop." User chose (via
prompt) the **unzipped tree** variant: the package must be written uncompressed into
a folder the operator picks on their own machine so it runs straight off the drive.

## What was built
A third `destination` value **`local`** alongside `zip` and `folder`.

- **Server-side, `local` is identical to `zip`**: `resolveOutputDir()` returns the
  normal staging dir (`downloads/portable-exports/export-<id>/`) and `finaliseOutput()`
  builds the ZIP (only `folder` skips the zip). The staging tree survives `createZip`
  (confirmed on disk: completed exports keep both `export-N/` and `export-N.zip`), so
  the unzipped tree is available for local delivery AND a ZIP exists for fallback.
- **Client-side (File System Access API):** on completion of a `local` export, if the
  browser exposes `window.showDirectoryPicker`, a **Save to folder on this computer**
  button runs the picker, pulls the manifest, and recreates the tree inside a named
  subfolder of the chosen directory using nested
  `getDirectoryHandle`/`getFileHandle`/`createWritable` (with a subdir cache and a live
  "Saving N/M files" status). On unsupported browsers (Firefox/Safari) it falls back to
  the existing ZIP download.
- **Two new admin-gated endpoints:**
  - `GET /portable-export/api/manifest?id=` → JSON `{folder, count, total_size, files:[{path,size}]}`
    (relative, forward-slash paths from the staging tree).
  - `GET /portable-export/api/file?id=&path=` → streams one file (`application/octet-stream`).
  - Both funnel through `localStagingDir()`, which resolves the export's staging dir
    ONLY if its realpath sits under `downloads/portable-exports`. Server `folder`-mode
    operator paths resolve outside the base and are rejected; `..` traversal is rejected
    (verified: `../export-8` blocked, legit `data/catalogue.json` allowed).
- **UI:** relabelled the old option "Folder / drive" → **"Server folder / drive"** and
  added a **"This computer"** radio + note (Chromium browsers; ZIP fallback elsewhere).
  Save button also added to historical `local` rows in the exports list.

## Routing / cache note
This added two RouteLoader routes (`addRoutes`), a config change — so it required a
cache clear, unlike a pure-service edit. Cleared `cache/qubit/prod/config` on both
roots, `sudo -u www-data php symfony cc`, then a single php-fpm restart. Routes resolve
301/302 (auth redirect), not 404/500.

## Verification
- `php -l` clean on config + actions + template.
- New routes reachable on both instances (301/302 auth, not 404).
- Manifest relative-path + traversal-guard logic run against a real completed export
  (export-9): 23 files, correct paths, `..` blocked, legit paths allowed.
- Completed exports confirmed to retain the staging tree (index.html at root + data/,
  objects/ref, objects/thumb, assets/*).
- The browser folder-picker flow itself is not headless-testable (needs Chrome + login
  + user gesture), but every server endpoint it depends on is proven.

## No DB migration
The `destination` column already exists (`varchar(20)`, previously `zip|folder`); it now
also stores `local`. No schema change.

## Files
- ahgPortableExportPlugin/config/ahgPortableExportPluginConfiguration.class.php (2 routes)
- ahgPortableExportPlugin/modules/portableExport/actions/actions.class.php (destination
  whitelist + apiManifest + apiFile + localStagingDir helper)
- ahgPortableExportPlugin/modules/portableExport/templates/indexSuccess.php (3-way
  destination UI + File System Access API writer + save buttons)
