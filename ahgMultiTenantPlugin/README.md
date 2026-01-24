# ahgMultiTenantPlugin

Repository-based multi-tenancy plugin for AtoM. Allows organizations to manage multiple repositories with isolated user access and custom branding per repository.

## Features

- **User Hierarchy**: Admin > Super User > User
- **Repository Isolation**: Users only see assigned repositories
- **Custom Branding**: Per-repository colors, logos, and custom CSS
- **Theme Agnostic**: Branding works with any AtoM theme

## User Hierarchy

### 1. Administrator (ADMIN)
- AtoM administrator group member (group_id = 100)
- Sees ALL repositories
- Can assign Super Users to any repository
- Can manage all settings

### 2. Super User
- Assigned to specific repositories by Admin
- Sees ONLY assigned repositories
- Can assign Users to their assigned repositories
- Can manage branding for their assigned repositories

### 3. User
- Assigned to specific repositories by Admin or Super User
- Sees ONLY assigned repositories
- Cannot assign anyone
- Standard editor/contributor/viewer permissions

## Installation

1. Enable the plugin:
```bash
php bin/atom extension:enable ahgMultiTenantPlugin
```

2. Clear cache:
```bash
php symfony cc
```

3. Add tenant switcher to your theme header (optional):
```php
<?php include_component('tenantSwitcher', 'switcher') ?>
```

## Configuration

The plugin stores all settings in the `ahg_settings` table using the following key format:

| Setting Key | Description | Example |
|-------------|-------------|---------|
| `tenant_repo_{id}_super_users` | Comma-separated user IDs | "5,12,18" |
| `tenant_repo_{id}_users` | Comma-separated user IDs | "22,25,30" |
| `tenant_repo_{id}_primary_color` | Primary brand color | "#336699" |
| `tenant_repo_{id}_secondary_color` | Secondary brand color | "#6c757d" |
| `tenant_repo_{id}_header_bg_color` | Header background | "#212529" |
| `tenant_repo_{id}_header_text_color` | Header text color | "#ffffff" |
| `tenant_repo_{id}_link_color` | Link color | "#0d6efd" |
| `tenant_repo_{id}_button_color` | Button color | "#198754" |
| `tenant_repo_{id}_logo` | Logo path | "/uploads/tenants/1/logo.png" |
| `tenant_repo_{id}_custom_css` | Custom CSS | ".custom { ... }" |

## Routes

| Route | URL | Description |
|-------|-----|-------------|
| `tenant_admin` | `/admin/tenants` | Admin: List all tenants |
| `tenant_admin_super_users` | `/admin/tenants/:id/super-users` | Admin: Manage super users |
| `tenant_users` | `/tenant/:id/users` | Super User: Manage users |
| `tenant_branding` | `/tenant/:id/branding` | Super User: Manage branding |
| `tenant_switch` | `/tenant/switch/:id` | Switch active repository |
| `tenant_switch_all` | `/tenant/switch/all` | Admin: View all mode |

## Services

### TenantContext

Manages the current tenant context and user repositories.

```php
use AhgMultiTenant\Services\TenantContext;

// Get current repository ID
$repoId = TenantContext::getCurrentRepositoryId();

// Switch to a repository
TenantContext::setCurrentRepository($repoId);

// Get user's accessible repositories
$repos = TenantContext::getUserRepositories($userId);

// Check if user is admin
$isAdmin = TenantContext::isAdmin($userId);

// Check if user is super user for a repo
$isSuperUser = TenantContext::isSuperUser($userId, $repoId);

// Apply repository filter to a query
$query = TenantContext::applyRepositoryFilter($query, 'repository_id');
```

### TenantAccess

Manages user access to repositories.

```php
use AhgMultiTenant\Services\TenantAccess;

// Check if user can access a repository
$canAccess = TenantAccess::canAccessRepository($userId, $repoId);

// Check if user can assign users
$canAssign = TenantAccess::canAssignUsers($userId, $repoId);

// Assign user to repository
$result = TenantAccess::assignUserToRepository($userId, $repoId, $assignedByUserId);

// Remove user from repository
$result = TenantAccess::removeUserFromRepository($userId, $repoId, $removedByUserId);

// Get repository users
$users = TenantAccess::getRepositoryUsers($repoId);
```

### TenantBranding

Manages repository-specific branding.

```php
use AhgMultiTenant\Services\TenantBranding;

// Get branding settings
$primaryColor = TenantBranding::getPrimaryColor($repoId);
$logo = TenantBranding::getLogo($repoId);
$branding = TenantBranding::getBranding($repoId);

// Save branding
$result = TenantBranding::saveBranding($repoId, $settings, $userId);

// Inject styles (called automatically via filter)
$styles = TenantBranding::injectStyles();
```

## Query Filtering

To filter queries by the current tenant:

```php
use AhgMultiTenant\Services\TenantContext;
use Illuminate\Database\Capsule\Manager as DB;

// Apply repository filter
$query = DB::table('information_object')
    ->where('repository_id', TenantContext::getCurrentRepositoryId());

// Or use the helper method
$query = DB::table('information_object');
TenantContext::applyRepositoryFilter($query, 'repository_id');
```

## Elasticsearch Integration

For Elasticsearch queries, add repository filter:

```php
$repositoryId = TenantContext::getCurrentRepositoryId();
if ($repositoryId && !TenantContext::isViewAllMode()) {
    $query['bool']['filter'][] = ['term' => ['repository.id' => $repositoryId]];
}
```

## Theme Integration

The plugin automatically injects tenant branding styles into the page. To add the tenant switcher to your theme's navigation:

```php
<!-- In your theme's header template -->
<ul class="navbar-nav">
  <?php include_component('tenantSwitcher', 'switcher') ?>
  <!-- other nav items -->
</ul>
```

## License

Proprietary - The Archive and Heritage Group (Pty) Ltd

## Author

Johan Pieterse <johan@theahg.co.za>
