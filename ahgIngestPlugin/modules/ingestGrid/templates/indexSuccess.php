<?php
$parent = $sf_data->getRaw('parent');
$children = $sf_data->getRaw('children') ?? [];
$levels = $sf_data->getRaw('levels') ?? [];
$columns = $sf_data->getRaw('columns') ?? [];
$maxRows = (int) $sf_data->getRaw('maxRows');
$nonce = sfConfig::get('csp_nonce', '');
$nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
$labels = [];
foreach ($columns as $field => $label) {
    $labels[$field] = __($label);
}
?>

<h1><?php echo __('Grid entry') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Ingestion Manager'), 'url' => url_for(['module' => 'ingest', 'action' => 'index'])],
        ['title' => __('Grid entry')],
    ],
]) ?>

<style <?php echo $nonceAttr ?>>
#grid-table { table-layout: fixed; min-width: 1100px; }
#grid-table td { padding: 0; vertical-align: top; }
#grid-table td.grid-rownum { padding: .35rem .4rem; text-align: right; color: #6c757d; width: 3.2rem; font-size: .85rem; }
#grid-table td.grid-del { width: 2.6rem; text-align: center; }
#grid-table .grid-cell { border: 0; border-radius: 0; width: 100%; padding: .3rem .4rem; font-size: .9rem; background: transparent; }
#grid-table .grid-cell:focus { outline: 2px solid #0d6efd; outline-offset: -2px; background: #fff; box-shadow: none; }
#grid-table textarea.grid-cell { resize: vertical; min-height: 2rem; }
#grid-table .grid-cell.is-invalid { background: #f8d7da; }
#grid-table th { font-size: .85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#grid-table col.c-identifier { width: 9rem; }
#grid-table col.c-title { width: 18rem; }
#grid-table col.c-levelOfDescription { width: 9rem; }
#grid-table col.c-creationDates { width: 9rem; }
#grid-table col.c-creationDatesStart, #grid-table col.c-creationDatesEnd { width: 7.5rem; }
#grid-table col.c-extentAndMedium { width: 10rem; }
#grid-table col.c-scopeAndContent { width: 22rem; }
#grid-scroll { overflow-x: auto; }
#existing-table td { font-size: .85rem; }
#parent-results { position: absolute; z-index: 20; width: 100%; max-height: 18rem; overflow-y: auto; }
</style>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i><?php echo __('Parent description') ?></h5>
    </div>
    <div class="card-body">
        <?php if ($parent): ?>
            <p class="mb-2">
                <?php echo __('New descriptions will be added below:') ?>
                <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $parent->slug]) ?>"><strong><?php echo esc_entities($parent->title ?: $parent->slug) ?></strong></a>
                <?php if ($parent->identifier): ?><span class="text-muted">(<?php echo esc_entities($parent->identifier) ?>)</span><?php endif ?>
            </p>
        <?php else: ?>
            <p class="mb-2"><?php echo __('Choose the description the new records belong under. To start a new batch, create the parent (for example a new series) first, then choose it here.') ?></p>
        <?php endif ?>
        <div class="position-relative" data-ahg-style="max-width: 36rem;">
            <label for="parent-search" class="form-label visually-hidden"><?php echo __('Find a parent description') ?></label>
            <input type="search" class="form-control" id="parent-search" autocomplete="off"
                   placeholder="<?php echo __('Type at least two letters of a title to find a parent...') ?>">
            <div id="parent-results" class="list-group shadow-sm"></div>
        </div>
    </div>
</div>

<?php if ($parent): ?>

