<?php
$drives = $sf_data->getRaw('drives');
$templates = $sf_data->getRaw('templates');
$selectedTemplate = $sf_data->getRaw('selectedTemplate');
$mappings = $sf_data->getRaw('mappings');
$selectedDriveId = (int) $sf_data->getRaw('selectedDriveId');

$standardsBySector = [
    'archive' => [
        ['isadg', 'ISAD(G)'], ['rad', 'RAD'], ['dacs', 'DACS'], ['dc', 'Dublin Core'],
    ],
    'library' => [
        ['mods', 'MODS'], ['dc', 'Dublin Core'], ['isadg', 'ISAD(G)'],
    ],
    'museum' => [
        ['spectrum', 'SPECTRUM'], ['dc', 'Dublin Core'],
    ],
    'gallery' => [
        ['cco', 'CCO'], ['dc', 'Dublin Core'],
    ],
    'dam' => [
        ['dc', 'Dublin Core'],
    ],
];

$sectorVal = $selectedTemplate->sector ?? 'archive';
$standardVal = $selectedTemplate->standard ?? 'isadg';

$standardsAllFlat = [];
foreach ($standardsBySector as $sec => $stds) {
    foreach ($stds as [$code, $label]) {
        $standardsAllFlat[$code] = $standardsAllFlat[$code] ?? ['label' => $label, 'sectors' => []];
        $standardsAllFlat[$code]['sectors'][] = $sec;
    }
}

$targetFieldsForStandard = [];
$targetFieldGroups = []; // [code => ['extras' => [...], 'core' => [...]]]
if (class_exists('\AhgIngestPlugin\Services\IngestService')) {
    $isadg = \AhgIngestPlugin\Services\IngestService::getTargetFields('isadg');
    foreach (array_keys($standardsAllFlat) as $code) {
        $all = \AhgIngestPlugin\Services\IngestService::getTargetFields($code);
        $targetFieldsForStandard[$code] = $all;
        $extras = ($code === 'isadg') ? [] : array_values(array_diff($all, $isadg));
        $core   = ($code === 'isadg') ? array_values($all) : array_values(array_intersect($all, $isadg));
        $targetFieldGroups[$code] = ['extras' => $extras, 'core' => $core];
    }
}
$currentTargetFields = $targetFieldsForStandard[$standardVal] ?? ($targetFieldsForStandard['isadg'] ?? []);
$currentGroups = $targetFieldGroups[$standardVal] ?? ($targetFieldGroups['isadg'] ?? ['extras' => [], 'core' => $currentTargetFields]);
$standardLabels = [];
foreach ($standardsAllFlat as $code => $info) {
    $standardLabels[$code] = $info['label'];
}
?>
<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>

<h1><?php echo __('SharePoint Mapping Templates') ?></h1>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif ?>
<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif ?>

<p>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'drives']) ?>" class="btn btn-outline-secondary"><?php echo __('Drives') ?></a>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'rules']) ?>" class="btn btn-outline-secondary"><?php echo __('Rules') ?></a>
</p>

