# ahgAuditTrailPlugin for AtoM 2.10

Comprehensive audit trail logging plugin for AtoM 2.10 Laravel rebuild.

## Features
- Full CRUD action logging
- Authentication event tracking
- Download/access logging
- Security classification tracking
- POPIA/NARSSA compliance
- Statistics dashboard
- Export to CSV/JSON

## Installation

1. Extract to `plugins/ahgAuditTrailPlugin`
2. Run database migration: `php atom-framework/bin/install-plugin-schema.php --database=<database> --user=<user> --password=<password> --plugin=ahgAuditTrailPlugin` (from the AtoM root)
3. Enable in `apps/qubit/config/settings.yml`
4. Clear cache: `php symfony cc`

## Usage

Access via Admin menu: **Admin → Audit Trail**

## Author

The Archive and Heritage Group (Pty) Ltd
