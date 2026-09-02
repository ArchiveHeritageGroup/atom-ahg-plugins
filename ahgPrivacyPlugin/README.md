# Privacy Compliance Management

POPIA/GDPR Privacy Compliance Management - DSAR tracking, Breach register, ROPA, Consent management

| | |
|---|---|
| Machine name | `ahgPrivacyPlugin` |
| Version | 1.1.14 |
| Category | compliance |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group (Pty) Ltd |

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Optional integrations

These are used when present and are not required:

- `ahgSecurityClearancePlugin`

## Database tables

Creates 34 table(s):

- `digital_object_metadata`
- `privacy_audit_log`
- `privacy_breach`
- `privacy_breach_i18n`
- `privacy_breach_incident`
- `privacy_breach_notification`
- `privacy_complaint`
- `privacy_config`
- `privacy_consent`
- `privacy_consent_i18n`
- `privacy_consent_log`
- `privacy_consent_record`
- `privacy_data_inventory`
- `privacy_dsar`
- `privacy_dsar_i18n`
- `privacy_dsar_log`
- `privacy_dsar_request`
- `privacy_jurisdiction`
- `privacy_notification`
- `privacy_officer`
- `privacy_paia_request`
- `privacy_processing_activity`
- `privacy_processing_activity_i18n`
- `privacy_retention_schedule`
- `privacy_template`
- `privacy_approval_log`
- `privacy_visual_redaction`
- `privacy_redaction_cache`
- `privacy_jurisdiction_registry`
- `privacy_lawful_basis`
- `privacy_special_category`
- `privacy_request_type`
- `privacy_compliance_rule`
- `privacy_institution_config`

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
cd tmp-fetch && git sparse-checkout set ahgPrivacyPlugin && cd ..
mv tmp-fetch/ahgPrivacyPlugin ./ahgPrivacyPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgPrivacyPlugin/database/install.sql

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
