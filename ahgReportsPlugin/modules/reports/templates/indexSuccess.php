<?php decorate_with('layout_2col'); ?>

<?php
use Illuminate\Database\Capsule\Manager as DB;

// Check which sector plugins are enabled
function isPluginActive($pluginName) {
    static $plugins = null;
    if ($plugins === null) {
        try {
            $pluginNames = DB::table('atom_plugin')
                ->where('is_enabled', 1)
                ->pluck('name')
                ->toArray();
            $plugins = array_flip($pluginNames);
        } catch (Exception $e) {
            $plugins = [];
        }
    }
    return isset($plugins[$pluginName]);
}

$hasLibrary = isPluginActive('ahgLibraryPlugin');
$hasMuseum = isPluginActive('ahgMuseumPlugin');
$hasGallery = isPluginActive('ahgGalleryPlugin');
$hasDam = isPluginActive('arDAMPlugin') || isPluginActive('ahgDAMPlugin');
$hasSpectrum = isPluginActive('ahgSpectrumPlugin');
$hasGrap = isPluginActive('ahgHeritageAccountingPlugin');
$hasHeritage = isPluginActive('ahgHeritageAccountingPlugin');
$hasResearch = isPluginActive('ahgResearchPlugin');
$hasDonor = isPluginActive('ahgDonorAgreementPlugin');
$hasRights = isPluginActive('ahgExtendedRightsPlugin');
$hasCondition = isPluginActive('ahgConditionPlugin');
$hasPrivacy = isPluginActive('ahgPrivacyPlugin');
$hasSecurity = isPluginActive('ahgSecurityClearancePlugin');
$hasAudit = isPluginActive('ahgAuditTrailPlugin');
$hasVendor = isPluginActive('ahgVendorPlugin');
$has3D = isPluginActive('ahg3DModelPlugin');
$hasOais = isPluginActive('ahgPreservationPlugin');
$hasPreservation = isPluginActive('ahgPreservationPlugin');
$hasReportBuilder = isPluginActive('ahgReportBuilderPlugin');
$hasAccessRequest = isPluginActive('ahgAccessRequestPlugin');
$hasRic = isPluginActive('ahgRicExplorerPlugin');
$hasDataMigration = isPluginActive('ahgDataMigrationPlugin');
$hasBackup = isPluginActive('ahgBackupPlugin');
$hasDedupe = isPluginActive('ahgDedupePlugin');
$hasForms = isPluginActive('ahgFormsPlugin');
$hasDoi = isPluginActive('ahgDoiPlugin');
$hasHeritage2 = isPluginActive('ahgHeritagePlugin');
$hasIngest = isPluginActive('ahgIngestPlugin');
$hasWorkflow = isPluginActive('ahgWorkflowPlugin');
$hasAccess = isPluginActive('ahgAccessRequestPlugin');
// Zimbabwe Compliance Plugins
$hasCDPA = isPluginActive('ahgCDPAPlugin');
$hasNAZ = isPluginActive('ahgNAZPlugin');
$hasIPSAS = isPluginActive('ahgIPSASPlugin');
$hasNMMZ = isPluginActive('ahgNMMZPlugin');
$hasAiCondition = isPluginActive('ahgAiConditionPlugin');
?>

