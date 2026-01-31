# ahgICIPPlugin Development Checkpoint

**Date:** 2026-01-29
**Status:** READY FOR TESTING

## Completed Files

### 1. Plugin Configuration
- [x] `ahgICIPPluginConfiguration.class.php` - Main plugin class

### 2. Database Schema
- [x] `database/install.sql` - Complete schema with:
  - `icip_community` - Community registry
  - `icip_consent` - Consent management
  - `icip_cultural_notice_type` - Notice type definitions (with default data)
  - `icip_cultural_notice` - Cultural notices on objects
  - `icip_notice_acknowledgement` - User acknowledgements
  - `icip_tk_label_type` - TK/BC label definitions (with Local Contexts data)
  - `icip_tk_label` - TK labels on objects
  - `icip_consultation` - Consultation log
  - `icip_access_restriction` - Access restrictions
  - `icip_object_summary` - Materialized summary view
  - `icip_config` - Plugin configuration

### 3. Routing
- [x] `config/routing.yml` - All routes defined

### 4. Services
- [x] `lib/ahgICIPService.class.php` - Core service with:
  - Consent status/scope constants and options
  - Object ICIP data retrieval methods
  - Access checking logic
  - Acknowledgement tracking
  - Summary updates
  - Dashboard statistics
  - Reporting queries

### 5. Module Actions
- [x] `modules/icip/actions/actions.class.php` - Controller with:
  - Dashboard
  - Community CRUD
  - Consent management
  - Consultation log
  - TK Labels management
  - Cultural notices
  - Notice types management
  - Access restrictions
  - Reports (overview, pending, expiry, community)
  - Object-specific ICIP pages
  - Acknowledgement handling
  - API endpoints

### 6. Templates
- [x] `modules/icip/templates/dashboardSuccess.php`
- [x] `modules/icip/templates/communitiesSuccess.php`
- [x] `modules/icip/templates/communityEditSuccess.php`
- [x] `modules/icip/templates/communityViewSuccess.php`
- [x] `modules/icip/templates/consentListSuccess.php`
- [x] `modules/icip/templates/consentEditSuccess.php`
- [x] `modules/icip/templates/consentViewSuccess.php`
- [x] `modules/icip/templates/consultationsSuccess.php`
- [x] `modules/icip/templates/consultationEditSuccess.php`
- [x] `modules/icip/templates/consultationViewSuccess.php`
- [x] `modules/icip/templates/tkLabelsSuccess.php`
- [x] `modules/icip/templates/noticesSuccess.php`
- [x] `modules/icip/templates/noticeTypesSuccess.php`
- [x] `modules/icip/templates/restrictionsSuccess.php`
- [x] `modules/icip/templates/reportsSuccess.php`
- [x] `modules/icip/templates/reportPendingSuccess.php`
- [x] `modules/icip/templates/reportExpirySuccess.php`
- [x] `modules/icip/templates/reportCommunitySuccess.php`
- [x] `modules/icip/templates/objectIcipSuccess.php`
- [x] `modules/icip/templates/objectConsentSuccess.php`
- [x] `modules/icip/templates/objectNoticesSuccess.php`
- [x] `modules/icip/templates/objectLabelsSuccess.php`
- [x] `modules/icip/templates/objectRestrictionsSuccess.php`
- [x] `modules/icip/templates/objectConsultationsSuccess.php`

### 7. CSS
- [x] `css/icip.css` - Plugin styles including:
  - TK/BC Label styling
  - Cultural notice severity styling
  - Consent status badges
  - Restriction indicators
  - Dashboard widgets
  - Timeline styling
  - Responsive design
  - Print styles

## Still To Do (Future Enhancements)

### 8. Images
- [ ] `images/tk-labels/` - TK Label icons (can download from Local Contexts)

### 9. Integration
- [ ] Add ICIP tab to information object edit template (theme override)
- [ ] Add ICIP notice display to information object view (theme partial)
- [ ] Integration with ahgSecurityClearancePlugin
- [ ] Integration with ahgAuditTrailPlugin

### 10. Installation
- [ ] Test database migration
- [ ] Create symlink in plugins/
- [ ] Enable plugin via `php bin/atom extension:enable ahgICIPPlugin`

## Directory Structure