<?php if (!empty($children)): ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i><?php echo __('Existing descriptions under this parent (%1%)', ['%1%' => count($children)]) ?></h5>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#existing-wrap" aria-expanded="false" aria-controls="existing-wrap"><?php echo __('Show / hide') ?></button>
    </div>
    <div class="collapse" id="existing-wrap">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0" id="existing-table">
                <thead>
                    <tr>
                        <th><?php echo $labels['identifier'] ?></th>
                        <th><?php echo $labels['title'] ?></th>
                        <th><?php echo $labels['levelOfDescription'] ?></th>
                        <th><?php echo __('Dates') ?></th>
                        <th><?php echo $labels['extentAndMedium'] ?></th>
                        <th><?php echo $labels['scopeAndContent'] ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($children as $c): ?>
                    <?php $d = explode('|', (string) $c->dates) + ['', '', '']; ?>
                    <tr>
                        <td><?php echo esc_entities((string) $c->identifier) ?></td>
                        <td>
                            <?php if ($c->slug): ?>
                                <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $c->slug]) ?>"><?php echo esc_entities((string) ($c->title ?: $c->slug)) ?></a>
                            <?php else: ?>
                                <?php echo esc_entities((string) $c->title) ?>
                            <?php endif ?>
                        </td>
                        <td><?php echo esc_entities((string) $c->level) ?></td>
                        <td><?php echo esc_entities($d[0] ?: trim($d[1] . ' - ' . $d[2], ' -')) ?></td>
                        <td><?php echo esc_entities((string) $c->extent) ?></td>
                        <td><?php echo esc_entities(mb_strimwidth(strip_tags((string) $c->scope), 0, 160, '...')) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif ?>

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0"><i class="fas fa-table me-2"></i><?php echo __('New descriptions') ?></h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-row"><i class="fas fa-plus me-1"></i><?php echo __('Add row') ?></button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-10"><i class="fas fa-plus me-1"></i><?php echo __('Add 10 rows') ?></button>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-2">
            <?php echo __('Type into the cells, or copy a block of cells from Excel or another spreadsheet and paste it into the grid: it fills from the cell you are in and adds rows as needed. If the first copied row holds column headings that match the grid, the columns are matched by heading. Dates as YYYY, YYYY-MM or YYYY-MM-DD. Empty rows are ignored. New descriptions are saved as drafts.') ?>
        </p>
        <div id="grid-alert" class="alert d-none" role="alert"></div>
        <div id="grid-scroll" class="border rounded">
            <table class="table table-bordered mb-0" id="grid-table">
                <colgroup>
                    <col>
                    <?php foreach ($columns as $field => $label): ?><col class="c-<?php echo $field ?>"><?php endforeach ?>
                    <col>
                </colgroup>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <?php foreach ($columns as $field => $label): ?>
                            <th title="<?php echo esc_entities($labels[$field]) ?>"><?php echo esc_entities($labels[$field]) ?><?php echo 'title' === $field ? ' <span class="text-danger">*</span>' : '' ?></th>
                        <?php endforeach ?>
                        <th><span class="visually-hidden"><?php echo __('Remove') ?></span></th>
                    </tr>
                </thead>
                <tbody id="grid-body"></tbody>
            </table>
        </div>
        <datalist id="grid-levels">
            <?php foreach ($levels as $l): ?><option value="<?php echo esc_entities($l) ?>"></option><?php endforeach ?>
        </datalist>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'index']) ?>" class="btn btn-outline-secondary"><?php echo __('Back to Ingestion Manager') ?></a>
        <div>
            <span class="text-muted small me-3" id="grid-count"></span>
            <button type="button" class="btn btn-primary" id="btn-save"><i class="fas fa-save me-1"></i><?php echo __('Validate & save') ?></button>
        </div>
    </div>
</div>

<?php endif ?>