<?php slot('sidebar'); ?>
<?php
$isAdmin = $sf_user->isAdministrator();
$isEditor = $sf_user->hasCredential('editor');
$canManage = $isAdmin || $isEditor;
?>
<div class="sidebar-content">
    <h4><?php echo __('Quick Links'); ?></h4>
    <ul class="list-unstyled">
        <?php if ($canManage && $hasReportBuilder): ?>
        <li><a href="/admin/report-builder"><i class="fas fa-tools me-2"></i><?php echo __('Report Builder'); ?></a></li>
        <?php endif; ?>
        <?php if ($canManage): ?>
        <li><a href="<?php echo url_for('export/index'); ?>"><i class="fas fa-download me-2"></i><?php echo __('Export Data'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasPreservation && $canManage): ?>
        <li><a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>"><i class="fas fa-shield-alt me-2"></i><?php echo __('Preservation'); ?></a></li>
        <?php endif; ?>
    </ul>

    <?php if ($hasVendor && $canManage): ?>
    <h4 class="mt-4"><?php echo __('Vendors'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'index']); ?>"><i class="fas fa-building me-2"></i><?php echo __('Vendor Dashboard'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'transactions']); ?>"><i class="fas fa-exchange-alt me-2"></i><?php echo __('Transactions'); ?></a></li>
    </ul>
    <?php endif; ?>

    <?php if (isPluginActive('ahgResearchPlugin') && $canManage): ?>
    <h4 class="mt-4"><?php echo __('Research'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><i class="fas fa-graduation-cap me-2"></i><?php echo __('Research Dashboard'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>"><i class="fas fa-calendar-alt me-2"></i><?php echo __('Bookings'); ?></a></li>
    </ul>
    <?php endif; ?>

    <?php if (isPluginActive('ahgAuditTrailPlugin') && $canManage): ?>
    <h4 class="mt-4"><?php echo __('Audit'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'statistics']); ?>"><i class="fas fa-chart-line me-2"></i><?php echo __('Statistics'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'browse']); ?>"><i class="fas fa-clipboard-list me-2"></i><?php echo __('Logs'); ?></a></li>
    </ul>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <h4 class="mt-4"><?php echo __('Settings'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'settings', 'action' => 'index']); ?>"><i class="fas fa-cogs me-2"></i><?php echo __('AHG Settings'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'levels']); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('Levels of Description'); ?></a></li>
    </ul>

    <h4 class="mt-4"><?php echo __('Compliance'); ?></h4>
    <ul class="list-unstyled">
        <?php if ($hasSecurity): ?>
        <li><a href="/admin/security/compliance"><i class="fas fa-shield-alt me-2"></i><?php echo __('Security'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasPrivacy): ?>
        <li><a href="/privacyAdmin"><i class="fas fa-user-shield me-2"></i><?php echo __('Privacy &amp; Compliance'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasCondition): ?>
        <li><a href="/admin/condition"><i class="fas fa-heartbeat me-2"></i><?php echo __('Condition'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasRights): ?>
        <li><a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'dashboard']); ?>"><i class="fas fa-gavel me-2"></i><?php echo __('Rights'); ?></a></li>
        <?php endif; ?>
    </ul>
    <?php endif; ?>

    <?php if (($hasCDPA || $hasNAZ || $hasIPSAS || $hasNMMZ) && $isAdmin): ?>
    <h4 class="mt-4"><?php echo __('Zimbabwe Compliance'); ?></h4>
    <ul class="list-unstyled">
        <?php if ($hasCDPA): ?>
        <li><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>"><i class="fas fa-shield-alt me-2"></i><?php echo __('CDPA (Data Protection)'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasNAZ): ?>
        <li><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>"><i class="fas fa-landmark me-2"></i><?php echo __('NAZ (Archives)'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasIPSAS): ?>
        <li><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>"><i class="fas fa-coins me-2"></i><?php echo __('IPSAS (Assets)'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasNMMZ): ?>
        <li><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>"><i class="fas fa-monument me-2"></i><?php echo __('NMMZ (Monuments)'); ?></a></li>
        <?php endif; ?>
    </ul>
    <?php endif; ?>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-tachometer-alt"></i> <?php echo __('Central Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php if (!$sf_user->isAuthenticated()): ?>
<div class="alert alert-warning">
    <i class="fas fa-lock me-2"></i><?php echo __('Please log in to access the Central Dashboard.'); ?>
    <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>" class="btn btn-sm btn-primary ms-3"><?php echo __('Login'); ?></a>