```
/usr/share/nginx/archive/atom-ahg-plugins/ahgICIPPlugin/
├── ahgICIPPluginConfiguration.class.php  ✓
├── ICIP.md                               ✓
├── config/
│   └── routing.yml                       ✓
├── css/
│   └── icip.css                          ✓
├── database/
│   └── install.sql                       ✓
├── images/
│   └── tk-labels/                        (future)
├── js/                                   (future)
├── lib/
│   └── ahgICIPService.class.php          ✓
└── modules/
    └── icip/
        ├── actions/
        │   └── actions.class.php         ✓
        └── templates/
            ├── dashboardSuccess.php               ✓
            ├── communitiesSuccess.php             ✓
            ├── communityEditSuccess.php           ✓
            ├── communityViewSuccess.php           ✓
            ├── consentListSuccess.php             ✓
            ├── consentEditSuccess.php             ✓
            ├── consentViewSuccess.php             ✓
            ├── consultationsSuccess.php           ✓
            ├── consultationEditSuccess.php        ✓
            ├── consultationViewSuccess.php        ✓
            ├── tkLabelsSuccess.php                ✓
            ├── noticesSuccess.php                 ✓
            ├── noticeTypesSuccess.php             ✓
            ├── restrictionsSuccess.php            ✓
            ├── reportsSuccess.php                 ✓
            ├── reportPendingSuccess.php           ✓
            ├── reportExpirySuccess.php            ✓
            ├── reportCommunitySuccess.php         ✓
            ├── objectIcipSuccess.php              ✓
            ├── objectConsentSuccess.php           ✓
            ├── objectNoticesSuccess.php           ✓
            ├── objectLabelsSuccess.php            ✓
            ├── objectRestrictionsSuccess.php      ✓
            └── objectConsultationsSuccess.php     ✓
```

## Installation Commands

```bash
# 1. Run database migration
mysql -u root archive < /usr/share/nginx/archive/atom-ahg-plugins/ahgICIPPlugin/database/install.sql

# 2. Create symlink
ln -sf /usr/share/nginx/archive/atom-ahg-plugins/ahgICIPPlugin /usr/share/nginx/archive/plugins/ahgICIPPlugin

# 3. Clear cache
rm -rf /usr/share/nginx/archive/cache/* && php /usr/share/nginx/archive/symfony cc

# 4. Enable plugin
php /usr/share/nginx/archive/bin/atom extension:enable ahgICIPPlugin

# 5. Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

## Access URLs

After installation:
- Dashboard: `/icip`
- Communities: `/icip/communities`
- Consent Records: `/icip/consent`
- Consultations: `/icip/consultations`
- TK Labels: `/icip/tk-labels`
- Cultural Notices: `/icip/notices`
- Restrictions: `/icip/restrictions`
- Reports: `/icip/reports`

Object-specific ICIP (replace `{slug}` with record slug):
- ICIP Overview: `/{slug}/icip`
- Consent: `/{slug}/icip/consent`
- Notices: `/{slug}/icip/notices`
- Labels: `/{slug}/icip/labels`
- Restrictions: `/{slug}/icip/restrictions`
- Consultations: `/{slug}/icip/consultations`

## Features Summary

### Community Registry
- Register Aboriginal and Torres Strait Islander communities
- Track contact information and PBC details
- Native Title reference tracking
- State/Territory filtering

### Consent Management
- Track consent status for records
- Define consent scope (preservation, access, reproduction, etc.)
- Consent expiry tracking and alerts
- Link consents to communities

### Cultural Notices
- Pre-defined notice types (deceased persons, sacred/secret, gender restrictions)
- Custom notice types
- Severity levels (info, warning, critical)
- User acknowledgement tracking
- Access blocking capabilities

### TK Labels (Local Contexts)
- Full support for Traditional Knowledge (TK) Labels
- Biocultural (BC) Labels
- Track community vs institution application
- Link to Local Contexts Hub projects

### Access Restrictions
- ICIP-specific access controls
- Gender restrictions
- Seasonal restrictions
- Mourning period restrictions
- Override standard security clearance

### Consultation Log
- Track all community consultations
- Multiple consultation types
- Follow-up tracking
- Link to specific records

### Reporting
- Dashboard with key metrics
- Pending consultation report
- Consent expiry report
- Community-specific reports
