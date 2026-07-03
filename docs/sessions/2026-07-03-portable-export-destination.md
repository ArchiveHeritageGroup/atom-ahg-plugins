# 2026-07-03 — Portable export destination option (ZIP vs folder/drive)

Release: atom-ahg-plugins v3.79.48. Both instances.

Ported Heratio's destination choice to PSIS ahgPortableExportPlugin:
- ZIP file (default) = downloadable compressed archive.
- Folder / drive = uncompressed bundle built DIRECTLY on an operator-chosen writable
  directory / mounted drive (no temp staging, no ZIP, no size cap) — for collections
  too large for a ZIP or writing straight to USB.

Files: migration_destination.sql (destination + destination_path columns);
ExportPipelineService (resolveOutputDir folder-aware, finaliseOutput + directorySize
skip zip for folder, both viewer + archive paths); actions.class.php (validate folder
path existing+writable, store); indexSuccess.php (Destination radio + folder-path input
+ toggle JS, submitted via existing FormData); executeDownload (folder = "written to
folder" flash + redirect, no download). Migration applied to archive + archeology.
Verified resolveOutputDir(folder) builds under the drive path.
