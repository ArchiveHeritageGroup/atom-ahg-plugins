<?php
$rule = $sf_data->getRaw('rule');
$drives = $sf_data->getRaw('drives');
$repositories = $sf_data->getRaw('repositories');
$parentLabel = $sf_data->getRaw('parentLabel');
$templatesByDrive = $sf_data->getRaw('templatesByDrive');
$flags = $rule && $rule->process_flags ? (json_decode($rule->process_flags, true) ?: []) : [];
$templatesJson = [];
foreach ($templatesByDrive as $driveId => $list) {
    $templatesJson[$driveId] = array_map(function ($t) {
        return ['id' => (int) $t->id, 'name' => $t->name, 'sector' => $t->sector, 'standard' => $t->standard, 'is_default' => (int) $t->is_default];
    }, $list->all());
}
?>
<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>

<h1><?php echo $rule ? __('Edit rule') : __('New rule') ?></h1>

<form method="post" action="<?php echo url_for(['module' => 'sharepoint', 'action' => 'ruleSave']) ?>">
    <?php if ($rule): ?>
        <input type="hidden" name="id" value="<?php echo (int) $rule->id ?>">
    <?php endif ?>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Source') ?></h5></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label"><?php echo __('Name') ?></label>
                <input type="text" name="name" class="form-control" required value="<?php echo esc_entities($rule->name ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label"><?php echo __('Drive') ?></label>
                <select name="drive_id" class="form-select" required>
                    <option value="">— <?php echo __('Select drive') ?> —</option>
                    <?php foreach ($drives as $d): ?>
                        <option value="<?php echo (int) $d->id ?>" <?php echo ($rule && (int)$rule->drive_id === (int)$d->id) ? 'selected' : '' ?>>
                            <?php echo esc_entities($d->site_title ?: '?') ?> / <?php echo esc_entities($d->drive_name ?: '?') ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Folder path (optional)') ?></label>
                    <input type="text" name="folder_path" class="form-control" placeholder="/Shared Documents/Archive" value="<?php echo esc_entities($rule->folder_path ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('File pattern (CSV of globs)') ?></label>
                    <input type="text" name="file_pattern" class="form-control" placeholder="*.pdf,*.tif" value="<?php echo esc_entities($rule->file_pattern ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label d-block"><?php echo __('Purview retention/disposal label gating') ?></label>
                <?php $hasLabel = !empty($rule->retention_label ?? ''); ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="retention_mode" id="retention_mode_off" value="off" <?php echo $hasLabel ? '' : 'checked' ?>>
                    <label class="form-check-label" for="retention_mode_off">
                        <?php echo __('Ingest all matching files') ?>
                        <small class="text-muted ms-1">(<?php echo __('no Purview filter — use this for demos or tenants without Purview') ?>)</small>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="retention_mode" id="retention_mode_on" value="on" <?php echo $hasLabel ? 'checked' : '' ?>>
                    <label class="form-check-label" for="retention_mode_on">
                        <?php echo __('Only items carrying specific Purview retention label(s)') ?>
                    </label>
                </div>
                <input type="text" name="retention_label" id="retention_label" class="form-control mt-2"
                       placeholder="<?php echo __('e.g. Ready for Archive, Permanent') ?>"
                       value="<?php echo esc_entities($rule->retention_label ?? '') ?>"
                       <?php echo $hasLabel ? '' : 'disabled' ?>>
                <small class="text-muted">
                    <?php echo __('Comma-separated label names exactly as defined in Microsoft Purview.') ?>
                </small>
            </div>
            <div class="mb-3">
                <label class="form-label" for="template_id"><?php echo __('Mapping template') ?></label>
                <select name="template_id" id="template_id" class="form-select">
                    <option value="">— <?php echo __('Use drive default') ?> —</option>
                </select>
                <small class="text-muted"><?php echo __('Templates are managed at') ?>
                    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'mappings']) ?>"><?php echo __('SharePoint → Mappings') ?></a>.
                </small>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Target') ?></h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?php echo __('Sector') ?></label>
                    <select name="sector" class="form-select">
                        <?php foreach (['archive','library','museum','gallery','dam'] as $s): ?>
                            <option value="<?php echo $s ?>" <?php echo ($rule && $rule->sector === $s) ? 'selected' : '' ?>><?php echo ucfirst($s) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?php echo __('Standard') ?></label>
                    <select name="standard" class="form-select">
                        <?php foreach (['isadg','dacs','dc','mods','rad'] as $s): ?>
                            <option value="<?php echo $s ?>" <?php echo ($rule && $rule->standard === $s) ? 'selected' : '' ?>><?php echo strtoupper($s) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="repository_id"><?php echo __('Repository (optional)') ?></label>
                    <select name="repository_id" id="repository_id" class="form-select tom-select-repo">
                        <option value="">— <?php echo __('No repository') ?> —</option>
                        <?php foreach ($repositories as $repo): ?>
                            <option value="<?php echo (int) $repo->id ?>"
                                    data-identifier="<?php echo esc_entities($repo->identifier ?? '') ?>"
                                    <?php echo ($rule && (int) $rule->repository_id === (int) $repo->id) ? 'selected' : '' ?>>
                                <?php
                                $bits = [];
                                if (!empty($repo->identifier)) { $bits[] = $repo->identifier; }
                                $bits[] = $repo->name ?: ('Repository #' . $repo->id);
                                echo esc_entities(implode(' — ', $bits));
                                ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Parent placement') ?></label>
                    <select name="parent_placement" class="form-select">
                        <?php foreach (['top_level','existing','new'] as $s): ?>
                            <option value="<?php echo $s ?>" <?php echo ($rule && $rule->parent_placement === $s) ? 'selected' : '' ?>><?php echo str_replace('_',' ',ucfirst($s)) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="parent_id"><?php echo __('Parent (when existing)') ?></label>
                    <select name="parent_id" id="parent_id" class="form-select tom-select-parent" placeholder="<?php echo __('Type to search archival descriptions…') ?>">
                        <?php if ($parentLabel): ?>
                            <option value="<?php echo (int) $parentLabel->id ?>" selected
                                    data-identifier="<?php echo esc_entities($parentLabel->identifier ?? '') ?>">
                                <?php
                                $bits = [];
                                if (!empty($parentLabel->identifier)) { $bits[] = $parentLabel->identifier; }
                                $bits[] = $parentLabel->title ?: ('Record #' . $parentLabel->id);
                                echo esc_entities(implode(' — ', $bits));
                                ?>
                            </option>
                        <?php endif ?>
                    </select>
                    <small class="text-muted"><?php echo __('Only used when Parent placement is set to "existing".') ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><?php echo __('AI processing') ?></h5></div>
        <div class="card-body row">
            <?php foreach (['virus_scan'=>'Virus scan','ocr'=>'OCR','ner'=>'NER','summarize'=>'Summarize','spellcheck'=>'Spellcheck','translate'=>'Translate','format_id'=>'Format ID','face_detect'=>'Face detect'] as $key=>$label): ?>
            <div class="col-md-3 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="process_<?php echo $key ?>" id="proc_<?php echo $key ?>" value="1" <?php echo !empty($flags[$key]) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="proc_<?php echo $key ?>"><?php echo __($label) ?></label>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Schedule') ?></h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Cron expression') ?></label>
                    <input type="text" name="schedule_cron" class="form-control" value="<?php echo esc_entities($rule->schedule_cron ?? '*/15 * * * *') ?>">
                    <small class="text-muted"><?php echo __('Standard cron syntax. The cron daemon must invoke `php symfony sharepoint:auto-ingest` periodically.') ?></small>
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="is_enabled" <?php echo (!$rule || $rule->is_enabled) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_enabled"><?php echo __('Enabled') ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'rules']) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
        <button type="submit" class="btn btn-primary"><?php echo __('Save rule') ?></button>
    </div>