<form method="get" action="<?php echo url_for(['module' => 'sharepoint', 'action' => 'mappings']) ?>" class="row g-2 mb-3 align-items-end">
    <div class="col-md-5">
        <label class="form-label"><?php echo __('Drive') ?></label>
        <select name="drive_id" class="form-select" onchange="this.form.submit()">
            <option value="">— <?php echo __('Select drive') ?> —</option>
            <?php foreach ($drives as $d): ?>
                <option value="<?php echo (int) $d->id ?>" <?php echo $selectedDriveId === (int) $d->id ? 'selected' : '' ?>>
                    <?php echo esc_entities($d->site_title ?: '?') ?> / <?php echo esc_entities($d->drive_name ?: '?') ?>
                </option>
            <?php endforeach ?>
        </select>
    </div>
    <?php if ($selectedDriveId): ?>
    <div class="col-md-5">
        <label class="form-label"><?php echo __('Template') ?></label>
        <select name="template_id" class="form-select" onchange="this.form.submit()">
            <?php if ($templates->isEmpty()): ?>
                <option value="">— <?php echo __('No templates yet — create one below') ?> —</option>
            <?php endif ?>
            <?php foreach ($templates as $t): ?>
                <option value="<?php echo (int) $t->id ?>" <?php echo ($selectedTemplate && (int) $selectedTemplate->id === (int) $t->id) ? 'selected' : '' ?>>
                    <?php echo esc_entities($t->name) ?>
                    <?php echo $t->is_default ? ' ★' : '' ?>
                    (<?php echo esc_entities($t->sector) ?>/<?php echo esc_entities($t->standard) ?>)
                </option>
            <?php endforeach ?>
            <option value="new" <?php echo (!$selectedTemplate && $selectedDriveId) ? 'selected' : '' ?>>— <?php echo __('+ New template…') ?> —</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="button" class="btn btn-outline-primary w-100" id="discover-btn" data-drive-id="<?php echo $selectedDriveId ?>">
            <i class="fas fa-sync me-1"></i><?php echo __('Discover SP fields') ?>
        </button>
    </div>
    <?php endif ?>
</form>

<?php if ($selectedDriveId): ?>

<div id="discovery-status" class="mb-2"></div>

