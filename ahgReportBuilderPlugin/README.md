# Report Builder

Enterprise report builder with rich text editing (Quill.js), Word/PDF/XLSX/CSV export, drag-drop sections, templates, collaboration workflows, SQL queries, sharing, and scheduling

| | |
|---|---|
| Machine name | `ahgReportBuilderPlugin` |
| Version | 2.0.1 |
| Category | reporting |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- Rich text editing with Quill.js
- Drag-drop section-based report structure
- Word export (.docx) with cover page, TOC, styled sections
- Enhanced PDF export with branding
- Excel and CSV export
- Reusable report templates (NARSSA, GRAP 103, Accession, Condition)
- Multiple data sources (54 sources)
- Chart support (bar, line, pie, doughnut, radar, polar)
- Visual query builder with join resolution
- Raw SQL mode for administrators
- Collaboration workflow (draft, review, approved, published, archived)
- Section-level comments and review annotations
- Version history with snapshot comparison and restore
- External links with OpenGraph preview
- Internal cross-references to AtoM entities
- File attachments and image galleries
- Public sharing with expiry tokens
- Scheduled report generation (recurring and trigger-based)
- Report archive management
- Dashboard widgets
- Security clearance per section
- Live and snapshot data binding

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Database tables

Creates 15 table(s):

- `access_audit_log`
- `gallery_exhibition`
- `object_provenance`
- `custom_report`
- `report_schedule`
- `report_archive`
- `report_attachment`
- `report_comment`
- `report_definition`
- `report_link`
- `report_query`
- `report_section`
- `report_share`
- `report_template`
- `report_version`

## Installation

This plugin requires **atom-framework**. It is not optional: the framework
provides `AhgController`, `AtomFramework\*` and the routing and settings
services that this plugin builds on.

```bash
# 1. Fetch into the AtoM plugins directory as a REAL DIRECTORY.
#    A symlink fails the prefix test in pluginsAction.class.php and the
#    plugin is then invisible in the stock admin UI with no error shown.
cd <atom-root>/plugins
git clone --depth 1 --filter=blob:none --sparse \
    https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git tmp-fetch
cd tmp-fetch && git sparse-checkout set ahgReportBuilderPlugin && cd ..
mv tmp-fetch/ahgReportBuilderPlugin ./ahgReportBuilderPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgReportBuilderPlugin/database/install.sql

# 3. Enable it, then clear the cache and reload PHP-FPM.
cd <atom-root>
php symfony cc
sudo systemctl reload php8.3-fpm
```

**Enabling differs by instance shape.** Check which list governs:

```bash
grep -c 'loadPluginsFromDatabase' <atom-root>/config/ProjectConfiguration.class.php
```

- `0` (stock AtoM): plugins load from the serialised `plugins` row in
  `setting_i18n`. The `atom_plugin` table is inert and the admin screen can
  show a plugin as enabled that does not load.
- `1` or more (AHG): `atom_plugin` is the source of truth.

Verify against whichever list governs, not against the admin screen.

## Licence

AGPL-3.0-or-later. Copyright The Archive and Heritage Digital Commons Group (Pty) Ltd.
