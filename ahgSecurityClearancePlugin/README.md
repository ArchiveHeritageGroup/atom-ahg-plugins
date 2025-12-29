# ahgSecurityClearancePlugin v2.0.0

A comprehensive security classification and access control plugin for Access to Memory (AtoM) 2.x.

## Features

### 1. Clearance Expiry with Renewal Workflow
- Time-limited security clearances with expiry dates
- Automatic expiry processing via scheduled task
- User-initiated renewal requests
- Admin approval workflow for renewals
- Email notifications for expiring clearances

### 2. Two-Factor Authentication for Classified Access
- Require 2FA for Secret and Top Secret classifications
- Session-based 2FA verification
- Configurable session duration
- Automatic session cleanup

### 3. Compartmentalised Access
- Project-based access beyond standard hierarchy
- Named compartments (e.g., ORCON, NOFORN, SPECIAL_PROJECT)
- Minimum clearance requirements per compartment
- Optional briefing requirements
- Independent expiry per compartment access

### 4. Access Request Workflow
- Users can request access to restricted materials
- Priority levels (normal, urgent, immediate)
- Justification requirements
- Admin review and approval
- Time-limited access grants

### 5. Declassification Scheduling
- Schedule automatic downgrades
- Configurable target classification
- Review date tracking
- Batch processing via scheduled task
- Audit trail for all changes

### 6. Security Audit Reports
- Comprehensive access logging
- Filter by user, object, date, action
- CSV export capability
- Visual activity charts
- Denial tracking

### 7. Dynamic Watermarking
- Automatic watermarks on classified downloads
- Unique tracking codes per download
- Watermark trace functionality
- Support for PDF, images, and DOCX files

## Installation

### 1. Copy Plugin Files
```bash
cp -r ahgSecurityClearancePlugin /usr/share/nginx/atom/plugins/
```

### 2. Install Database Schema
```bash
mysql -u atom -p atom < /usr/share/nginx/atom/plugins/ahgSecurityClearancePlugin/lib/install.sql
```

### 3. Enable the Plugin
Add to `apps/qubit/config/settings.yml`:
```yaml
all:
  .settings:
    plugins:
      - ahgSecurityClearancePlugin
```

### 4. Clear Cache
```bash
php symfony cc
```

### 5. Set Up Scheduled Task
Add to crontab:
```bash
0 1 * * * /usr/bin/php /var/www/atom/symfony security:process
```

## Configuration

### Classification Levels
Default levels are seeded during installation:

| Level | Code | Requires 2FA | Watermark |
|-------|------|--------------|-----------|
| 0 | PUBLIC | No | No |
| 1 | INTERNAL | No | No |
| 2 | RESTRICTED | No | No |
| 3 | CONFIDENTIAL | No | Yes |
| 4 | SECRET | Yes | Yes |
| 5 | TOP_SECRET | Yes | Yes |

### Default Compartments

| Code | Description |
|------|-------------|
| ORCON | Originator Controlled |
| NOFORN | No Foreign Nationals |
| SPECIAL_PROJECT | Special Projects |
| HISTORICAL | Historical Records |

## Routes

### Dashboard
- `/security` - Main security dashboard

### Classification Management
- `/:slug/security` - View object classification
- `/:slug/security/classify` - Classify/reclassify object

### User Clearances
- `/security/clearances` - List all user clearances
- `/security/clearance/:user_id` - View/edit user clearance
- `/security/clearance/grant` - Grant new clearance
- `/security/clearance/revoke` - Revoke clearance

### Access Requests
- `/security/request/:object_id` - Request access form
- `/security/requests` - Pending requests (admin)
- `/security/request/:id/review` - Review request
- `/security/my-requests` - User's own requests

### 2FA
- `/security/2fa` - Two-factor authentication page
- `/security/2fa/verify` - Verify 2FA code

### Compartments
- `/security/compartments` - List compartments
- `/security/compartment/:id/access` - Manage access

### Declassification
- `/security/declassification` - Declassification dashboard
- `/security/declassify/:object_id` - Process declassification

### Audit & Reports
- `/security/audit` - Full audit log
- `/security/audit/user/:user_id` - User audit
- `/security/audit/object/:object_id` - Object audit
- `/security/report` - Security reports
- `/security/report/export` - Export CSV

### Watermark
- `/security/watermark/:code` - Trace watermark

## Database Tables

1. `security_classification` - Classification levels
2. `user_security_clearance` - User clearances
3. `object_security_classification` - Object classifications
4. `security_compartment` - Compartment definitions
5. `user_compartment_access` - User compartment access
6. `object_compartment` - Object compartment assignments
7. `security_access_request` - Access request workflow
8. `security_access_log` - Comprehensive audit log
9. `security_clearance_history` - Clearance change history
10. `security_watermark_log` - Watermark tracking
11. `security_2fa_session` - 2FA sessions
12. `security_declassification_schedule` - Scheduled declassifications

## Services

### SecurityClearanceService
Main service class providing all security operations:
- Classification management
- User clearance management
- Access control checks
- Request workflow
- Audit logging
- Statistics

### WatermarkService
Handles document watermarking:
- PDF watermarking
- Image watermarking (ImageMagick/GD)
- DOCX header watermarking
- Watermark tracing

## Scheduled Task

Run daily via cron to process:
- Automatic declassifications
- Expired clearance deactivation
- Expiry warning emails
- 2FA session cleanup
- Audit log retention

```bash
php symfony security:process
```

## Dependencies

- AtoM 2.x
- PHP 8.3+
- Laravel Query Builder (via atom-framework)
- ImageMagick or GD (for watermarking)
- pdftk or Ghostscript (for PDF watermarking)

## Author

Johan Pieterse <johan@theahg.co.za>
The Archive and Heritage Group

## License

GNU Affero General Public License v3.0