<form method="post" action="<?php echo url_for(['module' => 'sharepoint', 'action' => 'mappingsSave']) ?>">
    <input type="hidden" name="drive_id" value="<?php echo $selectedDriveId ?>">
    <input type="hidden" name="template_id" value="<?php echo $selectedTemplate ? (int) $selectedTemplate->id : 0 ?>">

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Template details') ?></h5></div>
        <div class="card-body row">
            <div class="col-md-4 mb-3">
                <label class="form-label"><?php echo __('Template name') ?> <span class="text-danger">*</span></label>
                <input type="text" name="template_name" class="form-control" required
                       value="<?php echo esc_entities($selectedTemplate->name ?? '') ?>"
                       placeholder="<?php echo __('e.g. Heritage Photos — ISAD(G)') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="sector"><?php echo __('Sector') ?></label>
                <select class="form-select" id="sector" name="sector">
                    <?php foreach (['archive' => 'Archive', 'library' => 'Library', 'museum' => 'Museum', 'gallery' => 'Gallery', 'dam' => 'DAM'] as $val => $label): ?>
                        <option value="<?php echo $val ?>" <?php echo $sectorVal === $val ? 'selected' : '' ?>><?php echo __($label) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="standard"><?php echo __('Standard') ?></label>
                <select class="form-select" id="standard" name="standard">
                    <?php foreach ($standardsAllFlat as $code => $info): ?>
                        <option value="<?php echo $code ?>"
                                data-sectors="<?php echo implode(',', $info['sectors']) ?>"
                                <?php echo $standardVal === $code ? 'selected' : '' ?>>
                            <?php echo esc_entities($info['label']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2 mb-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1"
                        <?php echo ($selectedTemplate && $selectedTemplate->is_default) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_default"><?php echo __('Drive default') ?></label>
                </div>
            </div>
        </div>
    </div>

    <table class="table" id="mappings-table">
        <thead>
            <tr>
                <th style="width:32%"><?php echo __('SharePoint field') ?></th>
                <th style="width:32%"><?php echo __('AtoM target') ?></th>
                <th style="width:14%"><?php echo __('Transform') ?></th>
                <th style="width:18%"><?php echo __('Default value') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody id="mappings-tbody">
        <?php foreach ($mappings as $m): ?>
            <tr class="mapping-row">
                <td>
                    <input type="text" name="source_field[]" class="form-control sp-source"
                           list="sp-columns" value="<?php echo esc_entities($m->source_field) ?>"
                           placeholder="<?php echo __('e.g. Title') ?>">
                </td>
                <td>
                    <select name="target_field[]" class="form-select atom-target">
                        <option value=""></option>
                        <?php if (!empty($currentGroups['extras'])): ?>
                            <optgroup label="<?php echo esc_entities(($standardLabels[$standardVal] ?? strtoupper($standardVal)) . ' ' . __('elements')) ?>">
                                <?php foreach ($currentGroups['extras'] as $tf): ?>
                                    <option value="<?php echo esc_entities($tf) ?>" <?php echo $m->target_field === $tf ? 'selected' : '' ?>><?php echo esc_entities($tf) ?></option>
                                <?php endforeach ?>
                            </optgroup>
                        <?php endif ?>
                        <optgroup label="<?php echo esc_entities(__('AtoM core fields')) ?>">
                            <?php foreach ($currentGroups['core'] as $tf): ?>
                                <option value="<?php echo esc_entities($tf) ?>" <?php echo $m->target_field === $tf ? 'selected' : '' ?>><?php echo esc_entities($tf) ?></option>
                            <?php endforeach ?>
                        </optgroup>
                        <?php if ($m->target_field && !in_array($m->target_field, $currentTargetFields, true)): ?>
                            <option value="<?php echo esc_entities($m->target_field) ?>" selected><?php echo esc_entities($m->target_field) ?> <?php echo __('(not in standard)') ?></option>
                        <?php endif ?>
                    </select>
                </td>
                <td>
                    <select name="transform[]" class="form-select">
                        <option value=""></option>
                        <?php foreach (['uppercase','lowercase','trim','titlecase','date_iso','strip_html'] as $t): ?>
                            <option value="<?php echo $t ?>" <?php echo $m->transform === $t ? 'selected' : '' ?>><?php echo $t ?></option>
                        <?php endforeach ?>
                    </select>
                </td>
                <td><input type="text" name="default_value[]" class="form-control" value="<?php echo esc_entities($m->default_value ?? '') ?>"></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm row-remove" title="<?php echo __('Remove') ?>"><i class="fas fa-trash"></i></button></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <datalist id="sp-columns"></datalist>

    <p>
        <button type="button" class="btn btn-outline-primary btn-sm" id="add-row">
            <i class="fas fa-plus me-1"></i><?php echo __('Add mapping') ?>
        </button>
        <span class="text-muted ms-3" id="sp-columns-count"></span>
    </p>

    <div class="d-flex justify-content-between">
        <?php if ($selectedTemplate && (int) $selectedTemplate->id > 0): ?>
            <button type="button" class="btn btn-outline-danger" id="delete-template-btn"
                    data-action="<?php echo url_for(['module' => 'sharepoint', 'action' => 'mappingTemplateDelete']) ?>"
                    data-drive-id="<?php echo $selectedDriveId ?>"
                    data-template-id="<?php echo (int) $selectedTemplate->id ?>"
                    data-template-name="<?php echo esc_entities($selectedTemplate->name) ?>">
                <i class="fas fa-trash me-1"></i><?php echo __('Delete template') ?>
            </button>
        <?php else: ?>
            <span></span>
        <?php endif ?>
        <button type="submit" class="btn btn-primary"><?php echo __('Save template') ?></button>
    </div>
</form>

<form id="delete-template-form" method="post" action="" style="display:none;">
    <input type="hidden" name="drive_id" value="<?php echo $selectedDriveId ?>">
    <input type="hidden" name="template_id" value="">
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function () {
    var tbody = document.getElementById('mappings-tbody');
    var datalist = document.getElementById('sp-columns');
    var discoverBtn = document.getElementById('discover-btn');
    var statusEl = document.getElementById('discovery-status');
    var countEl = document.getElementById('sp-columns-count');
    var sectorSel = document.getElementById('sector');
    var standardSel = document.getElementById('standard');
    var targetFieldsByStandard = <?php echo json_encode($targetFieldsForStandard, JSON_UNESCAPED_SLASHES) ?>;
    var targetFieldGroups = <?php echo json_encode($targetFieldGroups, JSON_UNESCAPED_SLASHES) ?>;
    var standardLabels = <?php echo json_encode($standardLabels, JSON_UNESCAPED_SLASHES) ?>;

    function currentTargetFields() {
        return targetFieldsByStandard[standardSel.value] || [];
    }
    function currentGroups() {
        return targetFieldGroups[standardSel.value] || {extras: [], core: currentTargetFields()};
    }

    function buildTargetSelect(currentValue) {
        var groups = currentGroups();
        var stdLabel = standardLabels[standardSel.value] || standardSel.value.toUpperCase();
        var sel = document.createElement('select');
        sel.name = 'target_field[]';
        sel.className = 'form-select atom-target';
        var blank = document.createElement('option'); blank.value = ''; sel.appendChild(blank);
        var allValues = [];
        function addOption(parent, value) {
            var o = document.createElement('option');
            o.value = value; o.textContent = value;
            if (currentValue === value) o.selected = true;
            parent.appendChild(o);
            allValues.push(value);
        }
        if (groups.extras && groups.extras.length) {
            var og1 = document.createElement('optgroup');
            og1.label = stdLabel + ' elements';
            groups.extras.forEach(function (tf) { addOption(og1, tf); });
            sel.appendChild(og1);
        }
        if (groups.core && groups.core.length) {
            var og2 = document.createElement('optgroup');
            og2.label = 'AtoM core fields';
            groups.core.forEach(function (tf) { addOption(og2, tf); });
            sel.appendChild(og2);
        }
        if (currentValue && allValues.indexOf(currentValue) === -1) {
            var orphan = document.createElement('option');
            orphan.value = currentValue; orphan.selected = true;
            orphan.textContent = currentValue + ' (not in standard)';
            sel.appendChild(orphan);
        }
        return sel;
    }

    function rebuildAllTargetSelects() {
        var rows = tbody.querySelectorAll('tr.mapping-row');
        rows.forEach(function (row) {
            var old = row.querySelector('select.atom-target');
            var current = old ? old.value : '';
            var fresh = buildTargetSelect(current);
            old.parentNode.replaceChild(fresh, old);
        });
    }

    function filterStandards() {
        var sector = sectorSel.value;
        var currentVal = standardSel.value;
        var firstVisible = null;
        var currentVisible = false;
        for (var i = 0; i < standardSel.options.length; i++) {
            var opt = standardSel.options[i];
            var sectors = (opt.getAttribute('data-sectors') || '').split(',');
            var show = sectors.indexOf(sector) !== -1;
            opt.style.display = show ? '' : 'none';
            opt.disabled = !show;
            if (show && !firstVisible) firstVisible = opt.value;
            if (show && opt.value === currentVal) currentVisible = true;
        }
        if (!currentVisible && firstVisible) {
            standardSel.value = firstVisible;
            rebuildAllTargetSelects();
        }
    }

    sectorSel.addEventListener('change', filterStandards);
    standardSel.addEventListener('change', rebuildAllTargetSelects);
    filterStandards();

    document.getElementById('add-row').addEventListener('click', function () {
        var tr = document.createElement('tr');
        tr.className = 'mapping-row';
        var tdSource = document.createElement('td');
        var src = document.createElement('input'); src.type = 'text'; src.name = 'source_field[]'; src.className = 'form-control sp-source'; src.setAttribute('list', 'sp-columns'); src.placeholder = 'e.g. Title';
        tdSource.appendChild(src);
        var tdTarget = document.createElement('td'); tdTarget.appendChild(buildTargetSelect(''));
        var tdTransform = document.createElement('td');
        tdTransform.innerHTML = '<select name="transform[]" class="form-select"><option value=""></option><option>uppercase</option><option>lowercase</option><option>trim</option><option>titlecase</option><option>date_iso</option><option>strip_html</option></select>';
        var tdDefault = document.createElement('td'); tdDefault.innerHTML = '<input type="text" name="default_value[]" class="form-control">';
        var tdAct = document.createElement('td'); tdAct.innerHTML = '<button type="button" class="btn btn-outline-danger btn-sm row-remove"><i class="fas fa-trash"></i></button>';
        tr.appendChild(tdSource); tr.appendChild(tdTarget); tr.appendChild(tdTransform); tr.appendChild(tdDefault); tr.appendChild(tdAct);
        tbody.appendChild(tr);
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.row-remove');
        if (btn) btn.closest('tr').remove();
    });

    var deleteBtn = document.getElementById('delete-template-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            var name = deleteBtn.dataset.templateName || 'this template';
            if (!confirm('Delete "' + name + '" and all of its mappings? This cannot be undone.')) return;
            var form = document.getElementById('delete-template-form');
            form.action = deleteBtn.dataset.action;
            form.querySelector('input[name="template_id"]').value = deleteBtn.dataset.templateId;
            form.submit();
        });
    }

    if (discoverBtn) {
        discoverBtn.addEventListener('click', function () {
            statusEl.innerHTML = '<small class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Discovering columns from SharePoint…</small>';
            fetch('<?php echo url_for(['module' => 'sharepoint', 'action' => 'columns']) ?>?drive_id=' + discoverBtn.dataset.driveId, {
                credentials: 'same-origin', headers: { 'Accept': 'application/json' },
            }).then(function (r) {
                if (!r.ok) return r.json().then(function (j) { throw new Error(j.error || ('HTTP ' + r.status)); });
                return r.json();
            }).then(function (json) {
                var cols = json.columns || [];
                var userCols = cols.filter(function (c) { return !c.isSystem; });
                datalist.innerHTML = '';
                userCols.forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = c.name;
                    opt.label = c.displayName + '  (' + c.type + ')';
                    datalist.appendChild(opt);
                });
                var sorted = userCols.slice().sort(function (a, b) {
                    var aRank = a.readOnly ? 1 : 0;
                    var bRank = b.readOnly ? 1 : 0;
                    if (aRank !== bRank) return aRank - bRank;
                    return a.displayName.localeCompare(b.displayName);
                });
                var editableCount = sorted.filter(function (c) { return !c.readOnly; }).length;
                statusEl.innerHTML = '<div class="alert alert-success py-2 mb-0">'
                    + '<i class="fas fa-check me-1"></i>Discovered <strong>' + userCols.length + '</strong> user-facing SharePoint fields '
                    + '(' + editableCount + ' editable, ' + (userCols.length - editableCount) + ' read-only). '
                    + (cols.length - userCols.length) + ' SP internal columns hidden.</div>';
                countEl.textContent = userCols.length + ' SP fields available (' + cols.length + ' raw, ' + (cols.length - userCols.length) + ' system filtered).';
                if (tbody.children.length === 0) {
                    sorted.forEach(function (c) {
                        document.getElementById('add-row').click();
                        var lastRow = tbody.lastElementChild;
                        var srcInput = lastRow.querySelector('.sp-source');
                        srcInput.value = c.name;
                        srcInput.title = c.displayName + '  (' + c.type + (c.readOnly ? ', read-only' : '') + ')';
                        if (c.readOnly) {
                            lastRow.classList.add('table-secondary');
                            lastRow.style.opacity = '0.7';
                        }
                    });
                }
            }).catch(function (e) {
                statusEl.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + e.message + '</div>';
            });
        });
        // Auto-trigger discovery only when there are no rows yet
        if (tbody.children.length === 0) {
            discoverBtn.click();
        }
    }
})();
</script>

<?php else: ?>
    <div class="alert alert-info"><?php echo __('Select a drive to edit its mapping templates.') ?></div>
<?php endif ?>
