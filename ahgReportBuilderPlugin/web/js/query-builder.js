/**
 * Query Builder UI for Report Builder
 * Visual join builder, SQL editor, and saved queries.
 */
(function() {
    'use strict';

    var QueryBuilderUI = {
        containerId: null,
        apiUrls: {},

        /**
         * Initialize the query builder UI.
         *
         * @param {string} containerId  The root container element ID
         * @param {object} apiUrls      API endpoints: { tables, columns, execute, save, load, delete }
         */
        init: function(containerId, apiUrls) {
            this.containerId = containerId;
            this.apiUrls = apiUrls || {};

            var container = document.getElementById(containerId);
            if (!container) {
                console.error('QueryBuilderUI: container not found:', containerId);
                return;
            }

            // Build tab navigation
            container.innerHTML =
                '<ul class="nav nav-tabs mb-3" role="tablist">' +
                    '<li class="nav-item" role="presentation">' +
                        '<button class="nav-link active" id="qb-visual-tab" data-bs-toggle="tab" data-bs-target="#qb-visual-panel" type="button" role="tab">Visual Builder</button>' +
                    '</li>' +
                    '<li class="nav-item" role="presentation">' +
                        '<button class="nav-link" id="qb-sql-tab" data-bs-toggle="tab" data-bs-target="#qb-sql-panel" type="button" role="tab">SQL Editor</button>' +
                    '</li>' +
                    '<li class="nav-item" role="presentation">' +
                        '<button class="nav-link" id="qb-saved-tab" data-bs-toggle="tab" data-bs-target="#qb-saved-panel" type="button" role="tab">Saved Queries</button>' +
                    '</li>' +
                '</ul>' +
                '<div class="tab-content">' +
                    '<div class="tab-pane fade show active" id="qb-visual-panel" role="tabpanel"></div>' +
                    '<div class="tab-pane fade" id="qb-sql-panel" role="tabpanel"></div>' +
                    '<div class="tab-pane fade" id="qb-saved-panel" role="tabpanel"></div>' +
                '</div>' +
                '<div class="mt-3" id="qb-results-area"></div>';

            this.renderVisualBuilder();
            this.renderSqlEditor();
            this.loadSavedQueries();
        },

        /**
         * Render the visual query builder panel.
         */
        renderVisualBuilder: function() {
            var self = this;
            var panel = document.getElementById('qb-visual-panel');
            if (!panel) return;

            panel.innerHTML =
                '<div class="card">' +
                    '<div class="card-body">' +
                        '<div class="row g-3">' +
                            '<div class="col-md-6">' +
                                '<label class="form-label fw-bold">Table</label>' +
                                '<select class="form-select" id="qb-table-select">' +
                                    '<option value="">-- Select table --</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="col-md-6">' +
                                '<label class="form-label fw-bold">Limit</label>' +
                                '<input type="number" class="form-control" id="qb-limit" value="100" min="1" max="10000">' +
                            '</div>' +
                        '</div>' +
                        '<div class="mt-3">' +
                            '<label class="form-label fw-bold">Columns</label>' +
                            '<div id="qb-columns-list" class="border rounded p-2" style="max-height:200px;overflow-y:auto;">' +
                                '<span class="text-muted small">Select a table first</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="mt-3">' +
                            '<label class="form-label fw-bold d-flex justify-content-between">' +
                                '<span>Filters</span>' +
                                '<button class="btn btn-sm btn-outline-primary" id="qb-add-filter"><i class="bi bi-plus me-1"></i>Add Filter</button>' +
                            '</label>' +
                            '<div id="qb-filters-container"></div>' +
                        '</div>' +
                        '<div class="row g-3 mt-2">' +
                            '<div class="col-md-6">' +
                                '<label class="form-label fw-bold">Group By</label>' +
                                '<select class="form-select" id="qb-group-by">' +
                                    '<option value="">-- None --</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="col-md-6">' +
                                '<label class="form-label fw-bold">Order By</label>' +
                                '<div class="input-group">' +
                                    '<select class="form-select" id="qb-order-by">' +
                                        '<option value="">-- None --</option>' +
                                    '</select>' +
                                    '<select class="form-select" id="qb-order-dir" style="max-width:100px;">' +
                                        '<option value="ASC">ASC</option>' +
                                        '<option value="DESC">DESC</option>' +
                                    '</select>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="mt-3 d-flex gap-2">' +
                            '<button class="btn btn-primary" id="qb-visual-run"><i class="bi bi-play me-1"></i>Run Query</button>' +
                            '<button class="btn btn-outline-secondary" id="qb-visual-generate-sql"><i class="bi bi-code me-1"></i>Generate SQL</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            // Load tables
            self._loadTables();

            // Bind table change -> load columns
            document.getElementById('qb-table-select').addEventListener('change', function() {
                if (this.value) {
                    self._loadColumns(this.value);
                } else {
                    document.getElementById('qb-columns-list').innerHTML = '<span class="text-muted small">Select a table first</span>';
                }
            });

            // Add filter
            document.getElementById('qb-add-filter').addEventListener('click', function() {
                self._addFilterRow();
            });

            // Run query
            document.getElementById('qb-visual-run').addEventListener('click', function() {
                var query = self._buildVisualQuery();
                if (query) {
                    self.executeQuery(query);
                }
            });

            // Generate SQL
            document.getElementById('qb-visual-generate-sql').addEventListener('click', function() {
                var query = self._buildVisualQuery();
                if (query) {
                    // Switch to SQL tab and populate
                    var sqlTab = document.getElementById('qb-sql-tab');
                    if (sqlTab) {
                        var tab = new bootstrap.Tab(sqlTab);
                        tab.show();
                    }
                    var sqlArea = document.getElementById('qb-sql-textarea');
                    if (sqlArea) {
                        sqlArea.value = query;
                    }
                }
            });
        },

        /**
         * Render the SQL editor panel.
         */
        renderSqlEditor: function() {
            var self = this;
            var panel = document.getElementById('qb-sql-panel');
            if (!panel) return;

            panel.innerHTML =
                '<div class="card">' +
                    '<div class="card-body">' +
                        '<div class="alert alert-warning py-2 small">' +
                            '<i class="bi bi-exclamation-triangle me-1"></i>' +
                            'SQL editor is available to administrators only. Only SELECT statements are permitted.' +
                        '</div>' +
                        '<div class="mb-3">' +
                            '<label class="form-label fw-bold">SQL Query</label>' +
                            '<textarea class="form-control font-monospace" id="qb-sql-textarea" rows="8" ' +
                                'placeholder="SELECT * FROM information_object LIMIT 10;" ' +
                                'style="font-size:0.85rem;tab-size:4;"></textarea>' +
                        '</div>' +
                        '<div class="d-flex gap-2">' +
                            '<button class="btn btn-primary" id="qb-sql-run"><i class="bi bi-play me-1"></i>Execute</button>' +
                            '<button class="btn btn-outline-secondary" id="qb-sql-save"><i class="bi bi-save me-1"></i>Save Query</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            // Run SQL
            document.getElementById('qb-sql-run').addEventListener('click', function() {
                var sql = document.getElementById('qb-sql-textarea').value.trim();
                if (sql) {
                    self.executeQuery(sql);
                }
            });

            // Save SQL query
            document.getElementById('qb-sql-save').addEventListener('click', function() {
                var sql = document.getElementById('qb-sql-textarea').value.trim();
                if (!sql) {
                    alert('Enter a query first.');
                    return;
                }
                var name = prompt('Enter a name for this saved query:');
                if (name) {
                    self.saveQuery(name, sql);
                }
            });
        },

        /**
         * Execute a query and display results.
         *
         * @param {string} query  The SQL query or visual query string
         */
        executeQuery: function(query) {
            var self = this;
            var resultsArea = document.getElementById('qb-results-area');
            if (!resultsArea) return;

            if (!self.apiUrls.execute) {
                resultsArea.innerHTML = '<div class="alert alert-danger">Query execution endpoint not configured.</div>';
                return;
            }

            resultsArea.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm me-1"></span>Executing query...</div>';

            fetch(self.apiUrls.execute, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (!result.success) {
                    resultsArea.innerHTML =
                        '<div class="alert alert-danger">' +
                            '<i class="bi bi-exclamation-triangle me-1"></i>' +
                            '<strong>Query Error:</strong> ' + self._escHtml(result.error || 'Unknown error') +
                        '</div>';
                    return;
                }

                var rows = result.data || result.results || [];
                if (rows.length === 0) {
                    resultsArea.innerHTML =
                        '<div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>Query returned no results.</div>';
                    return;
                }

                // Build results table
                var columns = Object.keys(rows[0]);
                var html =
                    '<div class="card">' +
                        '<div class="card-header py-2 d-flex justify-content-between align-items-center">' +
                            '<span><i class="bi bi-table me-1"></i>Results <span class="badge bg-secondary">' + rows.length + ' row' + (rows.length !== 1 ? 's' : '') + '</span></span>' +
                        '</div>' +
                        '<div class="table-responsive" style="max-height:500px;overflow-y:auto;">' +
                            '<table class="table table-sm table-hover table-striped mb-0">' +
                                '<thead class="table-light sticky-top">' +
                                    '<tr>';

                columns.forEach(function(col) {
                    html += '<th class="small text-nowrap">' + self._escHtml(col) + '</th>';
                });

                html +=         '</tr>' +
                                '</thead>' +
                                '<tbody>';

                rows.forEach(function(row) {
                    html += '<tr>';
                    columns.forEach(function(col) {
                        var val = row[col];
                        if (val === null || val === undefined) val = '';
                        var strVal = String(val);
                        if (strVal.length > 80) strVal = strVal.substring(0, 80) + '...';
                        html += '<td class="small">' + self._escHtml(strVal) + '</td>';
                    });
                    html += '</tr>';
                });

                html +=         '</tbody>' +
                            '</table>' +
                        '</div>' +
                    '</div>';

                resultsArea.innerHTML = html;
            })
            .catch(function(err) {
                resultsArea.innerHTML =
                    '<div class="alert alert-danger">' +
                        '<i class="bi bi-exclamation-triangle me-1"></i>' +
                        '<strong>Error:</strong> ' + self._escHtml(err.message) +
                    '</div>';
                console.error('QueryBuilderUI: execute error', err);
            });
        },

        /**
         * Save a named query via API.
         *
         * @param {string} name  The query name
         * @param {string} sql   The SQL query text (optional, reads from textarea if omitted)
         */
        saveQuery: function(name, sql) {
            var self = this;
            if (!sql) {
                var textarea = document.getElementById('qb-sql-textarea');
                sql = textarea ? textarea.value.trim() : '';
            }
            if (!sql) {
                alert('No query to save.');
                return;
            }

            if (!self.apiUrls.save) {
                console.error('QueryBuilderUI: save URL not configured');
                return;
            }

            fetch(self.apiUrls.save, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name, query: sql })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('Query saved successfully.');
                    self.loadSavedQueries();
                } else {
                    alert('Error saving query: ' + (result.error || 'Unknown error'));
                }
            })
            .catch(function(err) {
                console.error('QueryBuilderUI: save error', err);
            });
        },

        /**
         * Load and render saved queries into the saved queries panel.
         */
        loadSavedQueries: function() {
            var self = this;
            var panel = document.getElementById('qb-saved-panel');
            if (!panel) return;

            if (!self.apiUrls.load) {
                panel.innerHTML =
                    '<div class="text-center text-muted py-4 small">' +
                        '<i class="bi bi-bookmark fs-3 d-block mb-2"></i>Saved queries not configured' +
                    '</div>';
                return;
            }

            panel.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></div>';

            fetch(self.apiUrls.load)
            .then(function(response) { return response.json(); })
            .then(function(result) {
                var queries = result.data || result.queries || [];
                if (queries.length === 0) {
                    panel.innerHTML =
                        '<div class="text-center text-muted py-4 small">' +
                            '<i class="bi bi-bookmark fs-3 d-block mb-2"></i>No saved queries yet' +
                        '</div>';
                    return;
                }

                var html = '<div class="list-group">';
                queries.forEach(function(q) {
                    html +=
                        '<div class="list-group-item">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                                '<div>' +
                                    '<strong class="small">' + self._escHtml(q.name) + '</strong>' +
                                    '<br><code class="small text-muted">' + self._escHtml((q.query || '').substring(0, 80)) + (q.query && q.query.length > 80 ? '...' : '') + '</code>' +
                                '</div>' +
                                '<div class="btn-group btn-group-sm">' +
                                    '<button class="btn btn-outline-primary qb-load-query" data-query="' + self._escAttr(q.query || '') + '" title="Load"><i class="bi bi-box-arrow-in-down"></i></button>' +
                                    '<button class="btn btn-outline-danger qb-delete-query" data-query-id="' + q.id + '" title="Delete"><i class="bi bi-trash"></i></button>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                });
                html += '</div>';

                panel.innerHTML = html;

                // Bind load buttons
                panel.querySelectorAll('.qb-load-query').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var query = this.dataset.query;
                        var sqlArea = document.getElementById('qb-sql-textarea');
                        if (sqlArea) sqlArea.value = query;
                        // Switch to SQL tab
                        var sqlTab = document.getElementById('qb-sql-tab');
                        if (sqlTab) {
                            var tab = new bootstrap.Tab(sqlTab);
                            tab.show();
                        }
                    });
                });

                // Bind delete buttons
                panel.querySelectorAll('.qb-delete-query').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var queryId = this.dataset.queryId;
                        if (confirm('Delete this saved query?')) {
                            self._deleteQuery(queryId);
                        }
                    });
                });
            })
            .catch(function(err) {
                panel.innerHTML = '<div class="text-center text-danger py-3 small">Failed to load saved queries</div>';
                console.error('QueryBuilderUI: load saved queries error', err);
            });
        },

        /**
         * Load available tables from the API.
         * @private
         */
        _loadTables: function() {
            var self = this;
            var tableSelect = document.getElementById('qb-table-select');
            if (!tableSelect || !self.apiUrls.tables) return;

            fetch(self.apiUrls.tables)
            .then(function(response) { return response.json(); })
            .then(function(result) {
                var tables = result.data || result.tables || [];
                tables.forEach(function(table) {
                    var name = typeof table === 'object' ? table.name : table;
                    var option = document.createElement('option');
                    option.value = name;
                    option.textContent = name;
                    tableSelect.appendChild(option);
                });
            })
            .catch(function(err) {
                console.error('QueryBuilderUI: load tables error', err);
            });
        },

        /**
         * Load columns for a selected table.
         * @private
         * @param {string} tableName
         */
        _loadColumns: function(tableName) {
            var self = this;
            var columnsContainer = document.getElementById('qb-columns-list');
            var groupBySelect = document.getElementById('qb-group-by');
            var orderBySelect = document.getElementById('qb-order-by');
            if (!columnsContainer) return;

            if (!self.apiUrls.columns) {
                columnsContainer.innerHTML = '<span class="text-muted small">Columns endpoint not configured</span>';
                return;
            }

            columnsContainer.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch(self.apiUrls.columns + '?table=' + encodeURIComponent(tableName))
            .then(function(response) { return response.json(); })
            .then(function(result) {
                var columns = result.data || result.columns || [];
                if (columns.length === 0) {
                    columnsContainer.innerHTML = '<span class="text-muted small">No columns found</span>';
                    return;
                }

                var html = '<div class="form-check mb-1">' +
                    '<input class="form-check-input" type="checkbox" id="qb-col-select-all" checked>' +
                    '<label class="form-check-label small fw-bold" for="qb-col-select-all">Select All</label>' +
                    '</div>';

                // Clear and rebuild group by / order by selects
                if (groupBySelect) {
                    groupBySelect.innerHTML = '<option value="">-- None --</option>';
                }
                if (orderBySelect) {
                    orderBySelect.innerHTML = '<option value="">-- None --</option>';
                }

                columns.forEach(function(col) {
                    var colName = typeof col === 'object' ? col.name : col;
                    html +=
                        '<div class="form-check mb-1">' +
                            '<input class="form-check-input qb-col-check" type="checkbox" id="qb-col-' + colName + '" value="' + colName + '" checked>' +
                            '<label class="form-check-label small" for="qb-col-' + colName + '">' + colName + '</label>' +
                        '</div>';

                    if (groupBySelect) {
                        var opt = document.createElement('option');
                        opt.value = colName;
                        opt.textContent = colName;
                        groupBySelect.appendChild(opt);
                    }
                    if (orderBySelect) {
                        var opt2 = document.createElement('option');
                        opt2.value = colName;
                        opt2.textContent = colName;
                        orderBySelect.appendChild(opt2);
                    }
                });

                columnsContainer.innerHTML = html;

                // Select all toggle
                document.getElementById('qb-col-select-all').addEventListener('change', function() {
                    var checked = this.checked;
                    columnsContainer.querySelectorAll('.qb-col-check').forEach(function(cb) {
                        cb.checked = checked;
                    });
                });
            })
            .catch(function(err) {
                columnsContainer.innerHTML = '<span class="text-danger small">Error loading columns</span>';
                console.error('QueryBuilderUI: load columns error', err);
            });
        },

        /**
         * Add a filter row to the visual builder.
         * @private
         */
        _addFilterRow: function() {
            var container = document.getElementById('qb-filters-container');
            if (!container) return;

            var row = document.createElement('div');
            row.className = 'input-group input-group-sm mb-2 qb-filter-row';
            row.innerHTML =
                '<select class="form-select qb-filter-column">' +
                    '<option value="">Column...</option>' +
                '</select>' +
                '<select class="form-select qb-filter-operator" style="max-width:120px;">' +
                    '<option value="=">=</option>' +
                    '<option value="!=">!=</option>' +
                    '<option value="LIKE">LIKE</option>' +
                    '<option value=">">></option>' +
                    '<option value="<"><</option>' +
                    '<option value=">=">>=</option>' +
                    '<option value="<="><=</option>' +
                    '<option value="IS NULL">IS NULL</option>' +
                    '<option value="IS NOT NULL">IS NOT NULL</option>' +
                '</select>' +
                '<input type="text" class="form-control qb-filter-value" placeholder="Value...">' +
                '<button class="btn btn-outline-danger qb-remove-filter"><i class="bi bi-x"></i></button>';

            // Copy columns from the columns list
            var colSelect = row.querySelector('.qb-filter-column');
            document.querySelectorAll('.qb-col-check').forEach(function(cb) {
                var opt = document.createElement('option');
                opt.value = cb.value;
                opt.textContent = cb.value;
                colSelect.appendChild(opt);
            });

            row.querySelector('.qb-remove-filter').addEventListener('click', function() {
                row.remove();
            });

            container.appendChild(row);
        },

        /**
         * Build a SQL query string from the visual builder form.
         * @private
         * @returns {string|null}
         */
        _buildVisualQuery: function() {
            var table = document.getElementById('qb-table-select').value;
            if (!table) {
                alert('Please select a table.');
                return null;
            }

            // Selected columns
            var columns = [];
            document.querySelectorAll('.qb-col-check:checked').forEach(function(cb) {
                columns.push(cb.value);
            });
            if (columns.length === 0) {
                alert('Please select at least one column.');
                return null;
            }

            var sql = 'SELECT ' + columns.join(', ') + ' FROM ' + table;

            // Filters
            var filters = [];
            document.querySelectorAll('.qb-filter-row').forEach(function(row) {
                var col = row.querySelector('.qb-filter-column').value;
                var op = row.querySelector('.qb-filter-operator').value;
                var val = row.querySelector('.qb-filter-value').value;
                if (col && op) {
                    if (op === 'IS NULL' || op === 'IS NOT NULL') {
                        filters.push(col + ' ' + op);
                    } else if (op === 'LIKE') {
                        filters.push(col + " LIKE '%" + val.replace(/'/g, "''") + "%'");
                    } else {
                        filters.push(col + ' ' + op + " '" + val.replace(/'/g, "''") + "'");
                    }
                }
            });
            if (filters.length > 0) {
                sql += ' WHERE ' + filters.join(' AND ');
            }

            // Group by
            var groupBy = document.getElementById('qb-group-by').value;
            if (groupBy) {
                sql += ' GROUP BY ' + groupBy;
            }

            // Order by
            var orderBy = document.getElementById('qb-order-by').value;
            var orderDir = document.getElementById('qb-order-dir').value;
            if (orderBy) {
                sql += ' ORDER BY ' + orderBy + ' ' + (orderDir || 'ASC');
            }

            // Limit
            var limit = parseInt(document.getElementById('qb-limit').value, 10);
            if (limit > 0) {
                sql += ' LIMIT ' + limit;
            }

            return sql;
        },

        /**
         * Delete a saved query.
         * @private
         * @param {number|string} queryId
         */
        _deleteQuery: function(queryId) {
            var self = this;
            if (!self.apiUrls.delete) {
                console.error('QueryBuilderUI: delete URL not configured');
                return;
            }

            fetch(self.apiUrls.delete, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: queryId })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    self.loadSavedQueries();
                } else {
                    alert('Error deleting query: ' + (result.error || 'Unknown error'));
                }
            })
            .catch(function(err) {
                console.error('QueryBuilderUI: delete error', err);
            });
        },

        /**
         * Escape HTML entities.
         * @param {string} str
         * @returns {string}
         */
        _escHtml: function(str) {
            if (str === null || str === undefined) return '';
            var div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        },

        /**
         * Escape for HTML attribute.
         * @param {string} str
         * @returns {string}
         */
        _escAttr: function(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
    };

    window.QueryBuilderUI = QueryBuilderUI;
})();
