# Loan Management

Shared loan management for GLAM institutions - Museums, Galleries, Archives, Libraries, and Digital Assets. Based on Collections Procedures and international GLAM standards.

| | |
|---|---|
| Machine name | `ahgLoanPlugin` |
| Version | 1.0.1 |
| Category | loans |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- Inbound and outbound loans
- Multi-sector support (Museum, Gallery, Archive, Library, DAM)
- Facility reports for venue assessment
- Condition reports with images
- Courier and shipment tracking
- Loan extensions and status history
- Automated notifications
- Cost tracking
- Insurance management

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Database tables

Creates 19 table(s):

- `loan`
- `loan_document`
- `loan_extension`
- `loan_object`
- `ahg_loan`
- `ahg_loan_object`
- `ahg_loan_document`
- `ahg_loan_extension`
- `ahg_loan_status_history`
- `ahg_loan_facility_report`
- `ahg_loan_facility_image`
- `ahg_loan_condition_report`
- `ahg_loan_condition_image`
- `ahg_loan_courier`
- `ahg_loan_shipment`
- `ahg_loan_shipment_event`
- `ahg_loan_notification_template`
- `ahg_loan_notification_log`
- `ahg_loan_cost`

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
cd tmp-fetch && git sparse-checkout set ahgLoanPlugin && cd ..
mv tmp-fetch/ahgLoanPlugin ./ahgLoanPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgLoanPlugin/database/install.sql

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
