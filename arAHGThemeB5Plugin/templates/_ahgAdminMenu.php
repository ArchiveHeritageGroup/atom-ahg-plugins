<?php
/**
 * AHG Admin Menu - Conditional display based on plugin existence
 */
$isAdmin = $sf_user->isAdministrator();
$pluginsDir = sfConfig::get('sf_plugins_dir');

// Check which plugins exist
$hasAuditTrail = is_dir($pluginsDir . '/arAuditTrailPlugin');
$hasSecurityClearance = is_dir($pluginsDir . '/arSecurityClearancePlugin');
$hasAccessRequest = is_dir($pluginsDir . '/arAccessRequestPlugin');
$hasResearch = is_dir($pluginsDir . '/arResearchPlugin');
$hasRic = is_dir($pluginsDir . '/arRicExplorerPlugin');
$hasAhgTheme = is_dir($pluginsDir . '/arAHGThemeB5Plugin');

?>
<?php if ($isAdmin): ?>
<style>
#ahg-plugins-menu + .dropdown-menu { font-size: 0.85rem; }
#ahg-plugins-menu + .dropdown-menu .dropdown-item { padding: 0.25rem 1rem; }
#ahg-plugins-menu + .dropdown-menu .dropdown-header { padding: 0.25rem 1rem; font-size: 0.75rem; }
#ahg-plugins-menu + .dropdown-menu .dropdown-divider { margin: 0.25rem 0; }
</style>
<li class="nav-item dropdown d-flex flex-column">
  <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" id="ahg-plugins-menu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-2x fa-fw fa-cubes px-0 px-lg-2 py-2" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo __('AHG Plugins'); ?>" aria-hidden="true"></i>
    <span class="d-lg-none mx-1"><?php echo __('AHG Plugins'); ?></span>
    <span class="visually-hidden"><?php echo __('AHG Plugins'); ?></span>
  </a>
  <ul class="dropdown-menu dropdown-menu-end mb-2" aria-labelledby="ahg-plugins-menu">

    <li><h6 class="dropdown-header"><?php echo __('Settings'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']); ?>"><i class="fas fa-cogs fa-fw me-2"></i><?php echo __('AHG Settings'); ?></a></li>

    <?php if ($hasSecurityClearance): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Security Clearances'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'arSecurityClearance', 'action' => 'index']); ?>"><i class="fas fa-user-shield fa-fw me-2"></i><?php echo __('Manage Clearances'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasResearch): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Research Services'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><i class="fas fa-book-reader fa-fw me-2"></i><?php echo __('Research Dashboard'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'researchers']); ?>"><i class="fas fa-users fa-fw me-2"></i><?php echo __('Manage Researchers'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>"><i class="fas fa-calendar-alt fa-fw me-2"></i><?php echo __('Manage Bookings'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'rooms']); ?>"><i class="fas fa-door-open fa-fw me-2"></i><?php echo __('Reading Rooms'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasAccessRequest): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Data Protection'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'arAccessRequest', 'action' => 'pending']); ?>"><i class="fas fa-shield-alt fa-fw me-2"></i><?php echo __('Access Requests'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'arAccessRequest', 'action' => 'approvers']); ?>"><i class="fas fa-user-check fa-fw me-2"></i><?php echo __('Manage Approvers'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasAuditTrail): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Audit'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'arAuditTrailPlugin', 'action' => 'browse']); ?>"><i class="fas fa-clipboard-list fa-fw me-2"></i><?php echo __('Audit Logs'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasRic): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('RIC'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><i class="fas fa-project-diagram fa-fw me-2"></i><?php echo __('RIC Sync Dashboard'); ?></a></li>
    <li><a class="dropdown-item" href="/ric/" target="_blank"><i class="fas fa-sitemap fa-fw me-2"></i><?php echo __('RIC Explorer'); ?></a></li>
    <?php endif; ?>

  </ul>
</li>
<?php endif; ?>
