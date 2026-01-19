<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-bar-chart-line text-primary me-2"></i><?php echo __('Report Builder'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $sf_user->getFlash('success'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $sf_user->getFlash('error'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Total Reports'); ?></h6>
                        <h2 class="mb-0"><?php echo $statistics['total_reports']; ?></h2>
                    </div>
                    <i class="bi bi-file-earmark-bar-graph fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php $colors = ['success', 'info', 'warning']; $i = 0; ?>
    <?php $rawStats = $sf_data->getRaw('statistics'); ?>
    <?php foreach (array_slice($rawStats['by_source'] ?? [], 0, 3) as $source => $count): ?>
    <div class="col-md-3">
        <div class="card bg-<?php echo $colors[$i % 3]; ?> text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo $dataSources[$source]['label'] ?? ucfirst($source); ?></h6>
                        <h2 class="mb-0"><?php echo $count; ?></h2>
                    </div>
                    <i class="bi <?php echo $dataSources[$source]['icon'] ?? 'bi-file-earmark'; ?> fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php $i++; endforeach; ?>
</div>

<!-- Action Buttons -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'create']); ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i><?php echo __('Create New Report'); ?>
        </a>
        <a href="/admin/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-speedometer2 me-1"></i><?php echo __('Central Dashboard'); ?>
        </a>
    </div>
    <div>
        <input type="text" class="form-control" id="searchReports" placeholder="<?php echo __('Search reports...'); ?>" style="width: 250px;">
    </div>
</div>

<!-- Reports List - Grouped by Category -->
<?php
// Group reports by category
$groupedReports = [];
$categoryIcons = [
    'Archives' => 'bi-archive',
    'Collections' => 'bi-collection',
    'Heritage' => 'bi-bank',
    'Spectrum' => 'bi-gem',
    'Privacy' => 'bi-shield-check',
    'Rights' => 'bi-c-circle',
    'Compliance' => 'bi-clipboard-check',
    'Security' => 'bi-shield-lock',
    'Vendors' => 'bi-building',
    'General' => 'bi-folder',
];
foreach ($reports as $report) {
    $cat = $report->category ?? 'General';
    if (!isset($groupedReports[$cat])) {
        $groupedReports[$cat] = [];
    }
    $groupedReports[$cat][] = $report;
}
?>

<?php if (empty($reports)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
        <?php echo __('No custom reports yet.'); ?>
        <br>
        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'create']); ?>" class="btn btn-primary mt-3">
            <i class="bi bi-plus-lg me-1"></i><?php echo __('Create Your First Report'); ?>
        </a>
    </div>
</div>
<?php else: ?>
    <?php foreach ($groupedReports as $category => $categoryReports): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <span>
                <i class="bi <?php echo $categoryIcons[$category] ?? 'bi-folder'; ?> me-2"></i>
                <strong><?php echo $category; ?></strong>
            </span>
            <span class="badge bg-secondary"><?php echo count($categoryReports); ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40"></th>
                        <th><?php echo __('Name'); ?></th>
                        <th><?php echo __('Data Source'); ?></th>
                        <th><?php echo __('Visibility'); ?></th>
                        <th><?php echo __('Last Updated'); ?></th>
                        <th width="200"><?php echo __('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryReports as $report): ?>
                    <tr class="report-row">
                        <td class="text-center">
                            <i class="bi <?php echo $dataSources[$report->data_source]['icon'] ?? 'bi-file-earmark'; ?> text-muted fs-5"></i>
                        </td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'preview', 'id' => $report->id]); ?>" class="fw-bold text-decoration-none">
                                <?php echo $report->name; ?>
                            </a>
                            <?php if ($report->description): ?>
                            <br><small class="text-muted"><?php echo substr($report->description, 0, 80); ?><?php echo strlen($report->description) > 80 ? '...' : ''; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <?php echo $dataSources[$report->data_source]['label'] ?? $report->data_source; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($report->is_public): ?>
                                <span class="badge bg-success"><i class="bi bi-globe me-1"></i><?php echo __('Public'); ?></span>
                            <?php elseif ($report->is_shared): ?>
                                <span class="badge bg-info"><i class="bi bi-people me-1"></i><?php echo __('Shared'); ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-lock me-1"></i><?php echo __('Private'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?php echo date('Y-m-d H:i', strtotime($report->updated_at)); ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'preview', 'id' => $report->id]); ?>" class="btn btn-outline-primary" title="<?php echo __('Preview'); ?>">
                                    <i class="bi bi-eye me-1"></i><?php echo __('View'); ?>
                                </a>
                                <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'edit', 'id' => $report->id]); ?>" class="btn btn-outline-secondary" title="<?php echo __('Edit'); ?>">
                                    <i class="bi bi-pencil me-1"></i><?php echo __('Edit'); ?>
                                </a>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="<?php echo __('Export'); ?>">
                                        <i class="bi bi-download me-1"></i><?php echo __('Export'); ?>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'export', 'id' => $report->id, 'format' => 'csv']); ?>"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
                                        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'export', 'id' => $report->id, 'format' => 'xlsx']); ?>"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Excel</a></li>
                                        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'export', 'id' => $report->id, 'format' => 'pdf']); ?>"><i class="bi bi-filetype-pdf me-2"></i>PDF</a></li>
                                    </ul>
                                </div>
                                <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'delete', 'id' => $report->id, 'confirm' => 1]); ?>" class="btn btn-outline-danger" title="<?php echo __('Delete'); ?>" onclick="return confirm('<?php echo __('Are you sure you want to delete this report?'); ?>');">
                                    <i class="bi bi-trash me-1"></i><?php echo __('Delete'); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchReports');
    const tableRows = document.querySelectorAll('.report-row');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        tableRows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
});
</script>
<?php end_slot() ?>
