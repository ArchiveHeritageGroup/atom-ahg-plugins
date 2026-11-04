<?php
/**
 * Tenant Switcher Component
 *
 * Displays a dropdown in the navbar for switching between repositories.
 * Include this in your theme's header template.
 *
 * Usage: <?php include_component('tenantSwitcher', 'switcher') ?>
 */

// Load services
require_once sfConfig::get('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantContext.php';
require_once sfConfig::get('sf_plugins_dir') . '/ahgMultiTenantPlugin/lib/Services/TenantAccess.php';

use AhgMultiTenant\Services\TenantContext;

$user = sfContext::getInstance()->getUser();
if (!$user->isAuthenticated()) {
    return;
}

$userId = $user->getAttribute('user_id');
$repositories = TenantContext::getUserRepositories($userId);
$currentRepoId = TenantContext::getCurrentRepositoryId();
$isAdmin = TenantContext::isAdmin($userId);
$viewAllMode = TenantContext::isViewAllMode();

// Get current repo name
$currentRepoName = 'Select Repository';
if ($viewAllMode) {
    $currentRepoName = 'All Repositories';
} elseif ($currentRepoId) {
    foreach ($repositories as $repo) {
        if ($repo->id == $currentRepoId) {
            $currentRepoName = $repo->name ?: $repo->identifier ?: "Repository {$repo->id}";
            break;
        }
    }
}

// Only show if user has multiple repos or is admin
if (count($repositories) <= 1 && !$isAdmin) {
    // Auto-select single repository
    if (count($repositories) === 1 && !$currentRepoId) {
        TenantContext::setCurrentRepository($repositories[0]->id);
    }
    return;
}
?>

<li class="nav-item dropdown d-flex flex-column tenant-switcher">
  <?php // Same shape as the theme's other navbar icons: icon with tooltip, name as text on small screens. ?>
  <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" id="tenantSwitcherDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-2x fa-fw fa-building px-0 px-lg-2 py-2" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="d-none d-lg-block" title="<?php echo esc_specialchars($currentRepoName) ?>" aria-hidden="true"></i>
    <span class="d-lg-none mx-1 tenant-name" aria-hidden="true"><?php echo esc_specialchars($currentRepoName) ?></span>
    <span class="visually-hidden"><?php echo __('Repository: %1%', ['%1%' => esc_specialchars($currentRepoName)]) ?></span>
  </a>
  <ul class="dropdown-menu dropdown-menu-end mb-2" aria-labelledby="tenantSwitcherDropdown">
    <?php if ($isAdmin): ?>
      <li>
        <a class="dropdown-item <?php echo $viewAllMode ? 'active' : '' ?>" href="<?php echo url_for('tenant_switch_all') ?>">
          <i class="fas fa-globe me-2"></i> All Repositories
        </a>
      </li>
      <li><hr class="dropdown-divider"></li>
    <?php endif; ?>

    <?php foreach ($repositories as $repo): ?>
      <?php
        $repoName = $repo->name ?: $repo->identifier ?: "Repository {$repo->id}";
        $isActive = (!$viewAllMode && $currentRepoId == $repo->id);
      ?>
      <li>
        <a class="dropdown-item <?php echo $isActive ? 'active' : '' ?>" href="<?php echo url_for('tenant_switch', ['id' => $repo->id]) ?>">
          <?php if (TenantContext::isSuperUser($userId, $repo->id) && !$isAdmin): ?>
            <i class="fas fa-star text-warning me-2" title="Super User"></i>
          <?php else: ?>
            <i class="fas fa-archive me-2"></i>
          <?php endif; ?>
          <?php echo esc_specialchars($repoName) ?>
        </a>
      </li>
    <?php endforeach; ?>

    <?php if ($isAdmin || !empty($repositories)): ?>
      <li><hr class="dropdown-divider"></li>
      <?php if ($isAdmin): ?>
        <li>
          <a class="dropdown-item" href="<?php echo url_for('tenant_admin') ?>">
            <i class="fas fa-cog me-2"></i> Manage Tenants
          </a>
        </li>
      <?php elseif (TenantContext::isSuperUser($userId, $currentRepoId)): ?>
        <li>
          <a class="dropdown-item" href="<?php echo url_for('tenant_users', ['id' => $currentRepoId]) ?>">
            <i class="fas fa-users me-2"></i> Manage Users
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo url_for('tenant_branding', ['id' => $currentRepoId]) ?>">
            <i class="fas fa-palette me-2"></i> Branding
          </a>
        </li>
      <?php endif; ?>
    <?php endif; ?>
  </ul>
</li>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.tenant-switcher .dropdown-menu {
  max-height: 400px;
  overflow-y: auto;
}
.tenant-switcher .dropdown-item.active {
  background-color: var(--bs-primary, #0d6efd);
  color: white;
}
.tenant-switcher .tenant-name {
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  display: inline-block;
  vertical-align: middle;
}
</style>
