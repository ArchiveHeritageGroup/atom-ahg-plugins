# arAuditTrailPlugin for AtoM 2.10

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

1. Extract to `plugins/arAuditTrailPlugin`
2. Run database migration: `mysql -u root -p archive < data/migrations/001_create_audit_tables.sql`
3. Enable in `apps/qubit/config/settings.yml`
4. Clear cache: `php symfony cc`

## Usage

Access via Admin menu: **Admin → Audit Trail**

## Author

The Archive and Heritage Group (Pty) Ltd
