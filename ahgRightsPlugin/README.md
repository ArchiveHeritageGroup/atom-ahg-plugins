# ahgRightsPlugin

Comprehensive Rights Management for AtoM 2.10 Laravel Rebuild

## Features

- **PREMIS Rights Basis**: Copyright, License, Statute, Donor, Policy
- **RightsStatements.org**: Full vocabulary integration
- **Creative Commons**: License selector with all CC variants
- **Traditional Knowledge Labels**: Local Contexts TK and BC labels
- **Embargoes**: Time-based restrictions with auto-release
- **Orphan Works**: Due diligence tracking and documentation
- **Territory Restrictions**: GDPR/geographic access control
- **Access Derivatives**: Watermarking and resolution controls
- **Centralized View**: Integrates with ahgDisplayPlugin

## Installation

```bash
# 1. Extract to plugins directory
tar -xzf ahgRightsPlugin.tar.gz -C /usr/share/nginx/archive/plugins/

# 2. Install database tables
mysql -u root archive < /usr/share/nginx/archive/plugins/ahgRightsPlugin/data/migrations/install.sql

# 3. Enable plugin in ProjectConfiguration.class.php
# Add 'ahgRightsPlugin' to the enabled plugins array

# 4. Clear cache
php symfony cc
```

## Integration with ahgDisplayPlugin

The plugin automatically integrates with ahgDisplayPlugin if installed:

1. Copy `DisplayRightsExtension.php` to ahgDisplayPlugin:
   ```bash
   cp /usr/share/nginx/archive/plugins/ahgRightsPlugin/lib/DisplayRightsExtension.php \
      /usr/share/nginx/archive/plugins/ahgDisplayPlugin/lib/
   ```

2. The DisplayService will automatically include rights data

3. Rights section appears in detail views

## Usage

### In Templates (ISAD/Museum)

Include the rights panel partial:
```php
<?php include_partial('rights/rightsPanel', ['resource' => $resource]); ?>
```

### Service API

```php
$service = RightsService::getInstance();

// Get rights for object
$rights = $service->getRightsForObject($objectId);

// Check access
$check = $service->checkAccess($objectId, 'information_object', $userId);

// Set embargo
$service->setEmbargo([
    'object_id' => $objectId,
    'embargo_type' => 'full',
    'reason' => 'donor_restriction',
    'end_date' => '2025-12-31',
    'auto_release' => true,
]);

// Assign TK Label
$service->assignTkLabel($objectId, $labelId, [
    'community_name' => 'Example Community',
    'provenance_statement' => 'Traditional knowledge holder',
]);
```

## Database Tables

| Table | Description |
|-------|-------------|
| `rights_statement` | RightsStatements.org vocabulary |
| `rights_cc_license` | Creative Commons licenses |
| `rights_tk_label` | Traditional Knowledge labels |
| `rights_record` | Main rights records |
| `rights_granted` | Granted rights (acts) |
| `rights_embargo` | Embargo records |
| `rights_orphan_work` | Orphan work documentation |
| `rights_territory` | Territory restrictions |
| `rights_derivative_profile` | Watermark/access profiles |
| `rights_audit_log` | Audit trail |

## URLs

| URL | Description |
|-----|-------------|
| `/rights/:slug` | View rights for object |
| `/rights/:slug/add` | Add rights record |
| `/rights/:slug/edit/:id` | Edit rights record |
| `/rights/admin` | Admin dashboard |
| `/api/rights/check/:id` | API: Check access |

## South African Compliance

- **PAIA**: Public access request integration
- **POPIA**: Privacy-aware access controls
- **Copyright Act**: Local jurisdiction support

## License

GNU Affero General Public License v3.0