</form>

<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function () {
    var templatesByDrive = <?php echo json_encode($templatesJson, JSON_UNESCAPED_SLASHES) ?>;
    var initialTemplateId = <?php echo $rule && !empty($rule->template_id) ? (int) $rule->template_id : 0 ?>;
    var driveSelEl = document.querySelector('select[name="drive_id"]');
    var templateSelEl = document.getElementById('template_id');

    function populateTemplates() {
        if (!driveSelEl || !templateSelEl) return;
        var driveId = String(parseInt(driveSelEl.value, 10) || 0);
        var prev = parseInt(templateSelEl.value, 10) || initialTemplateId || 0;
        while (templateSelEl.options.length > 1) templateSelEl.remove(1);
        var list = templatesByDrive[driveId] || [];
        list.forEach(function (t) {
            var o = document.createElement('option');
            o.value = String(t.id);
            o.textContent = t.name + (t.is_default ? ' ★' : '') + ' (' + t.sector + '/' + t.standard + ')';
            if (prev && t.id === prev) o.selected = true;
            templateSelEl.appendChild(o);
        });
    }
    if (driveSelEl) driveSelEl.addEventListener('change', populateTemplates);
    populateTemplates();

    if (typeof TomSelect === 'undefined') return;

    var repoEl = document.getElementById('repository_id');
    if (repoEl) {
        new TomSelect(repoEl, {
            plugins: ['clear_button'],
            searchField: ['text', 'identifier'],
            sortField: { field: 'text', direction: 'asc' },
            render: {
                option: function (data, escape) {
                    var ident = data.identifier ? '<code class="me-2">' + escape(data.identifier) + '</code>' : '';
                    return '<div>' + ident + escape(data.text.replace(/^.*? — /, '')) + '</div>';
                },
            },
        });
    }

    var parentEl = document.getElementById('parent_id');
    var parentTs = null;
    if (parentEl) {
        var parentSearchUrl = '<?php echo url_for(['module' => 'ingest', 'action' => 'searchParent']) ?>';
        parentTs = new TomSelect(parentEl, {
            plugins: ['clear_button'],
            valueField: 'id',
            labelField: 'title',
            searchField: ['title', 'identifier', 'slug'],
            preload: false,
            maxOptions: 50,
            load: function (query, callback) {
                if (!query || query.length < 2) { callback(); return; }
                fetch(parentSearchUrl + '?q=' + encodeURIComponent(query), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                .then(function (r) { return r.json(); })
                .then(function (data) { callback(Array.isArray(data) ? data : []); })
                .catch(function () { callback(); });
            },
            render: {
                option: function (data, escape) {
                    var ident = data.identifier ? '<code class="me-2">' + escape(data.identifier) + '</code>' : '';
                    return '<div>' + ident + escape(data.title || ('Record #' + data.id)) + '</div>';
                },
                item: function (data, escape) {
                    var ident = data.identifier ? '<code class="me-2">' + escape(data.identifier) + '</code>' : '';
                    return '<div>' + ident + escape(data.title || data.text || ('Record #' + data.id)) + '</div>';
                },
            },
        });
    }

    var retentionRadios = document.querySelectorAll('input[name="retention_mode"]');
    var retentionInput = document.getElementById('retention_label');
    function syncRetention() {
        var on = document.getElementById('retention_mode_on').checked;
        if (retentionInput) {
            retentionInput.disabled = !on;
            if (!on) retentionInput.value = '';
        }
    }
    retentionRadios.forEach(function (r) { r.addEventListener('change', syncRetention); });
    syncRetention();

    var placementSel = document.querySelector('select[name="parent_placement"]');
    function syncParentEnabled() {
        if (!placementSel || !parentEl) return;
        var enabled = placementSel.value === 'existing';
        var wrapper = parentEl.closest('.col-md-6');
        if (parentTs) {
            if (enabled) { parentTs.enable(); } else { parentTs.clear(true); parentTs.disable(); }
        } else {
            parentEl.disabled = !enabled;
        }
        if (wrapper) { wrapper.style.opacity = enabled ? '1' : '0.5'; }
    }
    if (placementSel) {
        placementSel.addEventListener('change', syncParentEnabled);
        syncParentEnabled();
    }
});
</script>
