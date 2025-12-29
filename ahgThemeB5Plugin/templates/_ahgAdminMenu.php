<?php
/**
 * AHG Admin Menu - Conditional display based on plugin registration
 */
$isAdmin = $sf_user->isAdministrator();

// Helper function to check if plugin is enabled (uses Propel - safe in web context)
function ahgIsPluginEnabled($name) {
    static $enabledPlugins = null;
    if ($enabledPlugins === null) {
        try {
            $conn = Propel::getConnection();
            $stmt = $conn->prepare('SELECT name FROM atom_plugin WHERE is_enabled = 1');
            $stmt->execute();
            $enabledPlugins = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Exception $e) {
            $enabledPlugins = [];
        }
    }
    return isset($enabledPlugins[$name]);
}

// Check which plugins are enabled
$hasBackup = ahgIsPluginEnabled('ahgBackupPlugin');
$hasAuditTrail = ahgIsPluginEnabled('ahgAuditTrailPlugin');
$hasSecurityClearance = ahgIsPluginEnabled('ahgSecurityClearancePlugin');
$hasAccessRequest = ahgIsPluginEnabled('ahgAccessRequestPlugin');
$hasResearch = ahgIsPluginEnabled('ahgResearchPlugin');
$hasRic = ahgIsPluginEnabled('arRicExplorerPlugin');
?>
<?php if ($isAdmin): ?>
<style>
#ahg-plugins-menu + .dropdown-menu { 
  font-size: 0.8rem; 
  max-height: 80vh;
  overflow-y: auto;
}
#ahg-plugins-menu + .dropdown-menu .dropdown-item { padding: 0.15rem 0.75rem; }
#ahg-plugins-menu + .dropdown-menu .dropdown-header { padding: 0.15rem 0.75rem; font-size: 0.7rem; margin-bottom: 0; }
#ahg-plugins-menu + .dropdown-menu .dropdown-divider { margin: 0.15rem 0; }
#ahg-plugins-menu + .dropdown-menu .fas, #ahg-plugins-menu + .dropdown-menu .far { font-size: 0.75rem; }
</style>
<li class="nav-item dropdown d-flex flex-column">
  <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" id="ahg-plugins-menu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-2x fa-fw fa-cubes px-0 px-lg-2 py-2" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo __('AHG Plugins'); ?>" aria-hidden="true"></i>
    <span class="d-lg-none mx-1"><?php echo __('AHG Plugins'); ?></span>
    <span class="visually-hidden"><?php echo __('AHG Plugins'); ?></span>
  </a>
  <ul class="dropdown-menu dropdown-menu-end mb-2" aria-labelledby="ahg-plugins-menu">
    <li><h6 class="dropdown-header"><?php echo __('Settings'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']); ?>"><i class="fas fa-cogs fa-fw me-1"></i><?php echo __('AHG Settings'); ?></a></li>

    <?php if ($hasSecurityClearance): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Security'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgSecurityClearance', 'action' => 'index']); ?>"><i class="fas fa-user-shield fa-fw me-1"></i><?php echo __('Clearances'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasResearch): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Research'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><i class="fas fa-book-reader fa-fw me-1"></i><?php echo __('Dashboard'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'researchers']); ?>"><i class="fas fa-users fa-fw me-1"></i><?php echo __('Researchers'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>"><i class="fas fa-calendar-alt fa-fw me-1"></i><?php echo __('Bookings'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'rooms']); ?>"><i class="fas fa-door-open fa-fw me-1"></i><?php echo __('Rooms'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasAccessRequest): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Access'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgAccessRequest', 'action' => 'pending']); ?>"><i class="fas fa-shield-alt fa-fw me-1"></i><?php echo __('Requests'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgAccessRequest', 'action' => 'approvers']); ?>"><i class="fas fa-user-check fa-fw me-1"></i><?php echo __('Approvers'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasAuditTrail): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Audit'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'dashboard']); ?>"><i class="fas fa-chart-line fa-fw me-1"></i><?php echo __('Dashboard'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'index']); ?>"><i class="fas fa-clipboard-list fa-fw me-1"></i><?php echo __('Logs'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'settings']); ?>"><i class="fas fa-sliders-h fa-fw me-1"></i><?php echo __('Settings'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasRic): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('RiC'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ricExplorer', 'action' => 'index']); ?>"><i class="fas fa-project-diagram fa-fw me-1"></i><?php echo __('RiC Dashboard'); ?></a></li>
    <?php endif; ?>

    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Maintenance'); ?></h6></li>
    <?php if ($hasBackup): ?>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'backup', 'action' => 'index']); ?>"><i class="fas fa-database fa-fw me-1"></i><?php echo __('Backup'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'backup', 'action' => 'restore']); ?>"><i class="fas fa-undo-alt fa-fw me-1"></i><?php echo __('Restore'); ?></a></li>
    <?php endif; ?>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'jobs', 'action' => 'browse']); ?>"><i class="fas fa-tasks fa-fw me-1"></i><?php echo __('Jobs'); ?></a></li>
  </ul>
</li>
<?php endif; ?>
