Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** ingest-preservation

### Features to add to Heratio (present in PSIS/AtoM)
- **[medium]** Sector-specific CSV import tasks (museum/archive/library/gallery/dam) — _PSIS plugin: ahgDataMigrationPlugin_: AtoM has museumCsvImportTask, archivesCsvImportTask, libraryCsvImportTask, galleryCsvImportTask, damCsvImportTask. Heratio uses unified DataMigrationService.php instead of separate sector-specific tasks.

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.