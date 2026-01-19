<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-eye text-primary me-2"></i><?php echo htmlspecialchars($report->name); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($report->name); ?></li>
    </ol>
</nav>

<!-- Report Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="mb-1"><?php echo htmlspecialchars($report->name); ?></h4>
        <?php if ($report->description): ?>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($report->description); ?></p>
        <?php endif; ?>
    </div>
    <div class="btn-group">
        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'edit', 'id' => $report->id]); ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i><?php echo __('Edit Report'); ?>
        </a>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i><?php echo __('Export'); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'export', 'id' => $report->id, 'format' => 'csv']); ?>"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'export', 'id' => $report->id, 'format' => 'xlsx']); ?>"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Excel (XLSX)</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'export', 'id' => $report->id, 'format' => 'pdf']); ?>"><i class="bi bi-filetype-pdf me-2"></i>PDF</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Stats Bar -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75"><?php echo __('Total Records'); ?></small>
                        <h3 class="mb-0"><?php echo number_format($results['total']); ?></h3>
                    </div>
                    <i class="bi bi-database fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75"><?php echo __('Columns'); ?></small>
                        <h3 class="mb-0"><?php echo count($report->columns); ?></h3>
                    </div>
                    <i class="bi bi-list-columns fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75"><?php echo __('Page'); ?></small>
                        <h3 class="mb-0"><?php echo $results['page']; ?> / <?php echo $results['pages']; ?></h3>
                    </div>
                    <i class="bi bi-file-earmark-text fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75"><?php echo __('Generated'); ?></small>
                        <h3 class="mb-0" style="font-size: 1rem;"><?php echo date('Y-m-d H:i'); ?></h3>
                    </div>
                    <i class="bi bi-clock fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2"></i><?php echo __('Report Data'); ?></span>
        <div>
            <label class="me-2 small"><?php echo __('Per page:'); ?></label>
            <select class="form-select form-select-sm d-inline-block w-auto" id="perPage" onchange="window.location.href='?page=1&limit='+this.value">
                <option value="25" <?php echo $results['limit'] == 25 ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $results['limit'] == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $results['limit'] == 100 ? 'selected' : ''; ?>>100</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <?php foreach ($report->columns as $col): ?>
                    <th class="text-nowrap"><?php echo $allColumns[$col]['label'] ?? $col; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results['results'])): ?>
                <tr>
                    <td colspan="<?php echo count($report->columns); ?>" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <?php echo __('No data found matching your criteria.'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($results['results'] as $row): ?>
                    <tr>
                        <?php foreach ($report->columns as $col): ?>
                        <td>
                            <?php
                            $value = $row->{$col} ?? '';
                            $colType = $allColumns[$col]['type'] ?? 'string';

                            // Format based on type
                            if ($colType === 'datetime' && $value) {
                                echo date('Y-m-d H:i', strtotime($value));
                            } elseif ($colType === 'date' && $value) {
                                echo date('Y-m-d', strtotime($value));
                            } elseif ($colType === 'boolean') {
                                echo $value ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>';
                            } elseif ($colType === 'text' && strlen($value) > 100) {
                                echo '<span title="' . htmlspecialchars($value) . '">' . htmlspecialchars(substr($value, 0, 100)) . '...</span>';
                            } elseif ($col === 'id') {
                                echo '<a href="#">' . htmlspecialchars($value) . '</a>';
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($results['pages'] > 1): ?>
    <div class="card-footer">
        <nav aria-label="Report pagination">
            <ul class="pagination justify-content-center mb-0">
                <?php if ($results['page'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1&limit=<?php echo $results['limit']; ?>">&laquo;</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $results['page'] - 1; ?>&limit=<?php echo $results['limit']; ?>">&lsaquo;</a>
                </li>
                <?php endif; ?>

                <?php
                $start = max(1, $results['page'] - 2);
                $end = min($results['pages'], $results['page'] + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                <li class="page-item <?php echo $i == $results['page'] ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $results['limit']; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($results['page'] < $results['pages']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $results['page'] + 1; ?>&limit=<?php echo $results['limit']; ?>">&rsaquo;</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $results['pages']; ?>&limit=<?php echo $results['limit']; ?>">&raquo;</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php end_slot() ?>