<script <?php echo $nonceAttr ?>>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ---- Parent search (reuses the ingest wizard's parent lookup) ----
    var searchUrl = <?php echo json_encode(url_for(['module' => 'ingest', 'action' => 'searchParent'])) ?>;
    var gridUrl = <?php echo json_encode(url_for(['module' => 'ingestGrid', 'action' => 'index'])) ?>;
    var search = document.getElementById('parent-search');
    var results = document.getElementById('parent-results');
    var timer = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'})[c];
        });
    }

    search.addEventListener('input', function () {
        clearTimeout(timer);
        var q = search.value.trim();
        if (q.length < 2) { results.innerHTML = ''; return; }
        timer = setTimeout(function () {
            fetch(searchUrl + (searchUrl.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(q), {
                credentials: 'same-origin', headers: {'Accept': 'application/json'}
            }).then(function (r) { return r.json(); }).then(function (list) {
                results.innerHTML = '';
                (list || []).forEach(function (it) {
                    var a = document.createElement('a');
                    a.href = gridUrl + (gridUrl.indexOf('?') === -1 ? '?' : '&') + 'parent=' + encodeURIComponent(it.id);
                    a.className = 'list-group-item list-group-item-action';
                    a.innerHTML = esc(it.title || it.slug) + (it.identifier ? ' <small class="text-muted">(' + esc(it.identifier) + ')</small>' : '');
                    results.appendChild(a);
                });
                if (!results.children.length) {
                    results.innerHTML = '<div class="list-group-item text-muted">' + esc(<?php echo json_encode(__('No matches')) ?>) + '</div>';
                }
            }).catch(function () { results.innerHTML = ''; });
        }, 250);
    });

    var body = document.getElementById('grid-body');
    if (!body) { return; }

    // ---- Grid ----
    var parentId = <?php echo (int) ($parent->id ?? 0) ?>;
    var saveUrl = <?php echo json_encode(url_for(['module' => 'ingestGrid', 'action' => 'save'])) ?>;
    var fields = <?php echo json_encode(array_keys($columns)) ?>;
    var labels = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
    var maxRows = <?php echo $maxRows ?>;
    var msg = {
        removeRow: <?php echo json_encode(__('Remove row')) ?>,
        tooMany: <?php echo json_encode(__('The grid holds at most %1% rows.', ['%1%' => $maxRows])) ?>,
        saving: <?php echo json_encode(__('Validating and saving...')) ?>,
        failed: <?php echo json_encode(__('Saving failed. Nothing was written.')) ?>,
        empty: <?php echo json_encode(__('The grid is empty.')) ?>,
        rowsFilled: <?php echo json_encode(__('%1% rows filled')) ?>,
        review: <?php echo json_encode(__('Review in the ingest wizard')) ?>,
        rowWord: <?php echo json_encode(__('Row')) ?>,
        leave: <?php echo json_encode(__('The grid has unsaved rows.')) ?>
    };
    var dirty = false;
    var submitting = false;

    function makeCell(field) {
        var el;
        if (field === 'scopeAndContent') {
            el = document.createElement('textarea');
            el.rows = 1;
        } else {
            el = document.createElement('input');
            el.type = 'text';
            if (field === 'levelOfDescription') { el.setAttribute('list', 'grid-levels'); }
            if (field === 'creationDatesStart' || field === 'creationDatesEnd') { el.placeholder = 'YYYY-MM-DD'; }
        }
        el.className = 'form-control grid-cell';
        el.dataset.field = field;
        el.setAttribute('aria-label', labels[field]);
        return el;
    }

    function addRow(values) {
        if (body.rows.length >= maxRows) { showAlert('warning', msg.tooMany); return null; }
        var tr = document.createElement('tr');
        var num = document.createElement('td');
        num.className = 'grid-rownum';
        tr.appendChild(num);
        fields.forEach(function (f) {
            var td = document.createElement('td');
            var cell = makeCell(f);
            if (values && values[f] != null) { cell.value = values[f]; }
            td.appendChild(cell);
            tr.appendChild(td);
        });
        var del = document.createElement('td');
        del.className = 'grid-del';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-link text-danger p-1';
        btn.title = msg.removeRow;
        btn.setAttribute('aria-label', msg.removeRow);
        btn.innerHTML = '<i class="fas fa-times"></i>';
        del.appendChild(btn);
        tr.appendChild(del);
        body.appendChild(tr);
        renumber();
        return tr;
    }

    function renumber() {
        var filled = 0;
        Array.prototype.forEach.call(body.rows, function (tr, i) {
            tr.cells[0].textContent = i + 1;
            if (rowValues(tr).some(function (v) { return v.trim() !== ''; })) { filled++; }
        });
        document.getElementById('grid-count').textContent = msg.rowsFilled.replace('%1%', filled);
    }

    function rowValues(tr) {
        return Array.prototype.map.call(tr.querySelectorAll('.grid-cell'), function (c) { return c.value; });
    }

    function cellAt(r, c) {
        var tr = body.rows[r];
        return tr ? tr.querySelectorAll('.grid-cell')[c] : null;
    }

    function position(cell) {
        var tr = cell.closest('tr');
        return {row: Array.prototype.indexOf.call(body.rows, tr), col: fields.indexOf(cell.dataset.field)};
    }

    body.addEventListener('click', function (e) {
        var btn = e.target.closest('.grid-del button');
        if (!btn) { return; }
        btn.closest('tr').remove();
        if (!body.rows.length) { addRow(); }
        dirty = true;
        renumber();
    });

    body.addEventListener('input', function (e) {
        if (e.target.classList.contains('grid-cell')) {
            dirty = true;
            e.target.classList.remove('is-invalid');
            e.target.removeAttribute('title');
        }
    });
    body.addEventListener('change', renumber);

    // Arrow up / down moves between rows (single-line cells only); Enter on
    // the last row adds a new one.
    body.addEventListener('keydown', function (e) {
        var cell = e.target;
        if (!cell.classList.contains('grid-cell') || cell.tagName === 'TEXTAREA') { return; }
        // The level cell's suggestion list uses the arrow keys itself.
        if (cell.dataset.field === 'levelOfDescription' && e.key !== 'Enter') { return; }
        var p = position(cell);
        var next = null;
        if (e.key === 'ArrowDown' || e.key === 'Enter') {
            if (p.row === body.rows.length - 1 && e.key === 'Enter') { addRow(); }
            next = cellAt(p.row + 1, p.col);
        } else if (e.key === 'ArrowUp') {
            next = cellAt(p.row - 1, p.col);
        }
        if (next) { e.preventDefault(); next.focus(); }
    });

    // Tab-separated text as spreadsheets put it on the clipboard: quoted
    // cells may hold tabs, newlines and doubled quotes.
    function parseTsv(text) {
        var rows = [], row = [], cell = '', quoted = false, i = 0, ch;
        text = text.replace(/\r\n?/g, '\n');
        while (i < text.length) {
            ch = text.charAt(i);
            if (quoted) {
                if (ch === '"') {
                    if (text.charAt(i + 1) === '"') { cell += '"'; i += 2; continue; }
                    quoted = false; i++; continue;
                }
                cell += ch; i++; continue;
            }
            if (ch === '"' && cell === '') { quoted = true; i++; continue; }
            if (ch === '\t') { row.push(cell); cell = ''; i++; continue; }
            if (ch === '\n') { row.push(cell); rows.push(row); row = []; cell = ''; i++; continue; }
            cell += ch; i++;
        }
        if (cell !== '' || row.length) { row.push(cell); rows.push(row); }
        return rows;
    }

    function norm(s) { return String(s).toLowerCase().replace(/[^a-z0-9]/g, ''); }

    // Field for a pasted heading, or null. Matches the grid labels and the
    // AtoM CSV column names (scopeAndContent etc.).
    function headingField(h) {
        var n = norm(h);
        if (!n) { return null; }
        for (var i = 0; i < fields.length; i++) {
            if (n === norm(fields[i]) || n === norm(labels[fields[i]])) { return fields[i]; }
        }
        return null;
    }

    body.addEventListener('paste', function (e) {
        var cell = e.target;
        if (!cell.classList.contains('grid-cell')) { return; }
        var text = (e.clipboardData || window.clipboardData).getData('text');
        if (!text || (text.indexOf('\t') === -1 && text.indexOf('\n') === -1 && text.indexOf('\r') === -1)) { return; }
        var data = parseTsv(text);
        if (!data.length) { return; }
        e.preventDefault();

        var start = position(cell);
        var map = null;
        var heads = data[0].map(headingField);
        if (heads.some(Boolean) && data[0].every(function (h, i) { return heads[i] || String(h).trim() === ''; })) {
            map = heads;
            data.shift();
        }

        data.forEach(function (vals, r) {
            var rowIndex = start.row + r;
            while (rowIndex >= body.rows.length) {
                if (!addRow()) { return; }
            }
            vals.forEach(function (v, c) {
                var field = map ? map[c] : fields[start.col + c];
                if (!field) { return; }
                var target = cellAt(rowIndex, fields.indexOf(field));
                if (target) {
                    target.value = v.trim();
                    target.classList.remove('is-invalid');
                }
            });
        });
        dirty = true;
        renumber();
    });

    function showAlert(kind, html) {
        var a = document.getElementById('grid-alert');
        a.className = 'alert alert-' + kind;
        a.innerHTML = html;
    }

    function collect() {
        return Array.prototype.map.call(body.rows, function (tr) {
            var o = {};
            tr.querySelectorAll('.grid-cell').forEach(function (c) { o[c.dataset.field] = c.value; });
            return o;
        });
    }

    function markErrors(errors) {
        var list = [];
        Object.keys(errors).forEach(function (idx) {
            Object.keys(errors[idx]).forEach(function (field) {
                var c = cellAt(parseInt(idx, 10), fields.indexOf(field));
                if (c) { c.classList.add('is-invalid'); c.title = errors[idx][field]; }
                list.push('<li>' + esc(msg.rowWord) + ' ' + (parseInt(idx, 10) + 1) + ', ' + esc(labels[field] || field) + ': ' + esc(errors[idx][field]) + '</li>');
            });
        });
        return list.length ? '<ul class="mb-0 mt-2">' + list.slice(0, 30).join('') + (list.length > 30 ? '<li>...</li>' : '') + '</ul>' : '';
    }

    document.getElementById('btn-add-row').addEventListener('click', function () { var tr = addRow(); if (tr) { tr.querySelector('.grid-cell').focus(); } });
    document.getElementById('btn-add-10').addEventListener('click', function () { for (var i = 0; i < 10; i++) { addRow(); } });

    document.getElementById('btn-save').addEventListener('click', function () {
        var btn = this;
        body.querySelectorAll('.is-invalid').forEach(function (c) { c.classList.remove('is-invalid'); c.removeAttribute('title'); });
        btn.disabled = true;
        showAlert('info', esc(msg.saving));
        fetch(saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({parent_id: parentId, rows: collect()})
        }).then(function (r) {
            return r.json().catch(function () { return {error: msg.failed}; });
        }).then(function (res) {
            if (res && res.ok && res.commit_url) {
                // Hand over to the ingest wizard's commit step, exactly as its
                // own "start commit" button does.
                submitting = true;
                var f = document.createElement('form');
                f.method = 'post';
                f.action = res.commit_url;
                document.body.appendChild(f);
                f.submit();
                return;
            }
            btn.disabled = false;
            var html = esc((res && res.error) || msg.failed);
            if (res && res.errors) { html += markErrors(res.errors); }
            if (res && res.review_url) { html += ' <a href="' + esc(res.review_url) + '">' + esc(msg.review) + '</a>'; }
            showAlert('danger', html);
        }).catch(function () {
            btn.disabled = false;
            showAlert('danger', esc(msg.failed));
        });
    });

    window.addEventListener('beforeunload', function (e) {
        if (dirty && !submitting) { e.preventDefault(); e.returnValue = msg.leave; return msg.leave; }
    });

    for (var i = 0; i < 10; i++) { addRow(); }
    dirty = false;
});
</script>
