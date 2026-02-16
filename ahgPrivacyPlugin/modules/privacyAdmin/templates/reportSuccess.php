<?php
$statsRaw = isset($sf_data) ? $sf_data->getRaw('stats') : (isset($stats) ? $stats : (object)[]);
$jurisdictionsRaw = isset($sf_data) ? $sf_data->getRaw('jurisdictions') : (isset($jurisdictions) ? $jurisdictions : []);
$usersRaw = isset($sf_data) ? $sf_data->getRaw('users') : (isset($users) ? $users : collect());
$dsarsByTypeRaw = isset($sf_data) ? $sf_data->getRaw('dsarsByType') : (isset($dsarsByType) ? $dsarsByType : collect());
$breachesBySeverityRaw = isset($sf_data) ? $sf_data->getRaw('breachesBySeverity') : (isset($breachesBySeverity) ? $breachesBySeverity : collect());
$dsarsByJurisdictionRaw = isset($sf_data) ? $sf_data->getRaw('dsarsByJurisdiction') : (isset($dsarsByJurisdiction) ? $dsarsByJurisdiction : collect());
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="fas fa-chart-bar me-2"></i><?php echo __('Privacy Compliance Reports'); ?></h1>
        <div class="d-flex gap-2">
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'export', 'type' => 'dsar', 'format' => 'csv']); ?>" class="btn btn-outline-success">
                <i class="fas fa-download me-1"></i><?php echo __('Export DSARs'); ?>
            </a>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'export', 'type' => 'breach', 'format' => 'csv']); ?>" class="btn btn-outline-danger">
                <i class="fas fa-download me-1"></i><?php echo __('Export Breaches'); ?>
            </a>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?>
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h3><?php echo number_format($statsRaw->total_dsars ?? 0); ?></h3>
                    <small><?php echo __('Total DSARs'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body text-center">
                    <h3><?php echo number_format($statsRaw->pending_dsars ?? 0); ?></h3>
                    <small><?php echo __('Pending DSARs'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body text-center">
                    <h3><?php echo number_format($statsRaw->total_breaches ?? 0); ?></h3>
                    <small><?php echo __('Total Breaches'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <h3><?php echo number_format($statsRaw->resolved_breaches ?? 0); ?></h3>
                    <small><?php echo __('Resolved Breaches'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- DSARs by Request Type -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('DSARs by Request Type'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (count($dsarsByTypeRaw) > 0): ?>
                    <table class="table table-sm table-hover">
                        <thead><tr><th><?php echo __('Type'); ?></th><th class="text-end"><?php echo __('Count'); ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($dsarsByTypeRaw as $row): ?>
                            <tr>
                                <td><span class="badge bg-primary me-1"><?php echo htmlspecialchars($row->request_type ?? ''); ?></span></td>
                                <td class="text-end"><?php echo number_format($row->count); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-muted"><?php echo __('No DSAR requests recorded.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Breaches by Severity -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Breaches by Severity'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (count($breachesBySeverityRaw) > 0): ?>
                    <table class="table table-sm table-hover">
                        <thead><tr><th><?php echo __('Severity'); ?></th><th class="text-end"><?php echo __('Count'); ?></th></tr></thead>
                        <tbody>
                            <?php
                            $severityColors = ['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'secondary'];
                            foreach ($breachesBySeverityRaw as $row):
                                $color = $severityColors[$row->severity] ?? 'secondary';
                            ?>
                            <tr>
                                <td><span class="badge bg-<?php echo $color; ?>"><?php echo htmlspecialchars(ucfirst($row->severity ?? '')); ?></span></td>
                                <td class="text-end"><?php echo number_format($row->count); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-muted"><?php echo __('No breaches recorded.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DSARs by Jurisdiction -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-globe me-2"></i><?php echo __('DSARs by Jurisdiction'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (count($dsarsByJurisdictionRaw) > 0): ?>
            <div class="row">
                <?php foreach ($dsarsByJurisdictionRaw as $row): ?>
                <div class="col-md-3 mb-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h4><?php echo number_format($row->count); ?></h4>
                            <small class="text-muted"><?php echo htmlspecialchars(strtoupper($row->jurisdiction ?? 'Unknown')); ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted"><?php echo __('No DSAR requests recorded.'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
