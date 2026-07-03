# Portable Export — recipient README.txt in viewer packages

**Date:** 2026-07-03
**Repo:** atom-ahg-plugins · **Releases:** v3.79.53 (README) + v3.79.54 (website + copyright)
**Also:** atom-extensions-catalog v1.12.9 (guide note)
**Plugin:** ahgPortableExportPlugin
**Instances:** archive/PSIS + archaeology (both synced, md5 MATCH)

## Requirement
"Add a README in the zip/folder to explain / user manual" — then "add
https://theahg.co.za as well as copyright notice."

## What was built
Every **viewer** export now writes a plain-text **`README.txt`** at the package root, so
it travels inside the ZIP / local folder / server folder / USB alongside `index.html`.
(Archive-mode packages already carried a `README.md` via ManifestBuilder — left untouched.)

- New `ExportPipelineService::writeViewerReadme($outputDir, $export, $totalDescriptions,
  $totalObjects, $excluded, $branding)`, called right after `$packager->package()` and
  before `finaliseOutput()`, so the README is included in ZIP, folder and local-staging
  outputs alike.
- Content (personalised from the export's branding + real figures):
  - title / subtitle (branding),
  - **what it is** (self-contained offline catalogue, no server/internet),
  - **how to open** (`index.html`, any modern browser) + an editable-mode note when
    `mode = editable`,
  - USB/CD tip (keep the whole folder together),
  - **what's inside** (index.html, data/, objects/, assets/, disclosure-summary.json),
  - **contents** (description + object counts, generated date),
  - **access & confidentiality** — names the withheld total and reasons, points to
    `data/disclosure-summary.json`,
  - footer: branding footer + **https://theahg.co.za** + a **copyright notice**
    (`Copyright (c) <date('Y')> The Archive and Heritage Group (Pty) Ltd.`) that asserts
    AHG's ownership of the Portable Export software while stating that catalogue content
    remains with its rights holders / originating institution (so it never overclaims a
    client's records).
- Plain text (README.txt) so it opens on any device with no software.
- **Best-effort** — wrapped in try/catch; a README problem can never fail an export.

## Verification
- `php -l` clean.
- Rendered via reflection (`newInstanceWithoutConstructor` — the ctor needs `sfConfig`):
  branding, editable note, counts, withheld breakdown (e.g. 47 unpublished + 2 ICIP + 3
  ACL + 1 redacted = 53), website + copyright footer all correct.

## Docs
- Plugin `help.md` "What It Produces" + catalog `portable-export-user-guide.md` Output
  Structure now list `README.txt`; both `.docx` regenerated; in-app help re-imported on
  both instances.

## Files
- ahgPortableExportPlugin/lib/Services/ExportPipelineService.php (writeViewerReadme +
  call site; footer website + copyright)
- ahgPortableExportPlugin/docs/help.md (+ help.docx)
- atom-extensions-catalog/docs/portable-export-user-guide.md (+ .docx)
