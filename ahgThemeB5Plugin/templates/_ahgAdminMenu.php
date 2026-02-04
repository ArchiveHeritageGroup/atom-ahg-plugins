<?php
/**
 * AHG Admin Menu - Conditional display based on plugin registration
 */
$isAdmin = $sf_user->isAdministrator();

// Helper function to check if plugin is enabled
if (!function_exists('ahgIsPluginEnabled')) { function ahgIsPluginEnabled($name) {
    static $enabledPlugins = null;
    if ($enabledPlugins === null) {
        try {
            $pluginNames = \Illuminate\Database\Capsule\Manager::table('atom_plugin')
                ->where('is_enabled', 1)
                ->pluck('name')
                ->toArray();
            $enabledPlugins = array_flip($pluginNames);
        } catch (Exception $e) {
            $enabledPlugins = [];
        }
    }
    return isset($enabledPlugins[$name]);
}
}

// Check which plugins are enabled
$hasBackup = ahgIsPluginEnabled('ahgBackupPlugin');
$hasAuditTrail = ahgIsPluginEnabled('ahgAuditTrailPlugin');
$hasSecurityClearance = ahgIsPluginEnabled('ahgSecurityClearancePlugin');
$hasAccessRequest = ahgIsPluginEnabled('ahgAccessRequestPlugin');
$hasResearch = ahgIsPluginEnabled('ahgResearchPlugin');
$hasRic = ahgIsPluginEnabled('ahgRicExplorerPlugin');
$hasDataMigration = ahgIsPluginEnabled('ahgDataMigrationPlugin');
$hasFormsPlugin = ahgIsPluginEnabled('ahgFormsPlugin');
$hasDoiPlugin = ahgIsPluginEnabled('ahgDoiPlugin');
$hasDedupePlugin = ahgIsPluginEnabled('ahgDedupePlugin');

// Get pending counts for badges
$pendingBookings = 0;
$pendingResearchers = 0;

if ($isAdmin && $hasResearch) {
    try {
        $pendingBookings = (int) \Illuminate\Database\Capsule\Manager::table('research_booking')
            ->where('status', 'pending')
            ->count();
        $pendingResearchers = (int) \Illuminate\Database\Capsule\Manager::table('research_researcher')
            ->where('status', 'pending')
            ->count();
    } catch (Exception $e) {}
}

// Pending duplicates count
$pendingDuplicates = 0;
if ($isAdmin && ahgIsPluginEnabled('ahgDedupePlugin')) {
    try {
        $pendingDuplicates = (int) \Illuminate\Database\Capsule\Manager::table('ahg_duplicate_detection')
            ->where('status', 'pending')
            ->count();
    } catch (Exception $e) {}
}

// Pending DOI queue count
$pendingDois = 0;
if ($isAdmin && ahgIsPluginEnabled('ahgDoiPlugin')) {
    try {
        $pendingDois = (int) \Illuminate\Database\Capsule\Manager::table('ahg_doi_queue')
            ->where('status', 'pending')
            ->count();
    } catch (Exception $e) {}
}
?>
<?php if ($isAdmin): ?>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
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
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'settings', 'action' => 'index']); ?>"><i class="fas fa-cogs fa-fw me-1"></i><?php echo __('AHG Settings'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'index']); ?>"><i class="fas fa-list-alt fa-fw me-1"></i><?php echo __('Dropdown Manager'); ?></a></li>

    <?php if ($hasSecurityClearance): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Security'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'index']); ?>"><i class="fas fa-user-shield fa-fw me-1"></i><?php echo __('Clearances'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasResearch): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Research'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><i class="fas fa-book-reader fa-fw me-1"></i><?php echo __('Dashboard'); ?></a></li>
    <li><a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for(['module' => 'research', 'action' => 'researchers']); ?>"><span><i class="fas fa-users fa-fw me-1"></i><?php echo __('Researchers'); ?></span><?php if ($pendingResearchers > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?php echo $pendingResearchers; ?></span><?php endif; ?></a></li>
    <li><a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>"><span><i class="fas fa-calendar-alt fa-fw me-1"></i><?php echo __('Bookings'); ?></span><?php if ($pendingBookings > 0): ?><span class="badge bg-danger rounded-pill"><?php echo $pendingBookings; ?></span><?php endif; ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'rooms']); ?>"><i class="fas fa-door-open fa-fw me-1"></i><?php echo __('Rooms'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasAccessRequest): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Access'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'accessRequest', 'action' => 'pending']); ?>"><i class="fas fa-shield-alt fa-fw me-1"></i><?php echo __('Requests'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'accessRequest', 'action' => 'approvers']); ?>"><i class="fas fa-user-check fa-fw me-1"></i><?php echo __('Approvers'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasAuditTrail): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Audit'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'statistics']); ?>"><i class="fas fa-chart-line fa-fw me-1"></i><?php echo __('Statistics'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'browse']); ?>"><i class="fas fa-clipboard-list fa-fw me-1"></i><?php echo __('Logs'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'settings']); ?>"><i class="fas fa-sliders-h fa-fw me-1"></i><?php echo __('Settings'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasRic): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('RiC'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><i class="fas fa-project-diagram fa-fw me-1"></i><?php echo __('RiC Dashboard'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasDataMigration || $hasDedupePlugin): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Data Quality'); ?></h6></li>
    <?php if ($hasDataMigration): ?>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']); ?>"><i class="fas fa-exchange-alt fa-fw me-1"></i><?php echo __('Data Migration'); ?></a></li>
    <?php endif; ?>
    <?php if ($hasDedupePlugin): ?>
    <li><a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>"><span><i class="fas fa-clone fa-fw me-1"></i><?php echo __('Duplicate Detection'); ?></span><?php if ($pendingDuplicates > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?php echo $pendingDuplicates; ?></span><?php endif; ?></a></li>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($hasFormsPlugin): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Data Entry'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'forms', 'action' => 'index']); ?>"><i class="fas fa-edit fa-fw me-1"></i><?php echo __('Form Templates'); ?></a></li>
    <?php endif; ?>

    <?php if ($hasDoiPlugin): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('DOI Management'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'doi', 'action' => 'index']); ?>"><i class="fas fa-link fa-fw me-1"></i><?php echo __('DOI Dashboard'); ?></a></li>
    <li><a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for(['module' => 'doi', 'action' => 'queue']); ?>"><span><i class="fas fa-tasks fa-fw me-1"></i><?php echo __('Minting Queue'); ?></span><?php if ($pendingDois > 0): ?><span class="badge bg-info rounded-pill"><?php echo $pendingDois; ?></span><?php endif; ?></a></li>
    <?php endif; ?>

    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><?php echo __('Heritage'); ?></h6></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminDashboard']); ?>"><i class="fas fa-landmark fa-fw me-1"></i><?php echo __('Admin'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'heritage', 'action' => 'analyticsDashboard']); ?>"><i class="fas fa-chart-line fa-fw me-1"></i><?php echo __('Analytics'); ?></a></li>
    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianDashboard']); ?>"><i class="fas fa-user-shield fa-fw me-1"></i><?php echo __('Custodian'); ?></a></li>

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
