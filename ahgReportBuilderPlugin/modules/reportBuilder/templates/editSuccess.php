<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-pencil-square text-primary me-2"></i><?php echo __('Report Designer'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
// Get raw arrays to avoid sfOutputEscaperArrayDecorator issues with in_array()
$rawReport = $sf_data->getRaw('report');
$rawColumns = is_array($rawReport->columns) ? $rawReport->columns : [];
$rawFilters = is_array($rawReport->filters) ? $rawReport->filters : [];
$rawCharts = is_array($rawReport->charts) ? $rawReport->charts : [];
$rawSortConfig = is_array($rawReport->sort_config) ? $rawReport->sort_config : [];
$rawLayout = is_array($rawReport->layout) ? $rawReport->layout : ['blocks' => []];
?>
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

<!-- Toolbar -->
<div class="bg-light border-bottom py-2 px-3 mb-4 sticky-top" style="z-index: 1020;">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i><?php echo __('Report Builder'); ?>
            </a>
            <div>
                <h5 class="mb-0" id="reportName"><?php echo htmlspecialchars($report->name); ?></h5>
                <small class="text-muted">
                    <i class="bi <?php echo $dataSource['icon']; ?> me-1"></i><?php echo $dataSource['label']; ?>
                </small>
            </div>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" id="btnSave">
                <i class="bi bi-save me-1"></i><?php echo __('Save'); ?>
            </button>
            <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'preview', 'id' => $report->id]); ?>" class="btn btn-outline-primary" target="_blank">
                <i class="bi bi-eye me-1"></i><?php echo __('Preview'); ?>
            </a>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i><?php echo __('Export'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'export', 'id' => $report->id, 'format' => 'csv']); ?>"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
                    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'export', 'id' => $report->id, 'format' => 'xlsx']); ?>"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Excel</a></li>
                    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'export', 'id' => $report->id, 'format' => 'pdf']); ?>"><i class="bi bi-filetype-pdf me-2"></i>PDF</a></li>
                </ul>
            </div>
            <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'schedule', 'id' => $report->id]); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-clock me-1"></i><?php echo __('Schedule'); ?>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Sidebar: Data Source & Columns -->
    <div class="col-md-3">
        <!-- Report Settings -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <i class="bi bi-gear me-1"></i><?php echo __('Settings'); ?>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small"><?php echo __('Report Name'); ?></label>
                    <input type="text" class="form-control form-control-sm" id="inputName" value="<?php echo htmlspecialchars($report->name); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small"><?php echo __('Description'); ?></label>
                    <textarea class="form-control form-control-sm" id="inputDescription" rows="2"><?php echo htmlspecialchars($report->description ?? ''); ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label small"><?php echo __('Visibility'); ?></label>
                    <select class="form-select form-select-sm" id="inputVisibility">
                        <option value="private" <?php echo !$report->is_shared && !$report->is_public ? 'selected' : ''; ?>><?php echo __('Private'); ?></option>
                        <option value="shared" <?php echo $report->is_shared && !$report->is_public ? 'selected' : ''; ?>><?php echo __('Shared'); ?></option>
                        <option value="public" <?php echo $report->is_public ? 'selected' : ''; ?>><?php echo __('Public'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Available Columns -->
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-columns me-1"></i><?php echo __('Available Columns'); ?></span>
                <button class="btn btn-sm btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#columnsCollapse">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
            <div class="collapse show" id="columnsCollapse">
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <div class="p-2">
                        <input type="text" class="form-control form-control-sm" id="columnSearch" placeholder="<?php echo __('Search columns...'); ?>">
                    </div>
                    <?php foreach ($columnsGrouped as $group => $columns): ?>
                    <div class="border-top">
                        <div class="px-3 py-2 bg-light small fw-bold"><?php echo $group; ?></div>
                        <div class="list-group list-group-flush" id="availableColumns">
                            <?php foreach ($columns as $key => $col): ?>
                            <label class="list-group-item list-group-item-action py-1 column-item" data-column="<?php echo $key; ?>">
                                <input class="form-check-input me-2 column-checkbox" type="checkbox" value="<?php echo $key; ?>"
                                       <?php echo in_array($key, $rawColumns) ? 'checked' : ''; ?>>
                                <span class="small"><?php echo $col['label']; ?></span>
                                <span class="badge bg-secondary float-end" style="font-size: 0.65rem;"><?php echo $col['type']; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-funnel me-1"></i><?php echo __('Filters'); ?></span>
                <button class="btn btn-sm btn-outline-primary" id="btnAddFilter">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <div class="card-body p-2" id="filtersContainer">
                <?php if (empty($rawFilters)): ?>
                <p class="text-muted small text-center mb-0 py-2"><?php echo __('No filters applied'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sorting -->
        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-sort-down me-1"></i><?php echo __('Sort Order'); ?>
            </div>
            <div class="card-body p-2">
                <div class="mb-2">
                    <select class="form-select form-select-sm" id="sortColumn">
                        <option value=""><?php echo __('Select column...'); ?></option>
                        <?php foreach ($allColumns as $key => $col): ?>
                        <?php if (isset($col['sortable']) && $col['sortable']): ?>
                        <option value="<?php echo $key; ?>" <?php echo isset($rawSortConfig[0]) && $rawSortConfig[0]['column'] === $key ? 'selected' : ''; ?>>
                            <?php echo $col['label']; ?>
                        </option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <select class="form-select form-select-sm" id="sortDirection">
                        <option value="desc" <?php echo isset($rawSortConfig[0]) && ($rawSortConfig[0]['direction'] ?? 'desc') === 'desc' ? 'selected' : ''; ?>><?php echo __('Descending'); ?></option>
                        <option value="asc" <?php echo isset($rawSortConfig[0]) && ($rawSortConfig[0]['direction'] ?? 'desc') === 'asc' ? 'selected' : ''; ?>><?php echo __('Ascending'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content: Designer Canvas -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-grid-3x3 me-1"></i><?php echo __('Report Layout'); ?></span>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" id="btnAddTable" title="<?php echo __('Add Table'); ?>">
                        <i class="bi bi-table me-1"></i><?php echo __('Table'); ?>
                    </button>
                    <button class="btn btn-outline-secondary" id="btnAddChart" title="<?php echo __('Add Chart'); ?>">
                        <i class="bi bi-bar-chart me-1"></i><?php echo __('Chart'); ?>
                    </button>
                    <button class="btn btn-outline-secondary" id="btnAddStat" title="<?php echo __('Add Stat Card'); ?>">
                        <i class="bi bi-123 me-1"></i><?php echo __('Stats'); ?>
                    </button>
                </div>
            </div>
            <div class="card-body" id="designerCanvas" style="min-height: 500px; background: #f8f9fa;">
                <!-- Layout blocks will be rendered here -->
                <div class="layout-blocks" id="layoutBlocks">
                    <?php foreach ($rawLayout['blocks'] ?? [] as $index => $block): ?>
                    <div class="layout-block mb-3" data-block-id="<?php echo $block['id'] ?? $index; ?>" data-block-type="<?php echo $block['type']; ?>">
                        <?php if ($block['type'] === 'table'): ?>
                        <div class="card">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                                <span class="drag-handle cursor-move"><i class="bi bi-grip-vertical text-muted me-2"></i><?php echo __('Data Table'); ?></span>
                                <button class="btn btn-sm btn-outline-danger btn-remove-block"><i class="bi bi-x"></i></button>
                            </div>
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr id="previewHeaders">
                                                <?php foreach ($rawColumns as $col): ?>
                                                <th class="small"><?php echo $allColumns[$col]['label'] ?? $col; ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody id="previewBody">
                                            <tr>
                                                <td colspan="<?php echo count($rawColumns); ?>" class="text-center text-muted py-3">
                                                    <i class="bi bi-table me-2"></i><?php echo __('Data will appear here'); ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($block['type'] === 'chart'): ?>
                        <div class="card">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                                <span class="drag-handle cursor-move"><i class="bi bi-grip-vertical text-muted me-2"></i><?php echo __('Chart'); ?></span>
                                <button class="btn btn-sm btn-outline-danger btn-remove-block"><i class="bi bi-x"></i></button>
                            </div>
                            <div class="card-body p-3">
                                <canvas id="chart_<?php echo $block['id'] ?? $index; ?>" height="200"></canvas>
                            </div>
                        </div>
                        <?php elseif ($block['type'] === 'stat'): ?>
                        <div class="card">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                                <span class="drag-handle cursor-move"><i class="bi bi-grip-vertical text-muted me-2"></i><?php echo __('Statistic'); ?></span>
                                <button class="btn btn-sm btn-outline-danger btn-remove-block"><i class="bi bi-x"></i></button>
                            </div>
                            <div class="card-body text-center py-4">
                                <h2 class="mb-0">--</h2>
                                <small class="text-muted"><?php echo __('Total Records'); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($rawLayout['blocks'])): ?>
                <div class="text-center py-5 text-muted" id="emptyCanvas">
                    <i class="bi bi-layout-text-window-reverse fs-1 d-block mb-3"></i>
                    <p><?php echo __('Add components to your report using the buttons above'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Sidebar: Properties Panel -->
    <div class="col-md-3">
        <!-- Selected Columns Order -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <i class="bi bi-list-ol me-1"></i><?php echo __('Column Order'); ?>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush sortable-list" id="selectedColumns">
                    <?php foreach ($rawColumns as $col): ?>
                    <li class="list-group-item list-group-item-action py-2 d-flex justify-content-between align-items-center sortable-item" data-column="<?php echo $col; ?>">
                        <div class="d-flex align-items-center flex-grow-1 drag-handle" style="cursor: grab;">
                            <i class="bi bi-grip-vertical text-muted me-2"></i>
                            <span class="small"><?php echo $allColumns[$col]['label'] ?? $col; ?></span>
                        </div>
                        <button class="btn btn-sm btn-link text-danger p-0 btn-remove-column" type="button"><i class="bi bi-x"></i></button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (empty($rawColumns)): ?>
                <p class="text-muted small text-center mb-0 py-3"><?php echo __('Select columns from the left panel'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chart Configuration (shown when chart is selected) -->
        <div class="card mb-3" id="chartConfigPanel" style="display: none;">
            <div class="card-header py-2">
                <i class="bi bi-bar-chart me-1"></i><?php echo __('Chart Settings'); ?>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small"><?php echo __('Chart Type'); ?></label>
                    <select class="form-select form-select-sm" id="chartType">
                        <option value="bar"><?php echo __('Bar Chart'); ?></option>
                        <option value="line"><?php echo __('Line Chart'); ?></option>
                        <option value="pie"><?php echo __('Pie Chart'); ?></option>
                        <option value="doughnut"><?php echo __('Doughnut Chart'); ?></option>
                        <option value="horizontalBar"><?php echo __('Horizontal Bar'); ?></option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small"><?php echo __('Group By'); ?></label>
                    <select class="form-select form-select-sm" id="chartGroupBy">
                        <option value=""><?php echo __('Select field...'); ?></option>
                        <?php foreach ($allColumns as $key => $col): ?>
                        <option value="<?php echo $key; ?>"><?php echo $col['label']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small"><?php echo __('Aggregate'); ?></label>
                    <select class="form-select form-select-sm" id="chartAggregate">
                        <option value="count"><?php echo __('Count'); ?></option>
                        <option value="sum"><?php echo __('Sum'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Quick Preview -->
        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-lightning me-1"></i><?php echo __('Quick Preview'); ?>
            </div>
            <div class="card-body p-2">
                <button class="btn btn-sm btn-outline-primary w-100" id="btnQuickPreview">
                    <i class="bi bi-play me-1"></i><?php echo __('Load Preview Data'); ?>
                </button>
                <div id="previewStats" class="mt-2 small text-muted text-center"></div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden data for JavaScript -->
<script>
window.reportBuilder = {
    reportId: <?php echo $report->id; ?>,
    dataSource: <?php echo json_encode($report->data_source); ?>,
    columns: <?php echo json_encode($rawColumns); ?>,
    filters: <?php echo json_encode($rawFilters); ?>,
    charts: <?php echo json_encode($rawCharts); ?>,
    sortConfig: <?php echo json_encode($rawSortConfig); ?>,
    layout: <?php echo json_encode($rawLayout); ?>,
    allColumns: <?php echo json_encode($allColumns); ?>,
    apiSaveUrl: <?php echo json_encode(url_for(['module' => 'reportBuilder', 'action' => 'apiSave'])); ?>,
    apiDataUrl: <?php echo json_encode(url_for(['module' => 'reportBuilder', 'action' => 'apiData'])); ?>,
    apiChartUrl: <?php echo json_encode(url_for(['module' => 'reportBuilder', 'action' => 'apiChartData'])); ?>
};
</script>

<style>
.cursor-move { cursor: move; }
.layout-block { transition: all 0.2s ease; }
.layout-block:hover { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.column-item { cursor: pointer; }
.column-item:hover { background: #f8f9fa; }
#selectedColumns .sortable-item { cursor: grab; }
#selectedColumns .sortable-item:active { cursor: grabbing; }
#selectedColumns .sortable-item .drag-handle { cursor: grab; }
#selectedColumns .sortable-item .drag-handle:active { cursor: grabbing; }
#selectedColumns .sortable-item.sortable-chosen { background-color: #e3f2fd !important; }
#selectedColumns .sortable-item.sortable-ghost { opacity: 0.4; background-color: #bbdefb !important; }
.bg-primary-subtle { background-color: rgba(13, 110, 253, 0.15) !important; }
</style>

<!-- Load CDN libraries first -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Inline initialization as backup -->
<script>
(function() {
    function initDragDrop() {
        if (typeof Sortable === 'undefined') {
            console.log('Waiting for Sortable...');
            setTimeout(initDragDrop, 150);
            return;
        }

        var container = document.getElementById('selectedColumns');
        if (!container) {
            console.log('selectedColumns container not found');
            return;
        }

        if (container.sortableInstance) {
            console.log('Already initialized');
            return;
        }

        console.log('Creating Sortable instance...');
        container.sortableInstance = Sortable.create(container, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: '.drag-handle',
            draggable: '.sortable-item',
            onStart: function(evt) {
                console.log('Drag START:', evt.item.dataset.column);
            },
            onEnd: function(evt) {
                console.log('Drag END:', evt.item.dataset.column, 'from', evt.oldIndex, 'to', evt.newIndex);
                // Update config
                if (window.reportBuilder) {
                    window.reportBuilder.columns = [];
                    container.querySelectorAll('li[data-column]').forEach(function(li) {
                        window.reportBuilder.columns.push(li.dataset.column);
                    });
                    console.log('New order:', window.reportBuilder.columns);
                }
                // Mark dirty
                var saveBtn = document.getElementById('btnSave');
                if (saveBtn) saveBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save *';
            }
        });
        console.log('Sortable created successfully:', container.sortableInstance);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDragDrop);
    } else {
        initDragDrop();
    }
})();
</script>

<!-- Then load designer.js for other functionality -->
<script src="/plugins/ahgReportBuilderPlugin/js/designer.js"></script>
<?php end_slot() ?>
