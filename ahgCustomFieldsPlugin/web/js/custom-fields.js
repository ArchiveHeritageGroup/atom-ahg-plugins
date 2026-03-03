/**
 * ahgCustomFieldsPlugin — Client-side JS
 * Handles: repeatable field add/remove, drag-drop reorder, URL field sync
 */
(function () {
    'use strict';

    // ----------------------------------------------------------------
    // Repeatable fields: add / remove rows
    // ----------------------------------------------------------------

    document.addEventListener('click', function (e) {
        // Add row
        var addBtn = e.target.closest('.cf-add-row');
        if (addBtn) {
            e.preventDefault();
            var fieldKey = addBtn.dataset.fieldKey;
            var container = addBtn.previousElementSibling; // .cf-repeatable-items
            if (!container) return;

            var row = document.createElement('div');
            row.className = 'input-group mb-1 cf-repeatable-row';
            row.innerHTML =
                '<input type="text" class="form-control" name="cf[' + fieldKey + '][]" value="">' +
                '<button type="button" class="btn btn-outline-danger cf-remove-row" title="Remove">' +
                '<i class="bi bi-dash-circle"></i></button>';
            container.appendChild(row);
            row.querySelector('input').focus();
            return;
        }

        // Remove row
        var removeBtn = e.target.closest('.cf-remove-row');
        if (removeBtn) {
            e.preventDefault();
            var rowEl = removeBtn.closest('.cf-repeatable-row');
            var items = rowEl.parentElement;

            // Keep at least one row
            if (items.querySelectorAll('.cf-repeatable-row').length > 1) {
                rowEl.remove();
            } else {
                rowEl.querySelector('input').value = '';
            }
            return;
        }
    });

    // ----------------------------------------------------------------
    // URL field: sync "Open" link with input value
    // ----------------------------------------------------------------

    document.addEventListener('input', function (e) {
        if (e.target.type === 'url' && e.target.closest('.input-group')) {
            var link = e.target.parentElement.querySelector('.cf-url-open');
            if (link) {
                link.href = e.target.value || '#';
            }
        }
    });

    // ----------------------------------------------------------------
    // Admin: drag-drop reorder for sortable tables
    // ----------------------------------------------------------------

    var dragSrcRow = null;

    window.cfInitSortable = function (tbody, reorderUrl) {
        if (!tbody) return;

        var rows = tbody.querySelectorAll('tr[data-id]');
        rows.forEach(function (row) {
            var handle = row.querySelector('.cf-drag-handle');
            if (!handle) return;

            row.draggable = true;

            row.addEventListener('dragstart', function (e) {
                dragSrcRow = this;
                this.classList.add('cf-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', this.dataset.id);
            });

            row.addEventListener('dragend', function () {
                this.classList.remove('cf-dragging');
                tbody.querySelectorAll('tr').forEach(function (r) {
                    r.classList.remove('cf-drag-over');
                });
            });

            row.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('cf-drag-over');
            });

            row.addEventListener('dragleave', function () {
                this.classList.remove('cf-drag-over');
            });

            row.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('cf-drag-over');

                if (dragSrcRow === this) return;

                // Reorder in DOM
                var allRows = Array.from(tbody.querySelectorAll('tr[data-id]'));
                var srcIdx = allRows.indexOf(dragSrcRow);
                var tgtIdx = allRows.indexOf(this);

                if (srcIdx < tgtIdx) {
                    tbody.insertBefore(dragSrcRow, this.nextSibling);
                } else {
                    tbody.insertBefore(dragSrcRow, this);
                }

                // Save new order
                var orderedIds = Array.from(tbody.querySelectorAll('tr[data-id]'))
                    .map(function (r) { return r.dataset.id; });

                var form = new FormData();
                orderedIds.forEach(function (id) {
                    form.append('ids[]', id);
                });

                fetch(reorderUrl, { method: 'POST', body: form })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            console.warn('Reorder failed:', data.error);
                        }
                    });
            });
        });
    };

    // Auto-init sortable on page load (for admin index)
    document.addEventListener('DOMContentLoaded', function () {
        // Sortable tables are initialized from the index template's inline script
        // This just ensures the cfInitSortable function is available
    });

})();