</div>
<?php return; endif; ?>
<div class="reports-dashboard">
    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo number_format($stats['totalDescriptions'] ?? 0); ?></h2>
                    <p class="mb-0"><?php echo __('Archival Descriptions'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo number_format($stats['totalActors'] ?? 0); ?></h2>
                    <p class="mb-0"><?php echo __('Authority Records'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo number_format($stats['totalDigitalObjects'] ?? 0); ?></h2>
                    <p class="mb-0"><?php echo __('Digital Objects'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-dark">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo number_format($stats['recentUpdates'] ?? 0); ?></h2>
                    <p class="mb-0"><?php echo __('Updated (7 days)'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <?php if ($canManage): ?>
    <div class="row mb-4">
        <!-- Reports Column -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('Reports'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'reports', 'action' => 'descriptions']); ?>"><i class="fas fa-archive me-2 text-muted"></i><?php echo __('Archival Descriptions'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'reports', 'action' => 'authorities']); ?>"><i class="fas fa-users me-2 text-muted"></i><?php echo __('Authority Records'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'reports', 'action' => 'repositories']); ?>"><i class="fas fa-building me-2 text-muted"></i><?php echo __('Repositories'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'reports', 'action' => 'accessions']); ?>"><i class="fas fa-inbox me-2 text-muted"></i><?php echo __('Accessions'); ?></a></li>
                    <?php if ($hasDonor): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse']); ?>"><i class="fas fa-handshake me-2 text-muted"></i><?php echo __('Donor Agreements'); ?></a></li>
                    <?php endif; ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'reports', 'action' => 'storage']); ?>"><i class="fas fa-boxes me-2 text-muted"></i><?php echo __('Physical Storage'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'reports', 'action' => 'reportSpatialAnalysis']); ?>"><i class="fas fa-map-marker-alt me-2 text-muted"></i><?php echo __('Spatial Analysis Export'); ?></a></li>
                    <?php if ($hasGallery || $hasLibrary || $hasDam || $hasMuseum || $has3D || $hasSpectrum): ?>
                    <li class="list-group-item border-top mt-2 pt-2"><small class="text-muted fw-bold"><?php echo __("Sector Reports"); ?></small></li>
                    <?php endif; ?>
                    <?php if ($hasGallery): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'galleryReports', 'action' => 'index']); ?>"><i class="fas fa-palette me-2 text-muted"></i><?php echo __("Gallery Reports"); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasLibrary): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>"><i class="fas fa-book me-2 text-muted"></i><?php echo __("Library Reports"); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasDam): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'damReports', 'action' => 'index']); ?>"><i class="fas fa-images me-2 text-muted"></i><?php echo __("DAM Reports"); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasMuseum): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'index']); ?>"><i class="fas fa-landmark me-2 text-muted"></i><?php echo __("Museum Reports"); ?></a></li>
                    <?php endif; ?>
                    <?php if ($has3D): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'threeDReports', 'action' => 'index']); ?>"><i class="fas fa-cube me-2 text-muted"></i><?php echo __("3D Object Reports"); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasSpectrum): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'spectrumReports', 'action' => 'index']); ?>"><i class="fas fa-clipboard-list me-2 text-muted"></i><?php echo __("Spectrum Reports"); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Dashboards Column -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i><?php echo __('Dashboards'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <!-- Quality Dashboard removed - module does not exist -->
                    <?php if ($hasSpectrum): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'dashboard']); ?>"><i class="fas fa-layer-group me-2 text-muted"></i><?php echo __('Spectrum Workflow'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasGrap): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'grapCompliance', 'action' => 'dashboard']); ?>"><i class="fas fa-balance-scale me-2 text-muted"></i><?php echo __('GRAP 103 Dashboard'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasHeritage): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'dashboard']); ?>"><i class="fas fa-landmark me-2 text-muted"></i><?php echo __('Heritage Asset Accounting'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasCondition): ?>
                    <li class="list-group-item"><a href="/admin/condition"><i class="fas fa-heartbeat me-2 text-muted"></i><?php echo __('Condition Management'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasOais): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'oais', 'action' => 'dashboard']); ?>"><i class="fas fa-archive me-2 text-muted"></i><?php echo __('Digital Preservation (OAIS)'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasResearch): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><i class="fas fa-graduation-cap me-2 text-muted"></i><?php echo __('Research Services'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasDonor): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'donor', 'action' => 'dashboard']); ?>"><i class="fas fa-handshake me-2 text-muted"></i><?php echo __('Donor Management'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasGallery): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>"><i class="fas fa-palette me-2 text-muted"></i><?php echo __('Gallery Management'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasLibrary): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'library', 'action' => 'browse']); ?>"><i class="fas fa-book me-2 text-muted"></i><?php echo __('Library Management'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasDam): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'dam', 'action' => 'dashboard']); ?>"><i class="fas fa-images me-2 text-muted"></i><?php echo __('Digital Asset Management'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasMuseum): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'museum', 'action' => 'dashboard']); ?>"><i class="fas fa-landmark me-2 text-muted"></i><?php echo __("Museum Dashboard"); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'dashboard', 'action' => 'index']); ?>"><i class="fas fa-chart-line me-2 text-muted"></i><?php echo __("Data Quality Dashboard"); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'dashboard']); ?>"><i class="fas fa-theater-masks me-2 text-muted"></i><?php echo __("Exhibitions"); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Export Column -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-download me-2"></i><?php echo __('Export'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if ($hasGrap): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'grapCompliance', 'action' => 'nationalTreasuryReport']); ?>"><i class="fas fa-balance-scale me-2 text-muted"></i><?php echo __('GRAP 103 National Treasury Report'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasSpectrum): ?>
                    <li class="list-group-item"><a href="/spectrum/export"><i class="fas fa-history me-2 text-muted"></i><?php echo __('Spectrum History Export'); ?></a></li>
                    <?php endif; ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'export', 'action' => 'csv']); ?>"><i class="fas fa-file-csv me-2 text-muted"></i><?php echo __('CSV Export'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'export', 'action' => 'ead']); ?>"><i class="fas fa-file-code me-2 text-muted"></i><?php echo __('EAD Export'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($hasWorkflow && $canManage): ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6610f2 !important;">
                    <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i><?php echo __('Approval Workflow'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="/workflow"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Workflow Dashboard'); ?></a></li>
                    <li class="list-group-item"><a href="/workflow/my-tasks"><i class="fas fa-clipboard-check me-2 text-muted"></i><?php echo __('My Tasks'); ?></a></li>
                    <li class="list-group-item"><a href="/workflow/pool"><i class="fas fa-inbox me-2 text-muted"></i><?php echo __('Task Pool'); ?></a></li>
                    <li class="list-group-item"><a href="/workflow/history"><i class="fas fa-history me-2 text-muted"></i><?php echo __('Workflow History'); ?></a></li>
                    <li class="list-group-item"><a href="/workflow/admin"><i class="fas fa-cog me-2 text-muted"></i><?php echo __('Configure Workflows'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php if ($hasSpectrum): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #0d6efd !important;">
                    <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i><?php echo __('Spectrum Workflow'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'dashboard']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Spectrum Dashboard'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for('@spectrum_my_tasks'); ?>"><i class="fas fa-clipboard-list me-2 text-muted"></i><?php echo __('My Spectrum Tasks'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflows']); ?>"><i class="fas fa-sitemap me-2 text-muted"></i><?php echo __('Workflow Configurations'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'notifications']); ?>"><i class="fas fa-bell me-2 text-muted"></i><?php echo __('Notifications'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($hasResearch): ?>
    <!-- Research Knowledge Platform Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #0d6efd !important;">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i><?php echo __('Research Services'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Research Dashboard'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'projects']); ?>"><i class="fas fa-project-diagram me-2 text-muted"></i><?php echo __('Projects'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'collections']); ?>"><i class="fas fa-layer-group me-2 text-muted"></i><?php echo __('Evidence Sets'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'journal']); ?>"><i class="fas fa-journal-whills me-2 text-muted"></i><?php echo __('Research Journal'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'reports']); ?>"><i class="fas fa-file-alt me-2 text-muted"></i><?php echo __('Research Reports'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'bibliographies']); ?>"><i class="fas fa-book me-2 text-muted"></i><?php echo __('Bibliographies'); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6610f2 !important;">
                    <h5 class="mb-0"><i class="fas fa-brain me-2"></i><?php echo __('Knowledge Platform'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations']); ?>"><i class="fas fa-highlighter me-2 text-muted"></i><?php echo __('Annotation Studio'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'savedSearches']); ?>"><i class="fas fa-search me-2 text-muted"></i><?php echo __('Saved Searches'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'validationQueue']); ?>"><i class="fas fa-check-double me-2 text-muted"></i><?php echo __('Validation Queue'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'entityResolution']); ?>"><i class="fas fa-object-group me-2 text-muted"></i><?php echo __('Entity Resolution'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'odrlPolicies']); ?>"><i class="fas fa-balance-scale me-2 text-muted"></i><?php echo __('ODRL Policies'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'documentTemplates']); ?>"><i class="fas fa-file-alt me-2 text-muted"></i><?php echo __('Document Templates'); ?></a></li>
                </ul>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #198754 !important;">
                    <h5 class="mb-0"><i class="fas fa-user-check me-2"></i><?php echo __('Research Admin'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'researchers']); ?>"><i class="fas fa-users me-2 text-muted"></i><?php echo __('Manage Researchers'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>"><i class="fas fa-calendar-alt me-2 text-muted"></i><?php echo __('Manage Bookings'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'rooms']); ?>"><i class="fas fa-door-open me-2 text-muted"></i><?php echo __('Reading Rooms'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'reproductions']); ?>"><i class="fas fa-copy me-2 text-muted"></i><?php echo __('Reproduction Requests'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'adminStatistics']); ?>"><i class="fas fa-chart-bar me-2 text-muted"></i><?php echo __('Statistics'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Compliance Row -->
    <?php if ($isAdmin): ?>
    <div class="row mb-4">
        <?php if ($hasSecurity || $hasAudit): ?>
        <!-- Security -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Security & Compliance'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if ($hasSecurity): ?>
                    <li class="list-group-item">
                        <a href="/admin/security/compliance"><i class="fas fa-lock me-2 text-muted"></i><?php echo __('Security Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'report']); ?>"><i class="fas fa-user-shield me-2 text-muted"></i><?php echo __('Clearance Report'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasAudit): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'browse']); ?>"><i class="fas fa-clipboard-list me-2 text-muted"></i><?php echo __('Audit Log'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'export']); ?>"><i class="fas fa-download me-2 text-muted"></i><?php echo __('Export Audit Log'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasPrivacy): ?>
        <!-- Privacy -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i><?php echo __('Privacy &amp; Data Protection'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="/privacyAdmin"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Privacy Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item"><a href="/privacyAdmin/ropaList"><i class="fas fa-clipboard-list me-2 text-muted"></i><?php echo __('ROPA'); ?></a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/dsarList"><i class="fas fa-user-clock me-2 text-muted"></i><?php echo __('DSAR Requests'); ?></a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/breachList"><i class="fas fa-exclamation-circle me-2 text-muted"></i><?php echo __('Breach Register'); ?></a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/paiaList"><i class="fas fa-file-contract me-2 text-muted"></i><?php echo __('PAIA Requests'); ?></a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/officerList"><i class="fas fa-user-tie me-2 text-muted"></i><?php echo __('Privacy Officers'); ?></a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/config"><i class="fas fa-file-alt me-2 text-muted"></i><?php echo __('Template Library'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasCondition): ?>
        <!-- Condition -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i><?php echo __('Condition (Spectrum 5.1)'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="/admin/condition"><i class="fas fa-clipboard-check me-2 text-muted"></i><?php echo __('Condition Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin/condition/risk"><i class="fas fa-exclamation-triangle me-2 text-muted"></i><?php echo __('Risk Assessment'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="/condition/templates"><i class="fas fa-clipboard me-2 text-muted"></i><?php echo __('Condition Templates'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($hasAiCondition): ?>
    <!-- AI Condition Assessment Row -->
    <div class="row mb-4">
        <!-- AI Condition Actions -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-robot me-2"></i><?php echo __('AI Condition Assessment'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'dashboard']); ?>"><i class="fas fa-tachometer-alt me-2 text-success"></i><?php echo __('Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'assess']); ?>"><i class="fas fa-camera me-2 text-success"></i><?php echo __('New AI Assessment'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'manualAssess']); ?>"><i class="fas fa-clipboard-check me-2 text-primary"></i><?php echo __('Manual Assessment'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'bulk']); ?>"><i class="fas fa-layer-group me-2 text-info"></i><?php echo __('Bulk Scan'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'browse']); ?>"><i class="fas fa-list me-2 text-muted"></i><?php echo __('Browse Assessments'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'training']); ?>"><i class="fas fa-brain me-2 text-warning"></i><?php echo __('Model Training'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'index']); ?>"><i class="fas fa-cog me-2 text-secondary"></i><?php echo __('Settings & API Clients'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($hasRights && $canManage): ?>
    <!-- Rights & Licensing Row -->
    <div class="row mb-4">
        <!-- Rights & Licensing -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-purple text-white" style="background-color: #6f42c1 !important;">
                    <h5 class="mb-0"><i class="fas fa-gavel me-2"></i><?php echo __('Rights & Licensing'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'dashboard']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Rights Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'batch']); ?>"><i class="fas fa-layer-group me-2 text-muted"></i><?php echo __('Batch Rights Assignment'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'index']); ?>"><i class="fas fa-list me-2 text-muted"></i><?php echo __('Browse Rights'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'export']); ?>"><i class="fas fa-download me-2 text-muted"></i><?php echo __('Export Rights Report'); ?></a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Embargo Management -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #e83e8c !important;">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i><?php echo __('Embargo Management'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'embargoStatus']); ?>"><i class="fas fa-clock me-2 text-muted"></i><?php echo __('Active Embargoes'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'batch', 'action_type' => 'embargo']); ?>"><i class="fas fa-plus-circle me-2 text-muted"></i><?php echo __('Apply Embargo'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'expiringEmbargoes']); ?>"><i class="fas fa-hourglass-half me-2 text-muted"></i><?php echo __('Expiring Soon'); ?></a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Rights Vocabularies -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #20c997 !important;">
                    <h5 class="mb-0"><i class="fas fa-book-open me-2"></i><?php echo __('Rights Vocabularies'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'browse']); ?>"><i class="fas fa-copyright me-2 text-muted"></i><?php echo __('Rights Statements'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'browse']) . '#creative-commons'; ?>"><i class="fab fa-creative-commons me-2 text-muted"></i><?php echo __('Creative Commons'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'browse']) . '#tk-labels'; ?>"><i class="fas fa-globe-africa me-2 text-muted"></i><?php echo __('TK Labels'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'actor', 'action' => 'browse', 'type' => 'rightsHolder']); ?>"><i class="fas fa-user-tie me-2 text-muted"></i><?php echo __('Rights Holders'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($hasVendor && $canManage): ?>
    <!-- Vendor Management Row -->
    <div class="row mb-4">
        <!-- Vendor Management -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #fd7e14 !important;">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i><?php echo __('Vendor Management'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Vendor Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'list']); ?>"><i class="fas fa-list me-2 text-muted"></i><?php echo __('Browse Vendors'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'add']); ?>"><i class="fas fa-plus-circle me-2 text-muted"></i><?php echo __('Add Vendor'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'transactions']); ?>"><i class="fas fa-exchange-alt me-2 text-muted"></i><?php echo __('Transactions'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'addTransaction']); ?>"><i class="fas fa-file-invoice me-2 text-muted"></i><?php echo __('New Transaction'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'serviceTypes']); ?>"><i class="fas fa-tools me-2 text-muted"></i><?php echo __('Service Types'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (($hasAccessRequest || $hasRic || $hasBackup) && $canManage): ?>
    <!-- Access, RiC & Maintenance Row -->
    <div class="row mb-4">
        <?php if ($hasAccessRequest): ?>
        <!-- Access Requests -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #0d6efd !important;">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Access Requests'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'accessRequest', 'action' => 'pending']); ?>"><i class="fas fa-clock me-2 text-muted"></i><?php echo __('Pending Requests'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'accessRequest', 'action' => 'approvers']); ?>"><i class="fas fa-user-check me-2 text-muted"></i><?php echo __('Approvers'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'accessRequest', 'action' => 'history']); ?>"><i class="fas fa-history me-2 text-muted"></i><?php echo __('Request History'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasRic): ?>
        <!-- RiC Explorer -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6f42c1 !important;">
                    <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i><?php echo __('Records in Contexts (RiC)'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('RiC Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'explorer']); ?>"><i class="fas fa-sitemap me-2 text-muted"></i><?php echo __('RiC Explorer'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'sync']); ?>"><i class="fas fa-sync me-2 text-muted"></i><?php echo __('Sync Status'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasBackup && $isAdmin): ?>
        <!-- Backup & Maintenance -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i><?php echo __('Backup & Maintenance'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'index']); ?>"><i class="fas fa-download me-2 text-muted"></i><?php echo __('Backup Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'restore']); ?>"><i class="fas fa-undo-alt me-2 text-muted"></i><?php echo __('Restore'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'jobs', 'action' => 'browse']); ?>"><i class="fas fa-tasks me-2 text-muted"></i><?php echo __('Background Jobs'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (($hasDedupe || $hasForms || $hasDoi) && $canManage): ?>
    <!-- Data Quality, Forms & DOI Row -->
    <div class="row mb-4">
        <?php if ($hasDedupe): ?>
        <!-- Duplicate Detection -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #dc3545 !important;">
                    <h5 class="mb-0"><i class="fas fa-clone me-2"></i><?php echo __('Duplicate Detection'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Dedupe Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse']); ?>"><i class="fas fa-list me-2 text-muted"></i><?php echo __('Browse Duplicates'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'scan']); ?>"><i class="fas fa-search me-2 text-muted"></i><?php echo __('Run Scan'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'rules']); ?>"><i class="fas fa-cog me-2 text-muted"></i><?php echo __('Detection Rules'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'report']); ?>"><i class="fas fa-chart-bar me-2 text-muted"></i><?php echo __('Reports'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasForms): ?>
        <!-- Form Templates -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #198754 !important;">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i><?php echo __('Form Templates'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Forms Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'browse']); ?>"><i class="fas fa-list me-2 text-muted"></i><?php echo __('Browse Templates'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'create']); ?>"><i class="fas fa-plus me-2 text-muted"></i><?php echo __('Create Template'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'assignments']); ?>"><i class="fas fa-link me-2 text-muted"></i><?php echo __('Assignments'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasDoi): ?>
        <!-- DOI Management -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #0dcaf0 !important;">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('DOI Management'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('DOI Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'browse']); ?>"><i class="fas fa-list me-2 text-muted"></i><?php echo __('Browse DOIs'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'queue']); ?>"><i class="fas fa-tasks me-2 text-muted"></i><?php echo __('Minting Queue'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'batchMint']); ?>"><i class="fas fa-layer-group me-2 text-muted"></i><?php echo __('Batch Mint'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'config']); ?>"><i class="fas fa-cog me-2 text-muted"></i><?php echo __('DataCite Config'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (($hasDataMigration || $hasHeritage2 || $hasIngest) && $canManage): ?>
    <!-- Data Migration, Ingest & Heritage Row -->
    <div class="row mb-4">
        <?php if ($hasDataMigration): ?>
        <!-- Data Migration -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #fd7e14 !important;">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i><?php echo __('Data Migration'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Migration Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'import']); ?>"><i class="fas fa-upload me-2 text-muted"></i><?php echo __('Import Data'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'export']); ?>"><i class="fas fa-download me-2 text-muted"></i><?php echo __('Export Data'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'history']); ?>"><i class="fas fa-history me-2 text-muted"></i><?php echo __('Migration History'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasIngest): ?>
        <!-- Data Ingest -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #0dcaf0 !important;">
                    <h5 class="mb-0"><i class="fas fa-file-import me-2"></i><?php echo __('Data Ingest'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Ingest Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'configure']); ?>"><i class="fas fa-plus-circle me-2 text-muted"></i><?php echo __('New Ingest'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'downloadTemplate', 'sector' => 'archive']); ?>"><i class="fas fa-download me-2 text-muted"></i><?php echo __('CSV Template'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasHeritage2): ?>
        <!-- Heritage Management -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6c757d !important;">
                    <h5 class="mb-0"><i class="fas fa-landmark me-2"></i><?php echo __('Heritage Management'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminDashboard']); ?>"><i class="fas fa-cogs me-2 text-muted"></i><?php echo __('Admin Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'analyticsDashboard']); ?>"><i class="fas fa-chart-line me-2 text-muted"></i><?php echo __('Analytics Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianDashboard']); ?>"><i class="fas fa-user-shield me-2 text-muted"></i><?php echo __('Custodian Dashboard'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($hasPreservation && $canManage): ?>
    <!-- Digital Preservation Row -->
    <div class="row mb-4">
        <!-- Digital Preservation -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #17a2b8 !important;">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Digital Preservation'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Preservation Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'fixityLog']); ?>"><i class="fas fa-check-double me-2 text-muted"></i><?php echo __('Fixity Verification'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'events']); ?>"><i class="fas fa-history me-2 text-muted"></i><?php echo __('PREMIS Events'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'reports']); ?>"><i class="fas fa-chart-bar me-2 text-muted"></i><?php echo __('Preservation Reports'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'browse']); ?>"><i class="fas fa-layer-group me-2 text-muted"></i><?php echo __('TIFF to PDF Merge Jobs'); ?></a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Format Registry -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6610f2 !important;">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('Format Registry'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'formats']); ?>"><i class="fas fa-list me-2 text-muted"></i><?php echo __('Browse Formats'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'formats']) . '?risk=high'; ?>"><i class="fas fa-exclamation-triangle me-2 text-muted"></i><?php echo __('At-Risk Formats'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'policies']); ?>"><i class="fas fa-cogs me-2 text-muted"></i><?php echo __('Preservation Policies'); ?></a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Checksums & Fixity -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #28a745 !important;">
                    <h5 class="mb-0"><i class="fas fa-fingerprint me-2"></i><?php echo __('Checksums & Integrity'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'reports']) . '?type=missing'; ?>"><i class="fas fa-exclamation-circle me-2 text-muted"></i><?php echo __('Missing Checksums'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'reports']) . '?type=stale'; ?>"><i class="fas fa-clock me-2 text-muted"></i><?php echo __('Stale Verification'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'fixityLog']) . '?status=failed'; ?>"><i class="fas fa-times-circle me-2 text-muted"></i><?php echo __('Failed Checks'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (($hasCDPA || $hasNAZ || $hasIPSAS || $hasNMMZ) && $isAdmin): ?>
    <!-- Zimbabwe Compliance Row -->
    <div class="row mb-4">
        <?php if ($hasCDPA): ?>
        <!-- CDPA - Data Protection -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #198754 !important;">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('CDPA Data Protection'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('CDPA Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'license']); ?>"><i class="fas fa-id-card me-2 text-muted"></i><?php echo __('POTRAZ License'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'requests']); ?>"><i class="fas fa-user-clock me-2 text-muted"></i><?php echo __('Data Subject Requests'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breaches']); ?>"><i class="fas fa-exclamation-triangle me-2 text-muted"></i><?php echo __('Breach Register'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasNAZ): ?>
        <!-- NAZ - National Archives -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #0d6efd !important;">
                    <h5 class="mb-0"><i class="fas fa-landmark me-2"></i><?php echo __('NAZ Archives'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('NAZ Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'closures']); ?>"><i class="fas fa-lock me-2 text-muted"></i><?php echo __('Closure Periods'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits']); ?>"><i class="fas fa-id-card me-2 text-muted"></i><?php echo __('Research Permits'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'naz', 'action' => 'transfers']); ?>"><i class="fas fa-truck me-2 text-muted"></i><?php echo __('Records Transfers'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasIPSAS): ?>
        <!-- IPSAS - Heritage Assets -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #ffc107 !important; color: #000 !important;">
                    <h5 class="mb-0" style="color: #000 !important;"><i class="fas fa-coins me-2"></i><?php echo __('IPSAS Heritage Assets'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('IPSAS Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assets']); ?>"><i class="fas fa-archive me-2 text-muted"></i><?php echo __('Asset Register'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'valuations']); ?>"><i class="fas fa-calculator me-2 text-muted"></i><?php echo __('Valuations'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'insurance']); ?>"><i class="fas fa-shield-alt me-2 text-muted"></i><?php echo __('Insurance'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($hasNMMZ): ?>
    <!-- NMMZ Row (separate if all 4 plugins are active) -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6c757d !important;">
                    <h5 class="mb-0"><i class="fas fa-monument me-2"></i><?php echo __('NMMZ Monuments'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('NMMZ Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'monuments']); ?>"><i class="fas fa-monument me-2 text-muted"></i><?php echo __('National Monuments'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'antiquities']); ?>"><i class="fas fa-vase me-2 text-muted"></i><?php echo __('Antiquities Register'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permits']); ?>"><i class="fas fa-file-export me-2 text-muted"></i><?php echo __('Export Permits'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'sites']); ?>"><i class="fas fa-map-marker-alt me-2 text-muted"></i><?php echo __('Archaeological Sites'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php end_slot(); ?>
