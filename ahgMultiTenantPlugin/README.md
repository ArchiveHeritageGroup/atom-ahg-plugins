# ahgMultiTenantPlugin

**Version:** 1.2.0
**Category:** Security
**Status:** Active Development

Multi-tenancy plugin for AtoM providing dedicated tenant management with status control, user hierarchy, and custom branding per tenant.

## Features

- **Tenant Management**: Full CRUD operations with dedicated database tables
- **Tenant Status**: Active, Suspended, and Trial states with automatic expiration
- **User Hierarchy**: Owner > Super User > Editor > Contributor > Viewer
- **Repository Isolation**: Users only see assigned tenants/repositories
- **Custom Branding**: Per-tenant colors, logos, and custom CSS
- **Settings Override**: Tenant-specific settings that override global defaults
- **Theme Agnostic**: Branding works with any AtoM theme

## What's New in v1.1.0

- **Dedicated Database Tables**: `heritage_tenant`, `heritage_tenant_user`, `heritage_tenant_settings_override`
- **Tenant Status Management**: Activate, suspend, and manage trial periods
- **Extended Role System**: Owner, Super User, Editor, Contributor, Viewer roles
- **Admin Dashboard**: Statistics, filtering, and bulk operations
- **Trial Management**: Configurable trial periods with extension capability
- **Migration Support**: Backward compatible with legacy repository-based settings
- **Domain Routing (Issue #85)**: Automatic tenant resolution from subdomain/custom domain

## Database Schema

### heritage_tenant
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| code | VARCHAR(50) | Unique tenant identifier |
| name | VARCHAR(255) | Display name |
| domain | VARCHAR(255) | Custom domain (optional) |
| subdomain | VARCHAR(100) | Subdomain prefix (optional) |
| settings | JSON | Tenant-specific settings |
| status | ENUM | `active`, `suspended`, `trial` |
| trial_ends_at | DATETIME | Trial expiration date |
| suspended_at | DATETIME | Suspension timestamp |
| suspended_reason | VARCHAR(500) | Reason for suspension |
| repository_id | INT | Link to AtoM repository |
| contact_name | VARCHAR(255) | Primary contact |
| contact_email | VARCHAR(255) | Contact email |
| created_at | DATETIME | Creation timestamp |

## Domain Routing (Issue #85)

The plugin supports automatic tenant resolution from HTTP host:

### Resolution Order

1. **Custom Domain** (exact match): `archive.institution.org` → Tenant
2. **Subdomain** (pattern match): `tenant.heritage.example.com` → Tenant
3. **Session** (fallback): User's previously selected tenant
4. **Unknown Domain** → Error page or redirect

### Configuration

Add to `apps/qubit/config/app.yml`:

```yaml
all:
  multi_tenant_base_domain: heritage.example.com
  multi_tenant_domain_routing: true
  multi_tenant_excluded_domains: [localhost, 127.0.0.1]
  multi_tenant_unknown_action: error  # or 'redirect'
  multi_tenant_unknown_redirect: https://heritage.example.com
```

Or via database settings:

```sql
INSERT INTO ahg_settings (setting_key, setting_value, setting_group) VALUES
('multi_tenant_base_domain', 'heritage.example.com', 'multi_tenant'),
('multi_tenant_domain_routing', '1', 'multi_tenant'),
('multi_tenant_excluded_domains', 'localhost,127.0.0.1', 'multi_tenant');
```

### Subdomain Setup

1. Configure wildcard DNS: `*.heritage.example.com → server IP`
2. Obtain wildcard SSL certificate
3. Configure nginx (see `config/nginx/multi-tenant.conf`)
4. Set tenant subdomains in Admin > Tenants

Example:
- Base domain: `heritage.example.com`
- Tenant subdomain: `national-archives`
- Result: `https://national-archives.heritage.example.com`

### Custom Domain Setup

1. Add DNS A/CNAME record for custom domain
2. Obtain SSL certificate for the domain
3. Add nginx server block
4. Set tenant's custom domain in Admin > Tenants

Example:
- Custom domain: `archive.institution.org`
- Points to same server as main site
- Tenant automatically resolved

### Nginx Configuration

See `config/nginx/multi-tenant.conf` for complete examples including:
- Wildcard subdomain configuration
- Custom domain server blocks
- SSL certificate setup (Let's Encrypt)
- Security headers

### TenantResolver Service

```php
use AhgMultiTenant\Services\TenantResolver;

// Resolve tenant from current request
$tenant = TenantResolver::resolveFromHost();

// Resolve specific host
$tenant = TenantResolver::resolveFromHost('tenant.heritage.example.com');

// Get resolution details (for debugging)
$details = TenantResolver::getResolutionDetails();
// Returns: ['host', 'subdomain', 'tenant', 'resolution_method']

// Generate tenant URL
$url = TenantResolver::generateTenantUrl($tenant, '/browse');

// Check if current request is tenant-specific
if (TenantResolver::isTenantRequest()) {
    // Handle tenant request
}
```

### heritage_tenant_user
| Column | Type | Description |
|--------|------|-------------|
| tenant_id | INT | FK to heritage_tenant |
| user_id | INT | FK to user |
| role | ENUM | `owner`, `super_user`, `editor`, `contributor`, `viewer` |
| is_primary | TINYINT | User's primary tenant flag |
| assigned_at | DATETIME | Assignment timestamp |
| assigned_by | INT | User who made assignment |

## User Hierarchy

### 1. Administrator (AtoM Admin)
- AtoM administrator group member (group_id = 100)
- Sees ALL tenants and repositories
- Can create, edit, suspend, and delete tenants
- Can assign any role including Owner
- Can manage all settings

### 2. Owner
- Highest tenant-level role
- Full control over tenant settings
- Can assign Super Users and below
- At least one Owner must exist per tenant

### 3. Super User
- Can assign Editors, Contributors, and Viewers
- Can manage tenant branding
- Cannot assign Owners or other Super Users

### 4. Editor
- Standard editing permissions
- Cannot manage users

### 5. Contributor
- Can add content
- Limited editing permissions

### 6. Viewer
- Read-only access

## Installation

1. Enable the plugin:
```bash
php bin/atom extension:enable ahgMultiTenantPlugin
```

2. Run database migrations:
```bash
mysql -u root archive < plugins/ahgMultiTenantPlugin/database/install.sql
```

3. Clear cache:
```bash
php symfony cc
```

4. (Optional) Migrate existing data:
```bash
mysql -u root archive -e "CALL migrate_tenant_data();"
```

5. Add tenant switcher to your theme header:
```php
<?php include_component('tenantSwitcher', 'switcher') ?>
```

## Routes

### Admin Routes (Administrator only)
| Route | URL | Description |
|-------|-----|-------------|
| `tenant_admin` | `/admin/tenants` | Dashboard with statistics |
| `tenant_admin_create` | `/admin/tenants/create` | Create new tenant |
| `tenant_admin_edit_tenant` | `/admin/tenants/:id/edit-tenant` | Edit tenant |
| `tenant_admin_activate` | `/admin/tenants/:id/activate` | Activate tenant |
| `tenant_admin_suspend` | `/admin/tenants/:id/suspend` | Suspend tenant |
| `tenant_admin_extend_trial` | `/admin/tenants/:id/extend-trial` | Extend trial |
| `tenant_admin_delete` | `/admin/tenants/:id/delete` | Delete tenant |

### User Management Routes
| Route | URL | Description |
|-------|-----|-------------|
| `tenant_admin_assign_user` | `/admin/tenants/assign-user` | Assign user to tenant |
| `tenant_admin_remove_user` | `/admin/tenants/remove-user` | Remove user |
| `tenant_admin_update_user_role` | `/admin/tenants/update-user-role` | Change role |

### Legacy Routes (Repository-based)
| Route | URL | Description |
|-------|-----|-------------|
| `tenant_admin_super_users` | `/admin/tenants/:id/super-users` | Manage super users |
| `tenant_users` | `/tenant/:id/users` | Manage users |
| `tenant_branding` | `/tenant/:id/branding` | Manage branding |
| `tenant_switch` | `/tenant/switch/:id` | Switch repository |

## Services

### TenantService

Primary service for tenant CRUD and status management.

```php
use AhgMultiTenant\Services\TenantService;

// Create tenant
$result = TenantService::create([
    'name' => 'New Organization',
    'code' => 'new-org',
    'status' => 'trial',
    'trial_days' => 30,
    'contact_email' => 'admin@example.com',
], $currentUserId);

// Update tenant
$result = TenantService::update($tenantId, [
    'name' => 'Updated Name',
    'domain' => 'custom.example.com',
], $currentUserId);

// Activate tenant
$result = TenantService::activate($tenantId, $currentUserId);

// Suspend tenant
$result = TenantService::suspend($tenantId, 'Payment overdue', $currentUserId);

// Extend trial
$result = TenantService::extendTrial($tenantId, 14, $currentUserId);

// Assign user with role
$result = TenantService::assignUser($tenantId, $userId, 'editor', $currentUserId);

// Get statistics
$stats = TenantService::getStatistics();
// Returns: ['total', 'active', 'suspended', 'trial', 'trial_expiring_soon', 'trial_expired']

// Get/Set tenant-specific settings
$value = TenantService::getSetting($tenantId, 'some_setting', $default);
TenantService::setSetting($tenantId, 'some_setting', $value, $currentUserId);
```

### Tenant Model

```php
use AhgMultiTenant\Models\Tenant;

// Find tenant
$tenant = Tenant::find($id);
$tenant = Tenant::findByCode('tenant-code');
$tenant = Tenant::findByRepository($repositoryId);
$tenant = Tenant::findByDomain('custom.example.com');

// Get all tenants with filters
$tenants = Tenant::all(['status' => 'active', 'search' => 'query']);

// Check status
$tenant->isActive();
$tenant->isSuspended();
$tenant->isTrial();
$tenant->isTrialExpired();
$tenant->canAccess(); // Active or valid trial

// Get users
$users = $tenant->getUsers();
$userCount = $tenant->getUserCount();

// Settings
$value = $tenant->getSetting('key', $default);
$tenant->setSetting('key', $value);
```

### TenantUser Model

```php
use AhgMultiTenant\Models\TenantUser;

// Role constants
TenantUser::ROLE_OWNER;
TenantUser::ROLE_SUPER_USER;
TenantUser::ROLE_EDITOR;
TenantUser::ROLE_CONTRIBUTOR;
TenantUser::ROLE_VIEWER;

// Find assignment
$assignment = TenantUser::findByTenantAndUser($tenantId, $userId);

// Get tenants for user
$tenants = TenantUser::getTenantsForUser($userId);

// Get users for tenant
$users = TenantUser::getUsersForTenant($tenantId, $role);

// Check role
TenantUser::hasMinimumRole($userId, $tenantId, 'editor');

// Assign/Remove
TenantUser::assign($tenantId, $userId, 'editor', $assignedBy);
TenantUser::remove($tenantId, $userId);

// Update role
TenantUser::updateRole($tenantId, $userId, 'super_user');

// Primary tenant
TenantUser::setPrimaryTenant($userId, $tenantId);
$primary = TenantUser::getPrimaryTenant($userId);
```

### TenantContext

Manages the current tenant context.

```php
use AhgMultiTenant\Services\TenantContext;

// Get/Set current tenant
$tenantId = TenantContext::getCurrentTenantId();
$tenant = TenantContext::getCurrentTenant();
TenantContext::setCurrentTenant($tenantId);

// Repository context (legacy)
$repoId = TenantContext::getCurrentRepositoryId();
TenantContext::setCurrentRepository($repoId);

// User access
TenantContext::canAccessTenant($userId, $tenantId);
TenantContext::getUserTenantRole($userId, $tenantId);
$tenants = TenantContext::getUserTenants($userId);

// Admin checks
TenantContext::isAdmin($userId);
TenantContext::isViewAllMode();
TenantContext::hasMinimumRoleInCurrentTenant($userId, 'editor');

// Query filtering
TenantContext::applyRepositoryFilter($query, 'repository_id');

// Cache
TenantContext::clearCache();
```

### TenantAccess

Access control for tenants.

```php
use AhgMultiTenant\Services\TenantAccess;

// Access checks
TenantAccess::canAccessTenant($userId, $tenantId);
TenantAccess::canManageTenantUsers($userId, $tenantId);
TenantAccess::canManageTenantSettings($userId, $tenantId);
TenantAccess::canManageTenantStatus($userId); // Admin only

// User assignment
$result = TenantAccess::assignUserToTenant($userId, $tenantId, 'editor', $assignedBy);
$result = TenantAccess::removeUserFromTenant($userId, $tenantId, $removedBy);
$result = TenantAccess::updateUserTenantRole($userId, $tenantId, 'super_user', $updatedBy);
```

### TenantBranding

Per-tenant branding management.

```php
use AhgMultiTenant\Services\TenantBranding;

// Get branding
$branding = TenantBranding::getBranding($repoId);
$primaryColor = TenantBranding::getPrimaryColor($repoId);
$logo = TenantBranding::getLogo($repoId);

// Save branding
$result = TenantBranding::saveBranding($repoId, [
    'primary_color' => '#336699',
    'logo' => '/uploads/tenants/1/logo.png',
    'custom_css' => '.custom { color: red; }',
], $userId);

// Inject styles (automatic via filter)
$styles = TenantBranding::injectStyles();
```

## Configuration

### Legacy Settings Format (ahg_settings table)

For backward compatibility, the plugin still supports repository-based settings:

| Setting Key | Description |
|-------------|-------------|
| `tenant_repo_{id}_super_users` | Comma-separated user IDs |
| `tenant_repo_{id}_users` | Comma-separated user IDs |
| `tenant_repo_{id}_primary_color` | Primary brand color |
| `tenant_repo_{id}_secondary_color` | Secondary brand color |
| `tenant_repo_{id}_header_bg_color` | Header background |
| `tenant_repo_{id}_header_text_color` | Header text color |
| `tenant_repo_{id}_link_color` | Link color |
| `tenant_repo_{id}_button_color` | Button color |
| `tenant_repo_{id}_logo` | Logo path |
| `tenant_repo_{id}_custom_css` | Custom CSS |

## Theme Integration

The plugin automatically injects tenant branding styles. Add the tenant switcher to your theme:

```php
<!-- In theme header -->
<ul class="navbar-nav">
  <?php include_component('tenantSwitcher', 'switcher') ?>
</ul>
```

## Migration from v1.0.x

To migrate existing repository-based tenant data to the new table structure:

1. Run the migration SQL to create tables
2. Execute the migration procedure:
```sql
CALL migrate_tenant_data();
```
3. Verify data in `heritage_tenant` and `heritage_tenant_user` tables
4. (Optional) Clean up legacy `ahg_settings` entries after verification

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | >= 8.1 |
| AtoM | >= 2.8 |
| MySQL | 8.0+ |
| ahgCorePlugin | >= 1.0.0 |

## License

Proprietary - The Archive and Heritage Group (Pty) Ltd

## Author

Johan Pieterse <johan@theahg.co.za>
