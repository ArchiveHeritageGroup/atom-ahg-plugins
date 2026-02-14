/**
 * Chart Builder for Report Builder
 * Enhanced chart configuration UI for inline chart sections.
 * Requires Chart.js to be loaded via CDN.
 */
(function() {
    'use strict';

    var COLOR_SCHEMES = {
        default: [
            'rgba(13, 110, 253, 0.7)',
            'rgba(25, 135, 84, 0.7)',
            'rgba(220, 53, 69, 0.7)',
            'rgba(255, 193, 7, 0.7)',
            'rgba(13, 202, 240, 0.7)',
            'rgba(111, 66, 193, 0.7)',
            'rgba(253, 126, 20, 0.7)',
            'rgba(108, 117, 125, 0.7)'
        ],
        pastel: [
            'rgba(174, 214, 241, 0.8)',
            'rgba(169, 223, 191, 0.8)',
            'rgba(245, 183, 177, 0.8)',
            'rgba(249, 231, 159, 0.8)',
            'rgba(210, 180, 222, 0.8)',
            'rgba(245, 203, 167, 0.8)',
            'rgba(174, 214, 241, 0.8)',
            'rgba(213, 219, 219, 0.8)'
        ],
        vibrant: [
            'rgba(231, 76, 60, 0.8)',
            'rgba(46, 204, 113, 0.8)',
            'rgba(52, 152, 219, 0.8)',
            'rgba(241, 196, 15, 0.8)',
            'rgba(155, 89, 182, 0.8)',
            'rgba(230, 126, 34, 0.8)',
            'rgba(26, 188, 156, 0.8)',
            'rgba(149, 165, 166, 0.8)'
        ],
        monochrome: [
            'rgba(44, 62, 80, 0.9)',
            'rgba(52, 73, 94, 0.8)',
            'rgba(93, 109, 126, 0.7)',
            'rgba(127, 140, 141, 0.7)',
            'rgba(149, 165, 166, 0.6)',
            'rgba(189, 195, 199, 0.6)',
            'rgba(214, 219, 223, 0.5)',
            'rgba(236, 240, 241, 0.5)'
        ]
    };

    var ChartBuilder = {
        _instances: {},
        _configs: {},

        /**
         * Initialize ChartBuilder. Call once on page load.
         */
        init: function() {
            // Nothing required at init time; per-chart setup is via createChartSection.
        },

        /**
         * Create a chart section in a container.
         *
         * @param {string} containerId  The container element ID
         * @param {object} config       Initial configuration:
         *   { chartType, groupBy, aggregate, colors, showLegend, showLabels, columns, data }
         */
        createChartSection: function(containerId, config) {
            var self = this;
            var container = document.getElementById(containerId);
            if (!container) {
                console.error('ChartBuilder: container not found:', containerId);
                return;
            }

            config = config || {};
            var cfg = {
                chartType: config.chartType || 'bar',
                groupBy: config.groupBy || '',
                aggregate: config.aggregate || 'count',
                colorScheme: config.colorScheme || 'default',
                showLegend: config.showLegend !== undefined ? config.showLegend : true,
                showLabels: config.showLabels !== undefined ? config.showLabels : true,
                columns: config.columns || [],
                data: config.data || null
            };
            self._configs[containerId] = cfg;

            // Build the config panel + canvas
            container.innerHTML =
                '<div class="row">' +
                    '<div class="col-md-4">' +
                        '<div class="card mb-3">' +
                            '<div class="card-header py-2"><i class="bi bi-gear me-1"></i>Chart Settings</div>' +
                            '<div class="card-body p-2">' +
                                '<div class="mb-2">' +
                                    '<label class="form-label small mb-1">Chart Type</label>' +
                                    '<select class="form-select form-select-sm" id="' + containerId + '-chartType">' +
                                        '<option value="bar"' + (cfg.chartType === 'bar' ? ' selected' : '') + '>Bar</option>' +
                                        '<option value="line"' + (cfg.chartType === 'line' ? ' selected' : '') + '>Line</option>' +
                                        '<option value="pie"' + (cfg.chartType === 'pie' ? ' selected' : '') + '>Pie</option>' +
                                        '<option value="doughnut"' + (cfg.chartType === 'doughnut' ? ' selected' : '') + '>Doughnut</option>' +
                                        '<option value="horizontalBar"' + (cfg.chartType === 'horizontalBar' ? ' selected' : '') + '>Horizontal Bar</option>' +
                                        '<option value="area"' + (cfg.chartType === 'area' ? ' selected' : '') + '>Area</option>' +
                                    '</select>' +
                                '</div>' +
                                '<div class="mb-2">' +
                                    '<label class="form-label small mb-1">Group By</label>' +
                                    '<select class="form-select form-select-sm" id="' + containerId + '-groupBy">' +
                                        '<option value="">-- Select column --</option>' +
                                        self._buildColumnOptions(cfg.columns, cfg.groupBy) +
                                    '</select>' +
                                '</div>' +
                                '<div class="mb-2">' +
                                    '<label class="form-label small mb-1">Aggregate</label>' +
                                    '<select class="form-select form-select-sm" id="' + containerId + '-aggregate">' +
                                        '<option value="count"' + (cfg.aggregate === 'count' ? ' selected' : '') + '>Count</option>' +
                                        '<option value="sum"' + (cfg.aggregate === 'sum' ? ' selected' : '') + '>Sum</option>' +
                                        '<option value="avg"' + (cfg.aggregate === 'avg' ? ' selected' : '') + '>Average</option>' +
                                    '</select>' +
                                '</div>' +
                                '<div class="mb-2">' +
                                    '<label class="form-label small mb-1">Color Scheme</label>' +
                                    '<select class="form-select form-select-sm" id="' + containerId + '-colorScheme">' +
                                        '<option value="default"' + (cfg.colorScheme === 'default' ? ' selected' : '') + '>Default</option>' +
                                        '<option value="pastel"' + (cfg.colorScheme === 'pastel' ? ' selected' : '') + '>Pastel</option>' +
                                        '<option value="vibrant"' + (cfg.colorScheme === 'vibrant' ? ' selected' : '') + '>Vibrant</option>' +
                                        '<option value="monochrome"' + (cfg.colorScheme === 'monochrome' ? ' selected' : '') + '>Monochrome</option>' +
                                    '</select>' +
                                '</div>' +
                                '<div class="form-check mb-1">' +
                                    '<input class="form-check-input" type="checkbox" id="' + containerId + '-showLegend"' + (cfg.showLegend ? ' checked' : '') + '>' +
                                    '<label class="form-check-label small" for="' + containerId + '-showLegend">Show Legend</label>' +
                                '</div>' +
                                '<div class="form-check">' +
                                    '<input class="form-check-input" type="checkbox" id="' + containerId + '-showLabels"' + (cfg.showLabels ? ' checked' : '') + '>' +
                                    '<label class="form-check-label small" for="' + containerId + '-showLabels">Show Labels</label>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-8">' +
                        '<div class="card">' +
                            '<div class="card-body">' +
                                '<canvas id="' + containerId + '-canvas" height="280"></canvas>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            // Bind change listeners
            var fields = ['chartType', 'groupBy', 'aggregate', 'colorScheme', 'showLegend', 'showLabels'];
            fields.forEach(function(field) {
                var el = document.getElementById(containerId + '-' + field);
                if (el) {
                    el.addEventListener('change', function() {
                        self._readConfig(containerId);
                        self._renderChart(containerId);
                    });
                }
            });

            // Render initial chart
            if (typeof Chart !== 'undefined') {
                self._renderChart(containerId);
            } else {
                console.warn('ChartBuilder: Chart.js not loaded, retrying...');
                setTimeout(function() { self._renderChart(containerId); }, 500);
            }
        },

        /**
         * Update chart data for a container.
         *
         * @param {string} containerId  The container element ID
         * @param {object} data  Chart data: { labels: [], values: [] }
         */
        updateChart: function(containerId, data) {
            if (!this._configs[containerId]) {
                console.error('ChartBuilder: no config for', containerId);
                return;
            }
            this._configs[containerId].data = data;
            this._renderChart(containerId);
        },

        /**
         * Get the current configuration for a chart container.
         *
         * @param {string} containerId  The container element ID
         * @returns {object|null} The chart configuration
         */
        getConfig: function(containerId) {
            this._readConfig(containerId);
            return this._configs[containerId] || null;
        },

        /**
         * Read current config values from the DOM form elements.
         * @param {string} containerId
         */
        _readConfig: function(containerId) {
            var cfg = this._configs[containerId];
            if (!cfg) return;

            var getVal = function(suffix) {
                var el = document.getElementById(containerId + '-' + suffix);
                return el ? el.value : null;
            };
            var getChecked = function(suffix) {
                var el = document.getElementById(containerId + '-' + suffix);
                return el ? el.checked : false;
            };

            cfg.chartType = getVal('chartType') || cfg.chartType;
            cfg.groupBy = getVal('groupBy') || cfg.groupBy;
            cfg.aggregate = getVal('aggregate') || cfg.aggregate;
            cfg.colorScheme = getVal('colorScheme') || cfg.colorScheme;
            cfg.showLegend = getChecked('showLegend');
            cfg.showLabels = getChecked('showLabels');
        },

        /**
         * Render or re-render the chart in a given container.
         * @param {string} containerId
         */
        _renderChart: function(containerId) {
            if (typeof Chart === 'undefined') {
                console.warn('ChartBuilder: Chart.js not available');
                return;
            }

            var cfg = this._configs[containerId];
            if (!cfg) return;

            var canvas = document.getElementById(containerId + '-canvas');
            if (!canvas) return;

            // Destroy existing instance
            if (this._instances[containerId]) {
                this._instances[containerId].destroy();
                this._instances[containerId] = null;
            }

            var labels = (cfg.data && cfg.data.labels) ? cfg.data.labels : ['No data'];
            var values = (cfg.data && cfg.data.values) ? cfg.data.values : [0];
            var colors = COLOR_SCHEMES[cfg.colorScheme] || COLOR_SCHEMES.default;

            // Determine Chart.js type
            var chartJsType = cfg.chartType;
            var indexAxis = 'x';
            var fill = false;
            if (cfg.chartType === 'horizontalBar') {
                chartJsType = 'bar';
                indexAxis = 'y';
            } else if (cfg.chartType === 'area') {
                chartJsType = 'line';
                fill = true;
            }

            var isPieType = (chartJsType === 'pie' || chartJsType === 'doughnut');

            this._instances[containerId] = new Chart(canvas, {
                type: chartJsType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: cfg.aggregate === 'count' ? 'Count' : (cfg.aggregate === 'sum' ? 'Sum' : 'Average'),
                        data: values,
                        backgroundColor: colors,
                        borderColor: colors.map(function(c) { return c.replace(/[\d.]+\)$/, '1)'); }),
                        borderWidth: 1,
                        fill: fill
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: indexAxis,
                    plugins: {
                        legend: {
                            display: cfg.showLegend && isPieType
                        },
                        datalabels: cfg.showLabels ? { display: true } : { display: false },
                        title: {
                            display: true,
                            text: cfg.groupBy ? ('Grouped by: ' + cfg.groupBy + ' (' + cfg.aggregate + ')') : 'Select a Group By column'
                        }
                    },
                    scales: isPieType ? {} : {
                        y: { beginAtZero: true },
                        x: {}
                    }
                }
            });
        },

        /**
         * Build <option> elements for column selects.
         * @param {Array} columns
         * @param {string} selected
         * @returns {string}
         */
        _buildColumnOptions: function(columns, selected) {
            var html = '';
            if (!columns || columns.length === 0) return html;
            columns.forEach(function(col) {
                var label = typeof col === 'object' ? (col.label || col.key) : col;
                var value = typeof col === 'object' ? col.key : col;
                html += '<option value="' + value + '"' + (value === selected ? ' selected' : '') + '>' + label + '</option>';
            });
            return html;
        }
    };

    window.ChartBuilder = ChartBuilder;
})();
