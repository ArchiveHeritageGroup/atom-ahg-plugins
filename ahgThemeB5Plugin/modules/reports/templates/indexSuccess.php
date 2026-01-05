<?php decorate_with('layout_2col'); ?>

<?php
// Check which sector plugins are enabled
function isPluginActive($pluginName) {
    static $plugins = null;
    if ($plugins === null) {
        try {
            $conn = Propel::getConnection();
            $stmt = $conn->prepare('SELECT name FROM atom_plugin WHERE is_enabled = 1');
            $stmt->execute();
            $plugins = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
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
$hasGrap = isPluginActive('ahgGrapPlugin');
$hasResearch = isPluginActive('ahgResearchPlugin');
$hasDonor = isPluginActive('ahgDonorAgreementPlugin');
$hasRights = isPluginActive('ahgExtendedRightsPlugin');
$hasCondition = isPluginActive('ahgConditionPlugin');
$hasPrivacy = isPluginActive('arPrivacyPlugin');
$hasSecurity = isPluginActive('ahgSecurityClearancePlugin');
$hasAudit = isPluginActive('ahgAuditTrailPlugin');
$hasVendor = isPluginActive('ahgVendorPlugin');
$has3D = isPluginActive('ar3DPlugin');
$hasOais = isPluginActive('arOaisPlugin');
?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Quick Links'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'export', 'action' => 'archival']); ?>"><i class="fas fa-download me-2"></i><?php echo __('Export Data'); ?></a></li>
<?php // TODO: Create dashboard module ?>
    </ul>

    <?php if ($hasVendor): ?>
    <h4 class="mt-4"><?php echo __('Vendors'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'index']); ?>"><i class="fas fa-building me-2"></i><?php echo __('Vendor Dashboard'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'transactions']); ?>"><i class="fas fa-exchange-alt me-2"></i><?php echo __('Transactions'); ?></a></li>
    </ul>
    <?php endif; ?>

    <h4 class="mt-4"><?php echo __('Settings'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'levels']); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('Levels of Description'); ?></a></li>
    </ul>

    <h4 class="mt-4"><?php echo __('Compliance'); ?></h4>
    <ul class="list-unstyled">
        <?php if ($hasSecurity): ?>
        <li><a href="/admin/security/compliance"><i class="fas fa-shield-alt me-2"></i><?php echo __('Security'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasPrivacy): ?>
        <li><a href="/admin/privacy/manage"><i class="fas fa-user-shield me-2"></i><?php echo __('Privacy (POPIA)'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasCondition): ?>
        <li><a href="/admin/condition"><i class="fas fa-heartbeat me-2"></i><?php echo __('Condition'); ?></a></li>
        <?php endif; ?>
        <?php if ($hasRights): ?>
        <li><a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'dashboard']); ?>"><i class="fas fa-gavel me-2"></i><?php echo __('Rights'); ?></a></li>
        <?php endif; ?>
    </ul>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-chart-line"></i> <?php echo __('Reports Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
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
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'grap', 'action' => 'dashboard']); ?>"><i class="fas fa-balance-scale me-2 text-muted"></i><?php echo __('GRAP 103 Dashboard'); ?></a></li>
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
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'ahgDonor', 'action' => 'dashboard']); ?>"><i class="fas fa-handshake me-2 text-muted"></i><?php echo __('Donor Management'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasGallery): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'ahgGalleryPlugin', 'action' => 'dashboard']); ?>"><i class="fas fa-palette me-2 text-muted"></i><?php echo __('Gallery Management'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasLibrary): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'ahgLibraryPlugin', 'action' => 'browse']); ?>"><i class="fas fa-book me-2 text-muted"></i><?php echo __('Library Management'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasDam): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'dam', 'action' => 'dashboard']); ?>"><i class="fas fa-images me-2 text-muted"></i><?php echo __('Digital Asset Management'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasMuseum): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'index']); ?>"><i class="fas fa-landmark me-2 text-muted"></i><?php echo __("Museum Reports (CCO)"); ?></a></li>
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
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'grap', 'action' => 'export']); ?>"><i class="fas fa-balance-scale me-2 text-muted"></i><?php echo __('GRAP 103 Export'); ?></a></li>
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

    <!-- Compliance Row -->
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
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'ahgSecurityClearance', 'action' => 'report']); ?>"><i class="fas fa-user-shield me-2 text-muted"></i><?php echo __('Clearance Report'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($hasAudit): ?>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'index']); ?>"><i class="fas fa-clipboard-list me-2 text-muted"></i><?php echo __('Audit Log'); ?></a></li>
                    <li class="list-group-item"><a href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'export']); ?>"><i class="fas fa-download me-2 text-muted"></i><?php echo __('Export Audit Log'); ?></a></li>
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
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i><?php echo __('Privacy (POPIA/PAIA)'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="/admin/privacy/manage"><i class="fas fa-tachometer-alt me-2 text-muted"></i><?php echo __('Privacy Dashboard'); ?></a>
                    </li>
                    <li class="list-group-item"><a href="/admin/privacy/ropa"><i class="fas fa-clipboard-list me-2 text-muted"></i><?php echo __('ROPA'); ?></a></li>
                    <li class="list-group-item"><a href="/admin/privacy/dsar"><i class="fas fa-user-clock me-2 text-muted"></i><?php echo __('DSAR Requests'); ?></a></li>
                    <li class="list-group-item"><a href="/admin/privacy/breaches"><i class="fas fa-exclamation-circle me-2 text-muted"></i><?php echo __('Breach Register'); ?></a></li>
                    <li class="list-group-item"><a href="/admin/privacy/templates"><i class="fas fa-file-alt me-2 text-muted"></i><?php echo __('Template Library'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasCondition): ?>
        <!-- Condition -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i><?php echo __('Condition (Spectrum 5.0)'); ?></h5>
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

    <?php if ($hasRights): ?>
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
                        <a href="<?php echo url_for(['module' => 'right', 'action' => 'batch']); ?>"><i class="fas fa-layer-group me-2 text-muted"></i><?php echo __('Batch Rights Assignment'); ?></a>
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
                        <a href="<?php echo url_for(['module' => 'right', 'action' => 'batch', 'action_type' => 'embargo']); ?>"><i class="fas fa-plus-circle me-2 text-muted"></i><?php echo __('Apply Embargo'); ?></a>
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
                        <a href="<?php echo url_for(['module' => 'taxonomy', 'action' => 'index', 'slug' => 'rights-statements']); ?>"><i class="fas fa-copyright me-2 text-muted"></i><?php echo __('Rights Statements'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'taxonomy', 'action' => 'index', 'slug' => 'creative-commons']); ?>"><i class="fab fa-creative-commons me-2 text-muted"></i><?php echo __('Creative Commons'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'taxonomy', 'action' => 'index', 'slug' => 'tk-labels']); ?>"><i class="fas fa-globe-africa me-2 text-muted"></i><?php echo __('TK Labels'); ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo url_for(['module' => 'actor', 'action' => 'browse', 'type' => 'rightsHolder']); ?>"><i class="fas fa-user-tie me-2 text-muted"></i><?php echo __('Rights Holders'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($hasVendor): ?>
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

    <!-- TIFF to PDF Merge Jobs Section -->
    <div class="row mt-4">
        <div class="col-12">
            <?php include_partial('reports/tiffPdfMergeJobs'); ?>
        </div>
    </div>
</div>
<?php end_slot(); ?>
