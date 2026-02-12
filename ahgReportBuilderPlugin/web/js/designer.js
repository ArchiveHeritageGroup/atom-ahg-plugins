/**
 * Report Builder Designer
 * Drag-drop interface for building custom reports
 */
(function() {
    'use strict';

    // Get configuration from window
    const config = window.reportBuilder || {};
    let isDirty = false;
    let blockCounter = 0;
    let chartInstances = {}; // Store Chart.js instances for updates

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initColumnSelection();
        initColumnOrdering();
        initLayoutBlocks();
        initSaveButton();
        initQuickPreview();
        initFilters();
        initChartSettings();
        updatePreviewHeaders();
    });

    /**
     * Initialize column selection checkboxes
     */
    function initColumnSelection() {
        const checkboxes = document.querySelectorAll('.column-checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const column = this.value;
                if (this.checked) {
                    addSelectedColumn(column);
                } else {
                    removeSelectedColumn(column);
                }
                markDirty();
                updatePreviewHeaders();
            });
        });

        // Column search
        const searchInput = document.getElementById('columnSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                document.querySelectorAll('.column-item').forEach(function(item) {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }
    }

    /**
     * Add a column to the selected list
     */
    function addSelectedColumn(column) {
        const container = document.getElementById('selectedColumns');
        const colConfig = config.allColumns[column] || {};

        const li = document.createElement('li');
        li.className = 'list-group-item list-group-item-action py-2 d-flex justify-content-between align-items-center sortable-item';
        li.dataset.column = column;
        li.innerHTML = `
            <div class="d-flex align-items-center flex-grow-1 drag-handle" style="cursor: grab;">
                <i class="bi bi-grip-vertical text-muted me-2"></i>
                <span class="small">${colConfig.label || column}</span>
            </div>
            <button class="btn btn-sm btn-link text-danger p-0 btn-remove-column" type="button"><i class="bi bi-x"></i></button>
        `;

        li.querySelector('.btn-remove-column').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            removeSelectedColumn(column);
            // Uncheck the checkbox
            const checkbox = document.querySelector('.column-checkbox[value="' + column + '"]');
            if (checkbox) checkbox.checked = false;
            markDirty();
            updatePreviewHeaders();
        });

        container.appendChild(li);

        // Remove empty message if present
        const emptyMsg = container.querySelector('p');
        if (emptyMsg) emptyMsg.remove();

        // Update config
        if (!config.columns.includes(column)) {
            config.columns.push(column);
        }
    }

    /**
     * Remove a column from the selected list
     */
    function removeSelectedColumn(column) {
        const container = document.getElementById('selectedColumns');
        const item = container.querySelector('[data-column="' + column + '"]');
        if (item) item.remove();

        // Update config
        const idx = config.columns.indexOf(column);
        if (idx > -1) config.columns.splice(idx, 1);

        // Show empty message if no columns left
        if (config.columns.length === 0) {
            container.innerHTML = '<p class="text-muted small text-center mb-0 py-3">Select columns from the left panel</p>';
        }
    }

    /**
     * Initialize column ordering with SortableJS
     * Uses retry mechanism to wait for SortableJS CDN to load
     */
    function initColumnOrdering() {
        const container = document.getElementById('selectedColumns');
        if (!container) return;

        // Retry if Sortable is not loaded yet
        if (typeof Sortable === 'undefined') {
            console.log('SortableJS not loaded yet, retrying...');
            setTimeout(initColumnOrdering, 100);
            return;
        }

        // Check if already initialized (avoid duplicate instances)
        if (container._sortableInstance) {
            console.log('Column ordering already initialized');
            return;
        }

        console.log('Initializing column ordering SortableJS');
        container._sortableInstance = new Sortable(container, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'bg-primary-subtle',
            chosenClass: 'bg-light',
            dragClass: 'shadow',
            onStart: function(evt) {
                console.log('Drag started', evt.item.dataset.column);
            },
            onEnd: function(evt) {
                console.log('Drag ended', evt.item.dataset.column);
                // Update column order in config
                config.columns = [];
                container.querySelectorAll('li').forEach(function(li) {
                    if (li.dataset.column) {
                        config.columns.push(li.dataset.column);
                    }
                });
                console.log('New column order:', config.columns);
                markDirty();
                updatePreviewHeaders();
            }
        });
        console.log('Column ordering initialized successfully');
    }

    /**
     * Initialize layout blocks (drag-drop, add, remove)
     */
    function initLayoutBlocks() {
        const container = document.getElementById('layoutBlocks');

        // Initialize Sortable for layout blocks with retry
        if (container) {
            if (typeof Sortable === 'undefined') {
                setTimeout(function() {
                    if (typeof Sortable !== 'undefined' && !container._sortableInstance) {
                        container._sortableInstance = new Sortable(container, {
                            animation: 150,
                            handle: '.drag-handle',
                            ghostClass: 'bg-primary-subtle',
                            onEnd: function() {
                                updateLayoutConfig();
                                markDirty();
                            }
                        });
                    }
                }, 200);
            } else if (!container._sortableInstance) {
                container._sortableInstance = new Sortable(container, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'bg-primary-subtle',
                    onEnd: function() {
                        updateLayoutConfig();
                        markDirty();
                    }
                });
            }
        }

        // Add block buttons
        document.getElementById('btnAddTable')?.addEventListener('click', function() {
            addBlock('table');
        });

        document.getElementById('btnAddChart')?.addEventListener('click', function() {
            addBlock('chart');
        });

        document.getElementById('btnAddStat')?.addEventListener('click', function() {
            addBlock('stat');
        });

        // Remove block buttons
        document.querySelectorAll('.btn-remove-block').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.closest('.layout-block').remove();
                updateLayoutConfig();
                markDirty();
            });
        });
    }

    /**
     * Add a new block to the layout
     */
    function addBlock(type) {
        const container = document.getElementById('layoutBlocks');
        const emptyCanvas = document.getElementById('emptyCanvas');
        if (emptyCanvas) emptyCanvas.remove();

        blockCounter++;
        const blockId = type + '_' + blockCounter;

        const block = document.createElement('div');
        block.className = 'layout-block mb-3';
        block.dataset.blockId = blockId;
        block.dataset.blockType = type;

        if (type === 'table') {
            block.innerHTML = `
                <div class="card">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                        <span class="drag-handle cursor-move"><i class="bi bi-grip-vertical text-muted me-2"></i>Data Table</span>
                        <button class="btn btn-sm btn-outline-danger btn-remove-block"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr id="previewHeaders_${blockId}">
                                        <th class="small text-muted">Select columns to see preview</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center text-muted py-3">
                                            <i class="bi bi-table me-2"></i>Data will appear here
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } else if (type === 'chart') {
            block.innerHTML = `
                <div class="card">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                        <span class="drag-handle cursor-move"><i class="bi bi-grip-vertical text-muted me-2"></i>Chart</span>
                        <button class="btn btn-sm btn-outline-danger btn-remove-block"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="card-body p-3">
                        <canvas id="chart_${blockId}" height="200"></canvas>
                    </div>
                </div>
            `;
            // Initialize chart after adding to DOM
            setTimeout(function() { initChart(blockId); }, 100);
        } else if (type === 'stat') {
            block.innerHTML = `
                <div class="card">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                        <span class="drag-handle cursor-move"><i class="bi bi-grip-vertical text-muted me-2"></i>Statistic</span>
                        <button class="btn btn-sm btn-outline-danger btn-remove-block"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="card-body text-center py-4">
                        <h2 class="mb-0" id="stat_${blockId}">--</h2>
                        <small class="text-muted">Total Records</small>
                    </div>
                </div>
            `;
        }

        container.appendChild(block);

        // Attach remove handler
        block.querySelector('.btn-remove-block').addEventListener('click', function() {
            block.remove();
            updateLayoutConfig();
            markDirty();
        });

        updateLayoutConfig();
        markDirty();
    }

    /**
     * Initialize a chart with placeholder or real data
     */
    function initChart(blockId, chartData) {
        const canvas = document.getElementById('chart_' + blockId);
        if (!canvas || typeof Chart === 'undefined') return;

        // Destroy existing instance if any
        if (chartInstances[blockId]) {
            chartInstances[blockId].destroy();
        }

        // Use provided data or placeholder
        const labels = chartData?.labels || ['Loading...'];
        const data = chartData?.data || [0];

        chartInstances[blockId] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Count',
                    data: data,
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(13, 202, 240, 0.7)',
                        'rgba(111, 66, 193, 0.7)',
                        'rgba(253, 126, 20, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ],
                    borderColor: [
                        'rgba(13, 110, 253, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(13, 202, 240, 1)',
                        'rgba(111, 66, 193, 1)',
                        'rgba(253, 126, 20, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: chartData ? 'Grouped by: ' + (config.chartGroupBy || 'count') : 'Click "Load Preview Data" to see chart'
                    }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    /**
     * Update all charts with real data
     */
    function updateCharts(groupBy) {
        if (!config.apiChartUrl) return;

        const chartBlocks = document.querySelectorAll('.layout-block[data-block-type="chart"]');
        if (chartBlocks.length === 0) return;

        // Use selected groupBy or first available column
        const effectiveGroupBy = groupBy || document.getElementById('chartGroupBy')?.value || config.columns[0];
        config.chartGroupBy = effectiveGroupBy;

        fetch(config.apiChartUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: config.reportId,
                groupBy: effectiveGroupBy,
                aggregate: 'count'
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                chartBlocks.forEach(block => {
                    const blockId = block.dataset.blockId;
                    initChart(blockId, result.data);
                });
            }
        })
        .catch(err => console.error('Error loading chart data:', err));
    }

    /**
     * Update layout configuration from DOM
     */
    function updateLayoutConfig() {
        config.layout = { blocks: [] };
        document.querySelectorAll('.layout-block').forEach(function(block) {
            config.layout.blocks.push({
                id: block.dataset.blockId,
                type: block.dataset.blockType
            });
        });
    }

    /**
     * Initialize save button
     */
    function initSaveButton() {
        const saveBtn = document.getElementById('btnSave');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveReport);
        }

        // Warn before leaving if dirty
        window.addEventListener('beforeunload', function(e) {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    /**
     * Save the report
     */
    function saveReport() {
        const saveBtn = document.getElementById('btnSave');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Saving...';

        // Gather settings
        const visibility = document.getElementById('inputVisibility').value;

        const data = {
            id: config.reportId,
            name: document.getElementById('inputName').value,
            description: document.getElementById('inputDescription').value,
            is_shared: visibility === 'shared' ? 1 : 0,
            is_public: visibility === 'public' ? 1 : 0,
            columns: config.columns,
            filters: config.filters || [],
            layout: config.layout,
            sort_config: getSortConfig(),
            charts: config.charts || []
        };

        fetch(config.apiSaveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                isDirty = false;
                saveBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Saved!';
                setTimeout(function() {
                    saveBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
                    saveBtn.disabled = false;
                }, 2000);

                // Update page title
                document.getElementById('reportName').textContent = data.name;
            } else {
                alert('Error: ' + (result.error || 'Failed to save'));
                saveBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
                saveBtn.disabled = false;
            }
        })
        .catch(function(err) {
            alert('Error saving report: ' + err.message);
            saveBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
            saveBtn.disabled = false;
        });
    }

    /**
     * Get sort configuration
     */
    function getSortConfig() {
        const column = document.getElementById('sortColumn')?.value;
        const direction = document.getElementById('sortDirection')?.value;
        if (column) {
            return [{ column: column, direction: direction || 'desc' }];
        }
        return [];
    }

    /**
     * Initialize quick preview
     */
    function initQuickPreview() {
        const btn = document.getElementById('btnQuickPreview');
        if (btn) {
            btn.addEventListener('click', loadPreviewData);
        }
    }

    /**
     * Load preview data via API
     */
    function loadPreviewData() {
        const btn = document.getElementById('btnQuickPreview');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Loading...';

        const data = {
            id: config.reportId,
            page: 1,
            limit: 5
        };

        fetch(config.apiDataUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                updatePreviewTable(result.data);
                updateStatBlocks(result.data.total);
                document.getElementById('previewStats').textContent =
                    result.data.total + ' total records';

                // Also load chart data
                updateCharts();
            } else {
                alert('Error: ' + (result.error || 'Failed to load data'));
            }
            btn.innerHTML = '<i class="bi bi-play me-1"></i>Load Preview Data';
            btn.disabled = false;
        })
        .catch(function(err) {
            alert('Error loading preview: ' + err.message);
            btn.innerHTML = '<i class="bi bi-play me-1"></i>Load Preview Data';
            btn.disabled = false;
        });
    }

    /**
     * Update preview table headers (draggable)
     */
    function updatePreviewHeaders() {
        const headers = document.getElementById('previewHeaders');
        if (!headers) return;

        if (config.columns.length === 0) {
            headers.innerHTML = '<th class="small text-muted">Select columns to see preview</th>';
            return;
        }

        let html = '';
        config.columns.forEach(function(col) {
            const colConfig = config.allColumns[col] || {};
            html += '<th class="small draggable-col" data-column="' + col + '">' +
                '<i class="bi bi-grip-vertical text-muted me-1" style="font-size:0.7rem;"></i>' +
                (colConfig.label || col) + '</th>';
        });
        headers.innerHTML = html;

        initHeaderDragDrop();
    }

    /**
     * Initialize drag-drop on preview table header cells
     */
    function initHeaderDragDrop() {
        var headers = document.getElementById('previewHeaders');
        if (!headers) return;

        if (typeof Sortable === 'undefined') {
            setTimeout(initHeaderDragDrop, 150);
            return;
        }

        // Destroy previous instance
        if (headers._sortableInstance) {
            headers._sortableInstance.destroy();
            headers._sortableInstance = null;
        }

        if (headers.querySelectorAll('th.draggable-col').length < 2) return;

        headers._sortableInstance = new Sortable(headers, {
            animation: 150,
            draggable: 'th.draggable-col',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            direction: 'horizontal',
            onEnd: function() {
                // Read new order from DOM
                var newOrder = [];
                headers.querySelectorAll('th.draggable-col').forEach(function(th) {
                    if (th.dataset.column) newOrder.push(th.dataset.column);
                });
                config.columns = newOrder;

                // Sync right sidebar Column Order list
                syncColumnOrderPanel();

                // Re-order preview data rows
                reorderPreviewDataCells();

                markDirty();
            }
        });
    }

    /**
     * Sync the right-sidebar Column Order list to match config.columns
     */
    function syncColumnOrderPanel() {
        var container = document.getElementById('selectedColumns');
        if (!container) return;

        // Build a map of existing <li> elements by column key
        var liMap = {};
        container.querySelectorAll('li[data-column]').forEach(function(li) {
            liMap[li.dataset.column] = li;
        });

        // Re-append in new order
        config.columns.forEach(function(col) {
            if (liMap[col]) container.appendChild(liMap[col]);
        });
    }

    /**
     * Re-order cells in each preview data row to match new column order
     */
    function reorderPreviewDataCells() {
        var body = document.getElementById('previewBody');
        if (!body) return;

        var headers = document.getElementById('previewHeaders');
        if (!headers) return;

        // Build column-index mapping from old DOM order stored in data rows
        var rows = body.querySelectorAll('tr');
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            // Only re-order if cells have data-column attrs
            if (cells.length > 0 && cells[0].dataset.column) {
                var cellMap = {};
                cells.forEach(function(td) { cellMap[td.dataset.column] = td; });
                config.columns.forEach(function(col) {
                    if (cellMap[col]) row.appendChild(cellMap[col]);
                });
            }
        });
    }

    /**
     * Update preview table with data
     */
    function updatePreviewTable(data) {
        const body = document.getElementById('previewBody');
        if (!body) return;

        if (!data.results || data.results.length === 0) {
            body.innerHTML = '<tr><td colspan="' + config.columns.length + '" class="text-center text-muted py-3">No data found</td></tr>';
            return;
        }

        let html = '';
        data.results.forEach(function(row) {
            html += '<tr>';
            config.columns.forEach(function(col) {
                let value = row[col] || '';
                if (value.length > 50) value = value.substring(0, 50) + '...';
                html += '<td class="small" data-column="' + col + '">' + escapeHtml(value) + '</td>';
            });
            html += '</tr>';
        });
        body.innerHTML = html;
    }

    /**
     * Update stat blocks with total count
     */
    function updateStatBlocks(total) {
        document.querySelectorAll('[id^="stat_"]').forEach(function(el) {
            el.textContent = total.toLocaleString();
        });
    }

    /**
     * Initialize filter management
     */
    function initFilters() {
        const addBtn = document.getElementById('btnAddFilter');
        if (addBtn) {
            addBtn.addEventListener('click', addFilter);
        }
    }

    /**
     * Add a new filter
     */
    function addFilter() {
        const container = document.getElementById('filtersContainer');

        // Remove empty message
        const emptyMsg = container.querySelector('p');
        if (emptyMsg) emptyMsg.remove();

        const filterDiv = document.createElement('div');
        filterDiv.className = 'filter-item border rounded p-2 mb-2';

        let columnOptions = '';
        for (const key in config.allColumns) {
            columnOptions += '<option value="' + key + '">' + config.allColumns[key].label + '</option>';
        }

        filterDiv.innerHTML = `
            <div class="row g-2">
                <div class="col-5">
                    <select class="form-select form-select-sm filter-column">
                        <option value="">Column...</option>
                        ${columnOptions}
                    </select>
                </div>
                <div class="col-4">
                    <select class="form-select form-select-sm filter-operator">
                        <option value="equals">Equals</option>
                        <option value="contains">Contains</option>
                        <option value="starts_with">Starts with</option>
                        <option value="is_not_null">Is not empty</option>
                        <option value="is_null">Is empty</option>
                    </select>
                </div>
                <div class="col-3">
                    <button class="btn btn-sm btn-outline-danger w-100 btn-remove-filter"><i class="bi bi-x"></i></button>
                </div>
            </div>
            <div class="mt-2">
                <input type="text" class="form-control form-control-sm filter-value" placeholder="Value...">
            </div>
        `;

        filterDiv.querySelector('.btn-remove-filter').addEventListener('click', function() {
            filterDiv.remove();
            updateFiltersConfig();
            markDirty();
        });

        filterDiv.querySelectorAll('select, input').forEach(function(el) {
            el.addEventListener('change', function() {
                updateFiltersConfig();
                markDirty();
            });
        });

        container.appendChild(filterDiv);
    }

    /**
     * Initialize chart settings panel
     */
    function initChartSettings() {
        const groupBySelect = document.getElementById('chartGroupBy');
        const chartTypeSelect = document.getElementById('chartType');
        const chartPanel = document.getElementById('chartConfigPanel');

        // Show panel if there are chart blocks
        function checkChartBlocks() {
            const hasCharts = document.querySelectorAll('.layout-block[data-block-type="chart"]').length > 0;
            if (chartPanel) {
                chartPanel.style.display = hasCharts ? '' : 'none';
            }
        }
        checkChartBlocks();

        // Listen for groupBy changes
        if (groupBySelect) {
            groupBySelect.addEventListener('change', function() {
                if (this.value) {
                    updateCharts(this.value);
                    markDirty();
                }
            });
        }

        // Listen for chart type changes
        if (chartTypeSelect) {
            chartTypeSelect.addEventListener('change', function() {
                updateChartTypes(this.value);
                markDirty();
            });
        }

        // Observe layout changes to show/hide chart panel
        const layoutContainer = document.getElementById('layoutBlocks');
        if (layoutContainer) {
            const observer = new MutationObserver(checkChartBlocks);
            observer.observe(layoutContainer, { childList: true, subtree: true });
        }
    }

    /**
     * Update chart types for all charts
     */
    function updateChartTypes(chartType) {
        for (const blockId in chartInstances) {
            const chart = chartInstances[blockId];
            if (chart) {
                chart.config.type = chartType === 'horizontalBar' ? 'bar' : chartType;
                if (chartType === 'horizontalBar') {
                    chart.options.indexAxis = 'y';
                } else {
                    chart.options.indexAxis = 'x';
                }
                chart.update();
            }
        }
    }

    /**
     * Update filters configuration
     */
    function updateFiltersConfig() {
        config.filters = [];
        document.querySelectorAll('.filter-item').forEach(function(item) {
            const column = item.querySelector('.filter-column').value;
            const operator = item.querySelector('.filter-operator').value;
            const value = item.querySelector('.filter-value').value;

            if (column) {
                config.filters.push({
                    column: column,
                    operator: operator,
                    value: value
                });
            }
        });
    }

    /**
     * Mark the form as dirty (unsaved changes)
     */
    function markDirty() {
        isDirty = true;
        const saveBtn = document.getElementById('btnSave');
        if (saveBtn && !saveBtn.textContent.includes('*')) {
            saveBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save *';
        }
    }

    /**
     * Escape HTML entities
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();
